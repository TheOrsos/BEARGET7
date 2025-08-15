<?php
// File: add_transfer.php (Versione AJAX)
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { 
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $from_account_id = trim($_POST['from_account_id']);
    $to_account_id = trim($_POST['to_account_id']);
    $amount = trim($_POST['amount']);
    $description = trim($_POST['description']);
    $transaction_date = trim($_POST['transaction_date']);

    if ($from_account_id == $to_account_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'I conti non possono essere uguali.']);
        exit();
    }
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Importo non valido.']);
        exit();
    }

    $transfer_group_id = uniqid('transfer_', true);
    $category = get_category_by_name($conn, 'Trasferimento', $user_id);
    $category_id = $category['id'] ?? null;

    if (!$category_id) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Categoria "Trasferimento" non trovata.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $sql_out = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date, transfer_group_id) VALUES (?, ?, ?, ?, 'expense', ?, ?, ?)";
        $stmt_out = $conn->prepare($sql_out);
        $negative_amount = -abs($amount);
        $stmt_out->bind_param("iiidsss", $user_id, $from_account_id, $category_id, $negative_amount, $description, $transaction_date, $transfer_group_id);
        $stmt_out->execute();
        $stmt_out->close();

        $sql_in = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date, transfer_group_id) VALUES (?, ?, ?, ?, 'income', ?, ?, ?)";
        $stmt_in = $conn->prepare($sql_in);
        $positive_amount = abs($amount);
        $stmt_in->bind_param("iiidsss", $user_id, $to_account_id, $category_id, $positive_amount, $description, $transaction_date, $transfer_group_id);
        $stmt_in->execute();
        $stmt_in->close();

        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Trasferimento registrato!']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante il trasferimento.']);
    }
    $conn->close();
    exit();
}
?>