<?php

function get_all_recurring_transactions_for_projection($conn, $user_id) {
    $transactions = [];
    $sql = "SELECT type, amount, frequency, next_due_date FROM recurring_transactions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
    return $transactions;
}

function get_monthly_cash_flow($conn, $user_id) {
    $monthly_income = 0;
    $monthly_expense = 0;

    $sql = "SELECT amount, type, frequency FROM recurring_transactions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $monthly_amount = $row['amount'];
        // Normalizza tutto su base mensile
        if ($row['frequency'] === 'yearly') {
            $monthly_amount /= 12;
        } elseif ($row['frequency'] === 'weekly') {
            $monthly_amount *= 4.33; // Media settimane in un mese
        } elseif ($row['frequency'] === 'bimonthly') {
            $monthly_amount /= 2;
        }

        if ($row['type'] === 'income') {
            $monthly_income += $monthly_amount;
        } else {
            $monthly_expense += $monthly_amount;
        }
    }
    $stmt->close();
    return $monthly_income - $monthly_expense;
}


function get_category_type($conn, $category_id, $user_id) {
    $sql = "SELECT type FROM categories WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['type'];
    }
    return null;
}

function get_transaction_details_for_ui($conn, $transaction_id, $user_id) {
    $sql = "SELECT
                t.id, t.description, t.amount, t.transaction_date, t.invoice_path,
                t.account_id, t.category_id,
                c.name as category_name, a.name as account_name,
                GROUP_CONCAT(tags.name SEPARATOR ', ') as tags
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
            LEFT JOIN tags ON tt.tag_id = tags.id
            WHERE t.id = ? AND t.user_id = ?
            GROUP BY t.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    return $transaction;
}

function get_shared_fund_details_for_creator($conn, $fund_id, $user_id) {
    $sql = "SELECT * FROM shared_funds WHERE id = ? AND creator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $fund_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fund = $result->fetch_assoc();
    $stmt->close();
    return $fund;
}
function get_all_due_recurring_transactions($conn) {
    $transactions = [];
    $today = date('Y-m-d');
    $sql = "SELECT * FROM recurring_transactions WHERE next_due_date <= ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $stmt->close();
    }
    return $transactions;
}

function check_budget_alerts($conn, $user_id) {
    $budgets = get_user_budgets($conn, $user_id); // Questa funzione calcola già la spesa corrente
    $current_month_year = date('Y-m');

    foreach ($budgets as $budget) {
        if ($budget['amount'] <= 0) continue; // Salta i budget non validi

        $percentage_spent = ($budget['spent'] / $budget['amount']) * 100;
        $budget_id = $budget['id'];
        $category_name = $budget['category_name'];

        // Controlla se il budget è stato superato
        if ($percentage_spent >= 100) {
            $notification_type = 'budget_exceeded';
            $message = "Hai superato il budget per la categoria '{$category_name}' questo mese!";
        }
        // Controlla se il budget è quasi esaurito (es. >= 90%)
        elseif ($percentage_spent >= 90) {
            $notification_type = 'budget_warning';
            $message = "Stai per superare il budget per la categoria '{$category_name}'! Speso: " . round($percentage_spent) . "%";
        } else {
            continue; // Nessuna notifica necessaria per questo budget
        }

        // Evita di inviare notifiche duplicate: controlla se una notifica simile per questo budget
        // è già stata inviata questo mese.
        $sql_check = "SELECT id FROM notifications WHERE user_id = ? AND related_id = ? AND message LIKE ?";
        $stmt_check = $conn->prepare($sql_check);
        $check_message = "%{$category_name}%"; // Controlla solo per il nome della categoria per semplicità
        $stmt_check->bind_param("iis", $user_id, $budget_id, $check_message);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows == 0) {
            // Nessuna notifica simile trovata, quindi creala.
            create_notification($conn, $user_id, $notification_type, $message, $budget_id);
        }
        $stmt_check->close();
    }
}

function process_and_link_tags($conn, $user_id, $transaction_id, $tags_string) {
    // 1. Rimuovi i tag esistenti per questa transazione per evitare duplicati
    $sql_delete = "DELETE FROM transaction_tags WHERE transaction_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $transaction_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. Processa la nuova stringa di tag
    $tags_array = array_unique(array_filter(array_map('trim', explode(',', $tags_string))));

    if (empty($tags_array)) {
        return; // Nessun tag da processare
    }

    // Prepara le query per inserire/collegare i tag
    $sql_get_or_create_tag = "INSERT INTO tags (user_id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
    $stmt_tag = $conn->prepare($sql_get_or_create_tag);

    $sql_link_tag = "INSERT INTO transaction_tags (transaction_id, tag_id) VALUES (?, ?)";
    $stmt_link = $conn->prepare($sql_link_tag);

    foreach ($tags_array as $tag_name) {
        // Crea il tag se non esiste, e ottieni il suo ID
        $stmt_tag->bind_param("is", $user_id, $tag_name);
        $stmt_tag->execute();
        $tag_id = $stmt_tag->insert_id;

        // Collega il tag alla transazione
        if ($tag_id) {
            $stmt_link->bind_param("ii", $transaction_id, $tag_id);
            $stmt_link->execute();
        }
    }
    $stmt_tag->close();
    $stmt_link->close();
}

function get_user_tags($conn, $user_id) {
    $tags = [];
    $sql = "SELECT id, name FROM tags WHERE user_id = ? ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    $stmt->close();
    return $tags;
}


function get_admin_stats($conn) {
    $stats = [
        'total_users' => 0,
        'pro_users' => 0,
        'free_users' => 0,
        'new_users_last_30_days' => 0
    ];

    // Utenti totali
    $result = $conn->query("SELECT COUNT(id) as total FROM users");
    if ($row = $result->fetch_assoc()) {
        $stats['total_users'] = $row['total'];
    }

    // Suddivisione abbonamenti
    $result = $conn->query("SELECT subscription_status, COUNT(id) as count FROM users GROUP BY subscription_status");
    while ($row = $result->fetch_assoc()) {
        if ($row['subscription_status'] === 'active' || $row['subscription_status'] === 'lifetime') {
            $stats['pro_users'] += $row['count'];
        } else {
            $stats['free_users'] += $row['count'];
        }
    }

    // Nuovi utenti negli ultimi 30 giorni
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    $sql = "SELECT COUNT(id) as new_users FROM users WHERE created_at >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $thirty_days_ago);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['new_users_last_30_days'] = $row['new_users'];
    }
    $stmt->close();

    return $stats;
}

function get_users_paginated_and_searched($conn, $search_term, $page, $users_per_page) {
    $offset = ($page - 1) * $users_per_page;
    $search_query = "%" . $search_term . "%";

    // Query per contare il totale degli utenti (filtrati)
    $sql_count = "SELECT COUNT(id) as total FROM users WHERE (username LIKE ? OR email LIKE ?)";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("ss", $search_query, $search_query);
    $stmt_count->execute();
    $total_users = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_users / $users_per_page);
    $stmt_count->close();

    // Query per recuperare gli utenti della pagina corrente
    $sql = "SELECT * FROM users WHERE (username LIKE ? OR email LIKE ?) ORDER BY id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $search_query, $search_query, $users_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return [
        'users' => $users,
        'total_pages' => $total_pages
    ];
}

/**
 * Ottiene i dati di un utente tramite il suo ID.
 */
function get_user_by_id($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Trova un utente tramite il suo codice amico.
 */
function find_user_by_friend_code($conn, $friend_code) {
    $sql = "SELECT id, username FROM users WHERE friend_code = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $friend_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// =============================================================================
// FUNZIONI NOTE
// =============================================================================

/**
 * Ottiene tutte le note di un utente.
 */
function get_notes_for_user($conn, $user_id, $filters = []) {
    $notes = [];
    $params = ['iii', $user_id, $user_id, $user_id];

    $sql = "SELECT
                n.id, n.title, n.content, n.todolist_content, n.updated_at,
                n.creator_id, n.user_id as owner_id,
                n.transaction_id, t.description as transaction_description,
                ns_self.permission
            FROM
                notes n
            LEFT JOIN note_shares ns_all ON n.id = ns_all.note_id
            LEFT JOIN note_shares ns_self ON n.id = ns_self.note_id AND ns_self.user_id = ?
            LEFT JOIN transactions t ON n.transaction_id = t.id AND t.user_id = n.user_id
            WHERE
                (n.user_id = ? OR ns_all.user_id = ?)";

    if (!empty($filters['text_search'])) {
        $search_term = '%' . $filters['text_search'] . '%';
        $sql .= " AND (n.title LIKE ? OR n.content LIKE ?)";
        $params[0] .= 'ss';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    if (!empty($filters['id_search'])) {
        $sql .= " AND n.id = ?";
        $params[0] .= 'i';
        $params[] = intval($filters['id_search']);
    }
    if (!empty($filters['date_search'])) {
        $sql .= " AND DATE(n.created_at) = ?";
        $params[0] .= 's';
        $params[] = $filters['date_search'];
    }

    $sql .= " GROUP BY n.id";

    $sort_order = 'n.updated_at DESC';
    if (!empty($filters['sort'])) {
        if ($filters['sort'] === 'oldest') {
            $sort_order = 'n.updated_at ASC';
        }
    }
    $sql .= " ORDER BY $sort_order";

    $stmt = $conn->prepare($sql);
    if (count($params) > 1) {
        $stmt->bind_param(...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    $stmt->close();
    return $notes;
}

/**
 * Condivide una nota con un altro utente tramite codice amico.
 */
function share_note_with_user($conn, $note_id, $sharer_user_id, $friend_code, $permission) {
    if ($permission !== 'view' && $permission !== 'edit') {
        return ['success' => false, 'message' => 'Permesso non valido.'];
    }

    $friend = find_user_by_friend_code($conn, $friend_code);
    if (!$friend) {
        return ['success' => false, 'message' => 'Codice amico non valido o utente non trovato.'];
    }
    $friend_id = $friend['id'];

    if ($friend_id == $sharer_user_id) {
        return ['success' => false, 'message' => 'Non puoi condividere una nota con te stesso.'];
    }

    $sql_check_creator = "SELECT creator_id FROM notes WHERE id = ? AND creator_id = ?";
    $stmt_check_creator = $conn->prepare($sql_check_creator);
    $stmt_check_creator->bind_param("ii", $note_id, $sharer_user_id);
    $stmt_check_creator->execute();
    $stmt_check_creator->store_result();
    if ($stmt_check_creator->num_rows == 0) {
        $stmt_check_creator->close();
        return ['success' => false, 'message' => 'Solo il creatore originale può condividere questa nota.'];
    }
    $stmt_check_creator->close();

    $sql_share = "INSERT INTO note_shares (note_id, user_id, permission) VALUES (?, ?, ?)";
    $stmt_share = $conn->prepare($sql_share);
    $stmt_share->bind_param("iis", $note_id, $friend_id, $permission);

    $success = $stmt_share->execute();
    $errno = $conn->errno;
    $stmt_share->close();

    if ($success) {
        return ['success' => true, 'message' => 'Nota condivisa con successo con ' . htmlspecialchars($friend['username']) . '!'];
    } else {
        if ($errno == 1062) {
            return ['success' => false, 'message' => 'Questa nota è già condivisa con questo utente.'];
        }
        return ['success' => false, 'message' => 'Errore durante la condivisione della nota.'];
    }
}

/**
 * Ottiene una singola nota tramite ID, verificando i permessi di condivisione.
 */
function get_note_by_id($conn, $note_id, $user_id) {
    $sql = "SELECT n.*, n.user_id as owner_id, ns.permission
            FROM notes n
            LEFT JOIN note_shares ns ON n.id = ns.note_id AND ns.user_id = ?
            WHERE n.id = ? AND (n.user_id = ? OR ns.user_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $note_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $note = $result->fetch_assoc();
    $stmt->close();
    return $note;
}

/**
 * Ottiene i membri con cui una nota è condivisa.
 */
function get_note_members($conn, $note_id, $current_user_id) {
    // Sicurezza: Prima, verifica che l'utente corrente sia il creatore della nota
    $sql_check = "SELECT creator_id FROM notes WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) return []; // Gestione errore preparazione

    $stmt_check->bind_param("i", $note_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $note_data = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$note_data || $note_data['creator_id'] != $current_user_id) {
        // Non autorizzato o nota non trovata, restituisce un array vuoto
        return [];
    }

    // Se autorizzato, recupera i membri
    $members = [];
    $sql = "SELECT u.id, u.username, ns.permission
            FROM users u
            JOIN note_shares ns ON u.id = ns.user_id
            WHERE ns.note_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return []; // Gestione errore preparazione

    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
    return $members;
}

/**
 * Rimuove la condivisione di una nota per un utente specifico.
 */
function remove_note_share($conn, $note_id, $member_id, $current_user_id) {
    // Sicurezza: L'azione è permessa se l'utente corrente è il creatore della nota,
    // o se l'utente corrente sta rimuovendo se stesso (abbandonando la nota).
    $sql_check = "SELECT creator_id FROM notes WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $note_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $note_data = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$note_data) {
        return ['success' => false, 'message' => 'Nota non trovata.'];
    }

    $is_creator = $note_data['creator_id'] == $current_user_id;
    $is_self_remove = $member_id == $current_user_id;

    if (!$is_creator && !$is_self_remove) {
        return ['success' => false, 'message' => 'Azione non autorizzata.'];
    }

    // Prosegui con l'eliminazione
    $sql_delete = "DELETE FROM note_shares WHERE note_id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $note_id, $member_id);

    $success = $stmt_delete->execute();
    $affected_rows = $stmt_delete->affected_rows;
    $stmt_delete->close();

    if ($success) {
        if ($affected_rows > 0) {
            $message = $is_self_remove ? 'Hai abbandonato la nota.' : 'Condivisione rimossa con successo.';
            return ['success' => true, 'message' => $message];
        } else {
            return ['success' => false, 'message' => 'Condivisione non trovata o già rimossa.'];
        }
    } else {
        return ['success' => false, 'message' => 'Errore durante la rimozione della condivisione.'];
    }
}

/**
 * Aggiorna i permessi di un membro per una nota condivisa.
 */
function update_note_permission($conn, $note_id, $member_id, $permission, $current_user_id) {
    // Validazione del permesso
    if ($permission !== 'view' && $permission !== 'edit') {
        return ['success' => false, 'message' => 'Permesso non valido.'];
    }

    // Sicurezza: Solo il creatore può modificare i permessi
    $sql_check = "SELECT creator_id FROM notes WHERE id = ? AND creator_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $note_id, $current_user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows == 0) {
        $stmt_check->close();
        return ['success' => false, 'message' => 'Azione non autorizzata.'];
    }
    $stmt_check->close();

    // Aggiorna il permesso
    $sql_update = "UPDATE note_shares SET permission = ? WHERE note_id = ? AND user_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sii", $permission, $note_id, $member_id);

    $success = $stmt_update->execute();
    $affected_rows = $stmt_update->affected_rows;
    $stmt_update->close();

    if ($success) {
        if ($affected_rows > 0) {
            return ['success' => true, 'message' => 'Permessi aggiornati.'];
        } else {
            return ['success' => false, 'message' => 'Nessuna condivisione trovata da aggiornare.'];
        }
    } else {
        return ['success' => false, 'message' => 'Errore durante l\'aggiornamento dei permessi.'];
    }
}

/**
 * Crea una nuova nota e la condivide con tutti i membri di un fondo.
 */
function create_and_share_note_with_fund_members($conn, $note_content, $creator_id, $fund_id) {
    // 1. Crea la nota
    $sql_create_note = "INSERT INTO notes (user_id, creator_id, title, content) VALUES (?, ?, ?, ?)";
    $stmt_create = $conn->prepare($sql_create_note);
    $note_title = "Nota per spesa di gruppo"; // Titolo generico
    $stmt_create->bind_param("iiss", $creator_id, $creator_id, $note_title, $note_content);
    $stmt_create->execute();
    $note_id = $stmt_create->insert_id;
    $stmt_create->close();

    if (!$note_id) {
        return null;
    }

    // 2. Ottieni i membri del fondo
    $members = get_fund_members($conn, $fund_id);

    // 3. Condividi la nota con ogni membro (tranne il creatore)
    $sql_share = "INSERT INTO note_shares (note_id, user_id, permission) VALUES (?, ?, 'view')";
    $stmt_share = $conn->prepare($sql_share);
    foreach ($members as $member) {
        if ($member['id'] != $creator_id) {
            $stmt_share->bind_param("ii", $note_id, $member['id']);
            $stmt_share->execute();
        }
    }
    $stmt_share->close();

    return $note_id;
}


// =============================================================================
// FUNZIONI FONDI COMUNI E NOTIFICHE
// =============================================================================

/**
 * Crea una notifica per un utente.
 */
function create_notification($conn, $user_id, $type, $message, $related_id) {
    $sql = "INSERT INTO notifications (user_id, type, message, related_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $user_id, $type, $message, $related_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Ottiene le notifiche non lette di un utente.
 */
function get_unread_notifications($conn, $user_id) {
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

/**
 * Ottiene tutti i fondi comuni di cui un utente è membro.
 */
function get_shared_funds_for_user($conn, $user_id) {
    $funds = [];
    $sql = "SELECT sf.id, sf.name, sf.target_amount, sf.creator_id,
                   (SELECT SUM(amount) FROM shared_fund_contributions WHERE fund_id = sf.id) as total_contributed
            FROM shared_funds sf
            JOIN shared_fund_members sfm ON sf.id = sfm.fund_id
            WHERE sfm.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['total_contributed'] = $row['total_contributed'] ?? 0;
        $funds[] = $row;
    }
    $stmt->close();
    return $funds;
}

/**
 * Ottiene i dettagli di un singolo fondo, verificando che l'utente sia membro.
 */
function get_shared_fund_details($conn, $fund_id, $user_id) {
    $sql = "SELECT sf.*,
                   (SELECT SUM(amount) FROM shared_fund_contributions WHERE fund_id = sf.id) as total_contributed
            FROM shared_funds sf
            JOIN shared_fund_members sfm ON sf.id = sfm.fund_id
            WHERE sf.id = ? AND sfm.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $fund_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fund = $result->fetch_assoc();
    if ($fund) {
        $fund['total_contributed'] = $fund['total_contributed'] ?? 0;
    }
    $stmt->close();
    return $fund;
}

/**
 * Ottiene i membri di un fondo.
 */
function get_fund_members($conn, $fund_id) {
    $members = [];
    $sql = "SELECT u.id, u.username FROM users u
            JOIN shared_fund_members sfm ON u.id = sfm.user_id
            WHERE sfm.fund_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
    return $members;
}

/**
 * Ottiene i contributi di un fondo.
 */
function get_fund_contributions($conn, $fund_id) {
    $contributions = [];
    $sql = "SELECT sfc.amount, sfc.contribution_date, u.username
            FROM shared_fund_contributions sfc
            JOIN users u ON sfc.user_id = u.id
            WHERE sfc.fund_id = ?
            ORDER BY sfc.contribution_date DESC, sfc.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contributions[] = $row;
    }
    $stmt->close();
    return $contributions;
}

/**
 * Ottiene le spese di un gruppo.
 */
function get_group_expenses($conn, $fund_id) {
    $expenses = [];
    $sql = "SELECT
                ge.*,
                u.username as paid_by_username,
                c.name as category_name,
                c.icon as category_icon,
                n.id as note_id
            FROM
                group_expenses ge
            JOIN
                users u ON ge.paid_by_user_id = u.id
            LEFT JOIN
                categories c ON ge.category_id = c.id
            LEFT JOIN
                notes n ON ge.note_id = n.id
            WHERE
                ge.fund_id = ?
            ORDER BY
                ge.expense_date DESC, ge.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    $stmt->close();
    return $expenses;
}

/**
 * Calcola i saldi di tutti i membri di un gruppo.
 */
function get_group_balances($conn, $fund_id) {
    $balances = [];
    $sql = "SELECT
                m.user_id,
                u.username,
                COALESCE((SELECT SUM(amount) FROM group_expenses WHERE paid_by_user_id = m.user_id AND fund_id = m.fund_id), 0) as total_paid,
                COALESCE((SELECT SUM(es.amount_owed) FROM expense_splits es JOIN group_expenses ge ON es.expense_id = ge.id WHERE es.user_id = m.user_id AND ge.fund_id = m.fund_id), 0) as total_owed
            FROM
                shared_fund_members m
            JOIN
                users u ON m.user_id = u.id
            WHERE
                m.fund_id = ?
            GROUP BY
                m.user_id, u.username";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['balance'] = $row['total_paid'] - $row['total_owed'];
        $balances[] = $row;
    }

    $stmt->close();
    return $balances;
}

/**
 * Ottiene il saldo di cassa di un fondo (somma dei contributi).
 */
function get_fund_cash_balance($conn, $fund_id) {
    $sql = "SELECT SUM(amount) as total_contributed FROM shared_fund_contributions WHERE fund_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total_contributed'] ?? 0;
}

// =============================================================================
// FUNZIONI TRANSAZIONI RICORRENTI
// =============================================================================

/**
 * Ottiene tutte le transazioni ricorrenti di un utente.
 */
function get_recurring_transactions($conn, $user_id) {
    $transactions = [];
    $sql = "SELECT * FROM recurring_transactions WHERE user_id = ? ORDER BY next_due_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
    return $transactions;
}

/**
 * Ottiene una singola transazione ricorrente tramite ID.
 */
function get_recurring_transaction_by_id($conn, $recurring_id, $user_id) {
    $sql = "SELECT * FROM recurring_transactions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recurring_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    return $transaction;
}

/**
 * Controlla e processa le transazioni ricorrenti scadute.
 */
function check_and_process_recurring_transactions($conn, $user_id) {
    $today = date('Y-m-d');
    $sql = "SELECT * FROM recurring_transactions WHERE user_id = ? AND next_due_date <= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($recurring = $result->fetch_assoc()) {
        // Inserisci la nuova transazione nello storico
        $amount = $recurring['type'] == 'expense' ? -abs($recurring['amount']) : abs($recurring['amount']);
        $sql_insert = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiidsss", $user_id, $recurring['account_id'], $recurring['category_id'], $amount, $recurring['type'], $recurring['description'], $recurring['next_due_date']);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Calcola la prossima data di scadenza
        $next_date = new DateTime($recurring['next_due_date']);
        switch ($recurring['frequency']) {
            case 'weekly':
                $next_date->modify('+1 week');
                break;
            case 'bimonthly':
                $next_date->modify('+2 months');
                break;
            case 'monthly':
                $next_date->modify('+1 month');
                break;
            case 'yearly':
                $next_date->modify('+1 year');
                break;
        }
        $new_next_due_date = $next_date->format('Y-m-d');

        // Aggiorna la transazione ricorrente con la nuova data
        $sql_update = "UPDATE recurring_transactions SET next_due_date = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_next_due_date, $recurring['id']);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();
}

// =============================================================================
// FUNZIONI CONTI E SALDI
// =============================================================================

/**
 * Calcola il saldo totale di tutti i conti di un utente.
 */
function get_total_balance($conn, $user_id) {
    $total = 0;
    $sql_initial = "SELECT SUM(initial_balance) as total_initial FROM accounts WHERE user_id = ?";
    $stmt_initial = $conn->prepare($sql_initial);
    $stmt_initial->bind_param('i', $user_id);
    $stmt_initial->execute();
    $result_initial = $stmt_initial->get_result();
    if($row = $result_initial->fetch_assoc()) {
        $total += $row['total_initial'] ?? 0;
    }
    $stmt_initial->close();

    $sql_transactions = "SELECT SUM(amount) as total_transactions FROM transactions WHERE user_id = ?";
    $stmt_transactions = $conn->prepare($sql_transactions);
    $stmt_transactions->bind_param('i', $user_id);
    $stmt_transactions->execute();
    $result_transactions = $stmt_transactions->get_result();
    if($row = $result_transactions->fetch_assoc()) {
        $total += $row['total_transactions'] ?? 0;
    }
    $stmt_transactions->close();

    return $total;
}

/**
 * Calcola il saldo di un singolo conto.
 */
function get_account_balance($conn, $account_id) {
    $balance = 0;
    $sql_initial = "SELECT initial_balance FROM accounts WHERE id = ?";
    $stmt_initial = $conn->prepare($sql_initial);
    $stmt_initial->bind_param('i', $account_id);
    $stmt_initial->execute();
    $result_initial = $stmt_initial->get_result();
    if ($row = $result_initial->fetch_assoc()) {
        $balance = $row['initial_balance'];
    }
    $stmt_initial->close();

    $sql_tx = "SELECT SUM(amount) as total_tx FROM transactions WHERE account_id = ?";
    $stmt_tx = $conn->prepare($sql_tx);
    $stmt_tx->bind_param('i', $account_id);
    $stmt_tx->execute();
    $result_tx = $stmt_tx->get_result();
    if ($row = $result_tx->fetch_assoc()) {
        $balance += $row['total_tx'] ?? 0;
    }
    $stmt_tx->close();

    return $balance;
}

/**
 * Ottiene tutti i conti di un utente.
 */
function get_user_accounts($conn, $user_id) {
    $accounts = [];
    $sql = "SELECT id, name FROM accounts WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    $stmt->close();
    return $accounts;
}

/**
 * Ottiene un singolo conto tramite ID.
 */
function get_account_by_id($conn, $account_id, $user_id) {
    $sql = "SELECT * FROM accounts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    $stmt->close();
    return $account;
}

// =============================================================================
// FUNZIONI CATEGORIE E BUDGET
// =============================================================================

/**
 * Ottiene le categorie di un utente, ordinate correttamente.
 */
function get_user_categories($conn, $user_id, $type) {
    $categories = [];
    $sql = "SELECT id, name, icon FROM categories WHERE user_id = ? AND type = ? ORDER BY category_order ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
    return $categories;
}

/**
 * Ottiene una singola categoria tramite ID.
 */
function get_category_by_id($conn, $category_id, $user_id) {
    $sql = "SELECT * FROM categories WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    return $category;
}

/**
 * Ottiene il prossimo valore di ordinamento disponibile per le categorie.
 */
function get_next_category_order($conn, $user_id) {
    $sql = "SELECT MAX(category_order) as max_order FROM categories WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($result['max_order'] ?? 0) + 1;
}

/**
 * Ottiene tutti i budget di un utente.
 */
function get_user_budgets($conn, $user_id) {
    $budgets = [];
    $first_day_of_month = date('Y-m-01');
    $last_day_of_month = date('Y-m-t');

    $sql = "SELECT b.id, b.amount, c.name as category_name, c.icon, b.category_id
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sql_spent = "SELECT SUM(ABS(amount)) as total_spent
                      FROM transactions
                      WHERE user_id = ? AND category_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ?";

        $stmt_spent = $conn->prepare($sql_spent);
        $stmt_spent->bind_param('iiss', $user_id, $row['category_id'], $first_day_of_month, $last_day_of_month);
        $stmt_spent->execute();
        $spent_result = $stmt_spent->get_result()->fetch_assoc();

        $row['spent'] = $spent_result['total_spent'] ?? 0;
        $budgets[] = $row;
        $stmt_spent->close();
    }
    $stmt->close();
    return $budgets;
}

/**
 * Ottiene le categorie di spesa che non hanno ancora un budget.
 */
function get_spend_categories_without_budget($conn, $user_id) {
    $categories = [];
    $sql = "SELECT c.id, c.name
            FROM categories c
            LEFT JOIN budgets b ON c.id = b.category_id AND b.user_id = ?
            WHERE c.user_id = ? AND c.type = 'expense' AND b.id IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
    return $categories;
}

/**
 * Ottiene un singolo budget tramite ID.
 */
function get_budget_by_id($conn, $budget_id, $user_id) {
    $sql = "SELECT b.id, b.amount, b.category_id, c.name as category_name
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.id = ? AND b.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $budget_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    return $budget;
}

/**
 * Ottiene una categoria tramite il nome.
 */
function get_category_by_name_and_type($conn, $name, $user_id, $type) {
    $sql = "SELECT * FROM categories WHERE name = ? AND user_id = ? AND type = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $name, $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    return $category;
}

// =============================================================================
// FUNZIONI OBIETTIVI DI RISPARMIO
// =============================================================================

/**
 * Ottiene tutti gli obiettivi di risparmio di un utente.
 */
function get_saving_goals($conn, $user_id) {
    $goals = [];
    $sql = "SELECT * FROM saving_goals WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $goals[] = $row;
    }
    $stmt->close();
    return $goals;
}

/**
 * Ottiene un singolo obiettivo tramite ID.
 */
function get_goal_by_id($conn, $goal_id, $user_id) {
    $sql = "SELECT * FROM saving_goals WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $goal_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $goal = $result->fetch_assoc();
    $stmt->close();
    return $goal;
}

// =============================================================================
// FUNZIONI REPORT E TRANSAZIONI
// =============================================================================

/**
 * Ottiene il riepilogo mensile di entrate e uscite.
 */
function get_monthly_summary($conn, $user_id) {
    $summary = ['income' => 0, 'expenses' => 0];
    $first_day_of_month = date('Y-m-01');
    $last_day_of_month = date('Y-m-t');

    $sql = "SELECT type, SUM(amount) as total FROM transactions
            WHERE user_id = ? AND transaction_date BETWEEN ? AND ?
            GROUP BY type";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $first_day_of_month, $last_day_of_month);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['type'] == 'income') {
            $summary['income'] = $row['total'];
        } elseif ($row['type'] == 'expense') {
            $summary['expenses'] = $row['total'];
        }
    }
    $stmt->close();
    return $summary;
}

/**
 * Ottiene le transazioni recenti.
 */
function get_recent_transactions($conn, $user_id, $limit = 5) {
    $transactions = [];
    $sql = "SELECT t.description, t.amount, t.transaction_date, c.name as category_name, c.icon
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ?
            ORDER BY t.transaction_date DESC, t.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
    return $transactions;
}

/**
 * Ottiene tutte le transazioni di un utente.
 */
function get_all_transactions($conn, $user_id, $filters = []) {
    $transactions = [];
    // CORREZIONE: Aggiunti t.account_id, t.category_id, t.invoice_path alla SELECT
    $sql = "SELECT
                t.id, t.description, t.amount, t.transaction_date, t.invoice_path,
                t.account_id, t.category_id,
                c.name as category_name, a.name as account_name,
                GROUP_CONCAT(tags.name SEPARATOR ', ') as tags
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN transaction_tags tt ON t.id = tt.transaction_id
            LEFT JOIN tags ON tt.tag_id = tags.id
            WHERE t.user_id = ?";

    $params = ['i', $user_id];

    if (!empty($filters['start_date'])) {
        $sql .= " AND t.transaction_date >= ?";
        $params[0] .= 's';
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND t.transaction_date <= ?";
        $params[0] .= 's';
        $params[] = $filters['end_date'];
    }
    if (!empty($filters['description'])) {
        $sql .= " AND t.description LIKE ?";
        $params[0] .= 's';
        $params[] = '%' . $filters['description'] . '%';
    }
    if (!empty($filters['category_id'])) {
        $sql .= " AND t.category_id = ?";
        $params[0] .= 'i';
        $params[] = $filters['category_id'];
    }
    if (!empty($filters['account_id'])) {
        $sql .= " AND t.account_id = ?";
        $params[0] .= 'i';
        $params[] = $filters['account_id'];
    }

    if (!empty($filters['tag_id'])) {
        $sql .= " AND t.id IN (SELECT transaction_id FROM transaction_tags WHERE tag_id = ?)";
        $params[0] .= 'i';
        $params[] = $filters['tag_id'];
    }

    $sql .= " GROUP BY t.id ORDER BY t.transaction_date DESC, t.created_at DESC";

    $stmt = $conn->prepare($sql);

    if (count($params) > 1) {
        $stmt->bind_param(...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
    return $transactions;
}


/**
 * Ottiene una singola transazione tramite ID.
 */
function get_transaction_by_id($conn, $transaction_id, $user_id) {
    $sql = "SELECT * FROM transactions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    return $transaction;
}

/**
 * Raggruppa le spese per categoria per il grafico a torta.
 */
function get_expenses_by_category($conn, $user_id, $filters = []) {
    $data = ['labels' => [], 'values' => []];

    $sql = "SELECT c.name as category_name, SUM(ABS(t.amount)) as total
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense'";

    $params = ['i', $user_id];

    if (!empty($filters['start_date'])) {
        $sql .= " AND t.transaction_date >= ?";
        $params[0] .= 's';
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND t.transaction_date <= ?";
        $params[0] .= 's';
        $params[] = $filters['end_date'];
    }
    if (!empty($filters['account_ids']) && is_array($filters['account_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['account_ids']), '?'));
        $sql .= " AND t.account_id IN ($placeholders)";
        $params[0] .= str_repeat('i', count($filters['account_ids']));
        foreach ($filters['account_ids'] as $acc_id) {
            $params[] = $acc_id;
        }
    }

    $sql .= " GROUP BY c.name ORDER BY total DESC";

    $stmt = $conn->prepare($sql);
    if (count($params) > 1) {
        $stmt->bind_param(...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['category_name'];
        $data['values'][] = $row['total'];
    }
    $stmt->close();
    return $data;
}

/**
 * Ottiene i dati per il grafico dell'andamento di entrate e uscite.
 */
function get_income_expense_trend($conn, $user_id, $filters = []) {
    $start_date_str = $filters['start_date'] ?? date('Y-m-d', strtotime('-5 months'));
    $end_date_str = $filters['end_date'] ?? date('Y-m-d');

    $start_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);
    $start_date->modify('first day of this month');
    $end_date->modify('first day of this month');
    $interval = new DateInterval('P1M');
    $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

    $data = ['labels' => [], 'income' => [], 'expenses' => []];

    foreach ($period as $dt) {
        $month_label = $dt->format('M Y');
        $month_start = $dt->format('Y-m-01');
        $month_end = $dt->format('Y-m-t');

        $data['labels'][] = $month_label;
        $income_for_month = 0;
        $expense_for_month = 0;

        $sql = "SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?";
        $params = ['iss', $user_id, $month_start, $month_end];

        if (!empty($filters['account_ids']) && is_array($filters['account_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['account_ids']), '?'));
            $sql .= " AND account_id IN ($placeholders)";
            $params[0] .= str_repeat('i', count($filters['account_ids']));
            foreach ($filters['account_ids'] as $acc_id) {
                $params[] = $acc_id;
            }
        }
        $sql .= " GROUP BY type";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['type'] == 'income') {
                $income_for_month = $row['total'];
            } elseif ($row['type'] == 'expense') {
                $expense_for_month = abs($row['total']);
            }
        }
        $stmt->close();

        $data['income'][] = $income_for_month;
        $data['expenses'][] = $expense_for_month;
    }
    return $data;
}

function get_upcoming_recurring_expenses_sum($conn, $user_id) {
    $total = 0;
    $today = date('Y-m-d');
    $in_30_days = date('Y-m-d', strtotime('+30 days'));

    $sql = "SELECT SUM(amount) as total_upcoming
            FROM recurring_transactions
            WHERE user_id = ?
            AND type = 'expense'
            AND next_due_date BETWEEN ? AND ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $today, $in_30_days);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total = $row['total_upcoming'] ?? 0;
    }
    $stmt->close();
    return $total;
}

function get_category_by_name_for_user($conn, $user_id, $category_name) {
    $sql = "SELECT id, type FROM categories WHERE user_id = ? AND name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    // Trim per rimuovere eventuali spazi bianchi dal nome della categoria nel CSV
    $trimmed_name = trim($category_name);
    $stmt->bind_param("is", $user_id, $trimmed_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    return $category;
}


/**
 * Ottiene i dati per il grafico dell'andamento del patrimonio netto.
 */
function get_net_worth_trend($conn, $user_id, $filters = []) {
    $start_date_str = $filters['start_date'] ?? date('Y-m-d', strtotime('-5 months'));
    $end_date_str = $filters['end_date'] ?? date('Y-m-d');

    $start_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);
    $start_date->modify('first day of this month');
    $end_date->modify('first day of this month');
    $interval = new DateInterval('P1M');
    $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

    $data = ['labels' => [], 'values' => []];

    // Calcola il patrimonio netto all'inizio del periodo di tempo selezionato
    $initial_net_worth = 0;
    $account_ids_filter = $filters['account_ids'] ?? [];

    // 1. Saldo iniziale dei conti
    $sql_initial = "SELECT SUM(initial_balance) as total_initial FROM accounts WHERE user_id = ?";
    $params_initial = ['i', $user_id];
    if (!empty($account_ids_filter)) {
        $placeholders = implode(',', array_fill(0, count($account_ids_filter), '?'));
        $sql_initial .= " AND id IN ($placeholders)";
        $params_initial[0] .= str_repeat('i', count($account_ids_filter));
        foreach ($account_ids_filter as $acc_id) $params_initial[] = $acc_id;
    }
    $stmt_initial = $conn->prepare($sql_initial);
    $stmt_initial->bind_param(...$params_initial);
    $stmt_initial->execute();
    $initial_net_worth += $stmt_initial->get_result()->fetch_assoc()['total_initial'] ?? 0;
    $stmt_initial->close();

    // 2. Somma delle transazioni prima della data di inizio
    $sql_past_tx = "SELECT SUM(amount) as total_past_tx FROM transactions WHERE user_id = ? AND transaction_date < ?";
    $params_past = ['is', $user_id, $start_date->format('Y-m-01')];
    if (!empty($account_ids_filter)) {
        $placeholders = implode(',', array_fill(0, count($account_ids_filter), '?'));
        $sql_past_tx .= " AND account_id IN ($placeholders)";
        $params_past[0] .= str_repeat('i', count($account_ids_filter));
        foreach ($account_ids_filter as $acc_id) $params_past[] = $acc_id;
    }
    $stmt_past_tx = $conn->prepare($sql_past_tx);
    $stmt_past_tx->bind_param(...$params_past);
    $stmt_past_tx->execute();
    $initial_net_worth += $stmt_past_tx->get_result()->fetch_assoc()['total_past_tx'] ?? 0;
    $stmt_past_tx->close();

    $current_net_worth = $initial_net_worth;

    // Itera per ogni mese nel periodo per calcolare il patrimonio netto alla fine di quel mese
    foreach ($period as $dt) {
        $month_label = $dt->format('M Y');
        $month_start = $dt->format('Y-m-01');
        $month_end = $dt->format('Y-m-t');
        $data['labels'][] = $month_label;

        $sql_month_tx = "SELECT SUM(amount) as total_month_tx FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?";
        $params_month = ['iss', $user_id, $month_start, $month_end];
        if (!empty($account_ids_filter)) {
            $placeholders = implode(',', array_fill(0, count($account_ids_filter), '?'));
            $sql_month_tx .= " AND account_id IN ($placeholders)";
            $params_month[0] .= str_repeat('i', count($account_ids_filter));
            foreach ($account_ids_filter as $acc_id) $params_month[] = $acc_id;
        }
        $stmt_month_tx = $conn->prepare($sql_month_tx);
        $stmt_month_tx->bind_param(...$params_month);
        $stmt_month_tx->execute();
        $current_net_worth += $stmt_month_tx->get_result()->fetch_assoc()['total_month_tx'] ?? 0;
        $stmt_month_tx->close();

        $data['values'][] = $current_net_worth;
    }
    return $data;
}

// =============================================================================
// FUNZIONI DI SALDACONTO (SETTLEMENT)
// =============================================================================

/**
 * Semplifica i debiti di un gruppo per minimizzare il numero di transazioni.
 * @param array $balances Un array di saldi, come restituito da get_group_balances.
 * @return array Una lista di pagamenti, ognuno con 'from', 'to', e 'amount'.
 */
function simplify_debts($balances) {
    $debtors = [];
    $creditors = [];

    // Separa debitori e creditori
    foreach ($balances as $b) {
        if ($b['balance'] < 0) {
            $debtors[] = ['user_id' => $b['user_id'], 'amount' => abs($b['balance'])];
        } elseif ($b['balance'] > 0) {
            $creditors[] = ['user_id' => $b['user_id'], 'amount' => $b['balance']];
        }
    }

    $payments = [];
    $debtor_idx = 0;
    $creditor_idx = 0;

    // Accoppia debitori e creditori
    while ($debtor_idx < count($debtors) && $creditor_idx < count($creditors)) {
        $debtor = &$debtors[$debtor_idx];
        $creditor = &$creditors[$creditor_idx];

        $payment_amount = min($debtor['amount'], $creditor['amount']);

        // Arrotonda a 2 cifre decimali per evitare problemi di precisione
        $payment_amount = round($payment_amount, 2);

        if ($payment_amount > 0) {
            $payments[] = [
                'from' => $debtor['user_id'],
                'to' => $creditor['user_id'],
                'amount' => $payment_amount
            ];

            $debtor['amount'] -= $payment_amount;
            $creditor['amount'] -= $payment_amount;
        }

        // Passa al prossimo debitore/creditore se il loro saldo è stato azzerato
        if ($debtor['amount'] < 0.01) {
            $debtor_idx++;
        }

        if ($creditor['amount'] < 0.01) {
            $creditor_idx++;
        }
    }

    return $payments;
}

/**
 * Ottiene i pagamenti di un saldaconto.
 */
function get_settlement_payments($conn, $fund_id) {
    $payments = [];
    $sql = "SELECT
                sp.*,
                from_user.username as from_username,
                to_user.username as to_username
            FROM settlement_payments sp
            JOIN users from_user ON sp.from_user_id = from_user.id
            JOIN users to_user ON sp.to_user_id = to_user.id
            WHERE sp.fund_id = ?
            ORDER BY sp.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
    return $payments;
}

/**
 * Calcola il contributo netto di ogni utente a un fondo.
 * @param object $conn La connessione al database.
 * @param int $fund_id L'ID del fondo.
 * @return array Un array associativo [user_id => net_contribution].
 */
function get_net_contributions_by_user($conn, $fund_id) {
    $contributions = [];
    $sql = "SELECT user_id, SUM(amount) as net_contribution
            FROM shared_fund_contributions
            WHERE fund_id = ?
            GROUP BY user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contributions[$row['user_id']] = (float)$row['net_contribution'];
    }
    $stmt->close();
    return $contributions;
}

function get_expenses_by_category_for_fund($conn, $fund_id) {
    $data = [];
    $sql = "SELECT c.name as category_name, SUM(ge.amount) as total_amount
            FROM group_expenses ge
            JOIN categories c ON ge.category_id = c.id
            WHERE ge.fund_id = ?
            GROUP BY ge.category_id
            ORDER BY total_amount DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function get_member_stats_for_fund($conn, $fund_id) {
    $stats = [];
    $sql = "SELECT
                u.id as user_id,
                u.username,
                (SELECT COALESCE(SUM(amount), 0) FROM group_expenses WHERE fund_id = ? AND paid_by_user_id = u.id) as total_paid,
                (SELECT COALESCE(SUM(amount), 0) FROM shared_fund_contributions WHERE fund_id = ? AND user_id = u.id) as total_contributed
            FROM users u
            JOIN shared_fund_members sfm ON u.id = sfm.user_id
            WHERE sfm.fund_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $fund_id, $fund_id, $fund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    $stmt->close();
    return $stats;
}
?>