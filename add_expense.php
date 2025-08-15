<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    $fund_id = $_POST['fund_id']; // Get fund_id early for redirects
    $user_id = $_SESSION['id'];

    try {
        // --- DATA RETRIEVAL & DEFAULTS ---
        $description = trim($_POST['description']);
        $amount = (float)$_POST['amount'];
        $expense_date = $_POST['expense_date'];
        $paid_by_user_id = $_POST['paid_by_user_id'];
        $account_id = $_POST['account_id']; // Conto personale di chi ha pagato
        $split_method = $_POST['split_method'] ?? 'equal';
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $note_content = trim($_POST['note_content']);

        // --- VALIDATION (BASIC) ---
        if (empty($fund_id) || empty($description) || $amount <= 0 || empty($expense_date) || empty($paid_by_user_id) || empty($account_id)) {
            throw new Exception("Tutti i campi principali sono obbligatori.");
        }

        // --- SECURITY CHECK ---
        $members = get_fund_members($conn, $fund_id);
        $member_ids = array_column($members, 'id');
        if (!in_array($user_id, $member_ids) || !in_array($paid_by_user_id, $member_ids)) {
            throw new Exception("Accesso non autorizzato o utente pagante non valido.");
        }

        // --- SPLIT LOGIC ---
        $splits = [];
        $total_check = 0; // Per validare la somma finale

        switch ($split_method) {
            case 'equal':
                $split_with_users = $_POST['split_with_users'] ?? [];
                if (empty($split_with_users)) throw new Exception("Seleziona almeno un membro per la divisione equa.");
                $split_count = count($split_with_users);
                $split_amount = round($amount / $split_count, 2);
                $remainder = $amount - ($split_amount * $split_count);

                foreach ($split_with_users as $uid) {
                    $splits[$uid] = $split_amount;
                }
                // Assegna il resto al primo utente per far quadrare i conti
                if ($remainder != 0 && !empty($split_with_users)) {
                    $splits[$split_with_users[0]] += $remainder;
                }
                break;

            case 'fixed':
                $fixed_amounts = $_POST['fixed'] ?? [];
                foreach ($fixed_amounts as $uid => $fixed_amount_str) {
                    $fixed_amount = (float)$fixed_amount_str;
                    if ($fixed_amount > 0) {
                        if (!in_array($uid, $member_ids)) throw new Exception("Utente non valido nella divisione.");
                        $splits[$uid] = $fixed_amount;
                        $total_check += $fixed_amount;
                    }
                }
                if (abs($total_check - $amount) > 0.015) { // Tolleranza per arrotondamenti
                    throw new Exception("La somma degli importi fissi (€" . number_format($total_check, 2) . ") non corrisponde all'importo totale della spesa (€" . number_format($amount, 2) . ").");
                }
                break;

            case 'percentage':
                $percentages = $_POST['percentage'] ?? [];
                $total_percentage = 0;
                foreach ($percentages as $uid => $perc_str) {
                    $perc = (float)$perc_str;
                    if ($perc > 0) {
                       if (!in_array($uid, $member_ids)) throw new Exception("Utente non valido nella divisione.");
                       $splits[$uid] = ($amount * $perc) / 100;
                       $total_percentage += $perc;
                    }
                }
                if (abs($total_percentage - 100) > 0.1) { // Tolleranza
                    throw new Exception("La somma delle percentuali (" . $total_percentage . "%) non è 100%.");
                }
                // Ricalcolo per precisione e per far quadrare i conti
                $total_check = 0;
                foreach($splits as $uid => $val) {
                    $splits[$uid] = round($val, 2);
                    $total_check += $splits[$uid];
                }
                $remainder = $amount - $total_check;
                if ($remainder != 0 && count($splits) > 0) {
                    $first_user_key = array_key_first($splits);
                    $splits[$first_user_key] += $remainder;
                }
                break;

            default:
                throw new Exception("Metodo di divisione non valido.");
        }
        if (empty($splits)) {
            throw new Exception("La spesa deve essere divisa con almeno un membro.");
        }


        // --- DB OPERATIONS ---

        // 1. (Opzionale) Crea la nota associata
        $note_id = null;
        if (!empty($note_content)) {
            $note_id = create_and_share_note_with_fund_members($conn, $note_content, $user_id, $fund_id);
        }

        // 2. Registra la spesa personale sul conto di chi ha pagato
        $fund_details = get_shared_fund_details($conn, $fund_id, $user_id);
        $sql_personal_tx = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, 'expense', ?, ?)";
        $stmt_personal_tx = $conn->prepare($sql_personal_tx);
        $personal_tx_amount = -$amount;
        $personal_tx_desc = "Spesa di gruppo '{$fund_details['name']}': {$description}";
        $stmt_personal_tx->bind_param("iiidss", $paid_by_user_id, $account_id, $category_id, $personal_tx_amount, $personal_tx_desc, $expense_date);
        $stmt_personal_tx->execute();
        $stmt_personal_tx->close();

        // 3. Inserisci la spesa nel log del gruppo
        $sql_expense = "INSERT INTO group_expenses (fund_id, paid_by_user_id, description, amount, expense_date, category_id, note_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_expense = $conn->prepare($sql_expense);
        $stmt_expense->bind_param("iisdsii", $fund_id, $paid_by_user_id, $description, $amount, $expense_date, $category_id, $note_id);
        $stmt_expense->execute();
        $expense_id = $stmt_expense->insert_id;
        $stmt_expense->close();

        // 4. Inserisci le divisioni (chi deve cosa)
        $sql_split = "INSERT INTO expense_splits (expense_id, user_id, amount_owed) VALUES (?, ?, ?)";
        $stmt_split = $conn->prepare($sql_split);
        foreach ($splits as $uid => $amount_owed) {
            $stmt_split->bind_param("iid", $expense_id, $uid, $amount_owed);
            $stmt_split->execute();
        }
        $stmt_split->close();

        // Se tutto è andato bene, conferma la transazione
        $conn->commit();
        header("location: fund_details.php?id=" . $fund_id . "&message=Spesa aggiunta con successo!&type=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("location: fund_details.php?id=" . $fund_id . "&message=Errore: " . urlencode($e->getMessage()) . "&type=error");
    } finally {
        if (isset($conn)) $conn->close();
    }
} else {
    header("location: shared_funds.php");
    exit;
}
?>