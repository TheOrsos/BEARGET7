<?php
// File: delete_transaction.php (Versione AJAX)
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
    $transaction_id = $_POST['transaction_id'];

    if (empty($transaction_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID transazione mancante.']);
        exit();
    }

    $sql_get_info = "SELECT transfer_group_id, invoice_path FROM transactions WHERE id = ? AND user_id = ?";
    $stmt_get = $conn->prepare($sql_get_info);
    $stmt_get->bind_param("ii", $transaction_id, $user_id);
    $stmt_get->execute();
    $transaction = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if ($transaction && !empty($transaction['invoice_path'])) {
        if (file_exists($transaction['invoice_path'])) {
            unlink($transaction['invoice_path']);
        }
    }

    if ($transaction && !empty($transaction['transfer_group_id'])) {
        $sql_delete = "DELETE FROM transactions WHERE transfer_group_id = ? AND user_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("si", $transaction['transfer_group_id'], $user_id);
    } else {
        $sql_delete = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $transaction_id, $user_id);
    }

    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Transazione eliminata con successo!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione.']);
    }

    $stmt_delete->close();
    $conn->close();
    exit();
}
?>