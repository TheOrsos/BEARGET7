<?php
// File: delete_transaction.php (Versione AJAX - Corretta Definitivamente)
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $transaction_id = $_POST['transaction_id'] ?? null;
    // L'utente vuole ripristinare il saldo? 'yes' o 'no'.
    $should_restore_balance = ($_POST['restore_balance'] ?? 'no') === 'yes';

    if (empty($transaction_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID transazione mancante.']);
        exit();
    }

    $conn->begin_transaction();

    try {
        // Passo 1: Ottenere le informazioni sulla transazione principale
        $sql_get_main_tx = "SELECT id, amount, account_id, transfer_group_id, invoice_path FROM transactions WHERE id = ? AND user_id = ?";
        $stmt_get_main = $conn->prepare($sql_get_main_tx);
        $stmt_get_main->bind_param("ii", $transaction_id, $user_id);
        $stmt_get_main->execute();
        $main_transaction = $stmt_get_main->get_result()->fetch_assoc();
        $stmt_get_main->close();

        if (!$main_transaction) {
            throw new Exception("Transazione non trovata o non autorizzata.", 404);
        }

        $transactions_to_delete = [];
        // Passo 2: Se è un trasferimento, trovare tutte le transazioni collegate
        if (!empty($main_transaction['transfer_group_id'])) {
            $sql_get_transfer_group = "SELECT id, amount, account_id, invoice_path FROM transactions WHERE transfer_group_id = ? AND user_id = ?";
            $stmt_get_group = $conn->prepare($sql_get_transfer_group);
            $stmt_get_group->bind_param("si", $main_transaction['transfer_group_id'], $user_id);
            $stmt_get_group->execute();
            $transactions_to_delete = $stmt_get_group->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_get_group->close();
        } else {
            $transactions_to_delete[] = $main_transaction;
        }

        if (empty($transactions_to_delete)) {
             throw new Exception("Nessuna transazione valida da eliminare.", 404);
        }

        // Passo 3: Logica di aggiornamento saldo
        // Se NON dobbiamo ripristinare il saldo, dobbiamo neutralizzare l'effetto della cancellazione.
        // Lo facciamo "assorbendo" l'importo della transazione nel saldo iniziale del conto.
        if ($should_restore_balance === false) {
            $sql_update_balance = "UPDATE accounts SET initial_balance = initial_balance + ? WHERE id = ? AND user_id = ?";
            $stmt_update = $conn->prepare($sql_update_balance);

            foreach ($transactions_to_delete as $tx) {
                if ($tx['amount'] != 0 && !is_null($tx['account_id'])) {
                    $stmt_update->bind_param("dii", $tx['amount'], $tx['account_id'], $user_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Errore durante la neutralizzazione del saldo per il conto ID: " . $tx['account_id']);
                    }
                }
            }
            $stmt_update->close();
        }
        // Se $should_restore_balance è TRUE, non facciamo nulla. La semplice eliminazione
        // della transazione farà sì che il saldo calcolato venga "ripristinato".

        // Passo 4: Eliminare le transazioni e i file associati
        $sql_delete = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        foreach ($transactions_to_delete as $tx) {
            // Elimina la transazione
            $stmt_delete->bind_param("ii", $tx['id'], $user_id);
            if (!$stmt_delete->execute()) {
                throw new Exception("Errore durante l'eliminazione della transazione ID: " . $tx['id']);
            }

            // Elimina il file della fattura
            if (!empty($tx['invoice_path']) && file_exists($tx['invoice_path'])) {
                unlink($tx['invoice_path']);
            }
        }
        $stmt_delete->close();

        // Passo 5: Commit della transazione
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Transazione/i eliminata/e con successo!']);

    } catch (Exception $e) {
        $conn->rollback();
        $http_code = ($e->getCode() > 0) ? $e->getCode() : 500;
        http_response_code($http_code);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
    exit();
}
?>
