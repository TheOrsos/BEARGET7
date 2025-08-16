<?php
// File: delete_transaction.php (Versione AJAX aggiornata)
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
    $restore_balance = isset($_POST['restore_balance']) && $_POST['restore_balance'] === '1';

    if (empty($transaction_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID transazione mancante.']);
        exit();
    }

    $conn->begin_transaction();

    try {
        // Passo 1: Ottenere le informazioni sulla transazione/i da eliminare
        $sql_get_transactions = "
            SELECT id, amount, account_id, transfer_group_id, invoice_path
            FROM transactions
            WHERE user_id = ? AND (
                id = ? OR
                (
                    transfer_group_id IS NOT NULL AND
                    transfer_group_id = (SELECT transfer_group_id FROM transactions WHERE id = ? AND user_id = ?)
                )
            )
        ";
        $stmt_get = $conn->prepare($sql_get_transactions);
        $stmt_get->bind_param("iiii", $user_id, $transaction_id, $transaction_id, $user_id);
        $stmt_get->execute();
        $transactions_to_delete = $stmt_get->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_get->close();

        if (empty($transactions_to_delete)) {
            throw new Exception("Transazione non trovata o non autorizzata.", 404);
        }

        // Passo 2: Se richiesto, ripristinare il saldo per ogni transazione
        if ($restore_balance) {
            $sql_update_balance = "UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?";
            $stmt_update = $conn->prepare($sql_update_balance);

            foreach ($transactions_to_delete as $tx) {
                if ($tx['amount'] != 0 && !is_null($tx['account_id'])) {
                    $stmt_update->bind_param("dii", $tx['amount'], $tx['account_id'], $user_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Errore durante l'aggiornamento del saldo per il conto ID: " . $tx['account_id']);
                    }
                }
            }
            $stmt_update->close();
        }

        // Passo 3: Eliminare le transazioni e i file associati
        $sql_delete = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        foreach ($transactions_to_delete as $tx) {
            // Delete transaction
            $stmt_delete->bind_param("ii", $tx['id'], $user_id);
            if (!$stmt_delete->execute()) {
                throw new Exception("Errore durante l'eliminazione della transazione ID: " . $tx['id']);
            }

            // Delete invoice file
            if (!empty($tx['invoice_path']) && file_exists($tx['invoice_path'])) {
                unlink($tx['invoice_path']);
            }
        }
        $stmt_delete->close();

        // Passo 4: Commit della transazione
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
