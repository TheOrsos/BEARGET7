<?php
// File: add_transaction.php (Versione AJAX)
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
    $type = trim($_POST['type']);
    $amount = trim($_POST['amount']);
    $transaction_date = trim($_POST['transaction_date']);
    $description = trim($_POST['description']);
    $account_id = trim($_POST['account_id']);
    $category_id = trim($_POST['category_id']);
    $tags_string = trim($_POST['tags'] ?? '');
    $invoice_path = null;

    if (empty($amount) || !is_numeric($amount) || $amount <= 0 || empty($transaction_date) || empty($description) || empty($account_id) || empty($category_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori non sono stati compilati.']);
        exit();
    }

    $user = get_user_by_id($conn, $user_id);
    $is_pro_user = ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'lifetime');
    
    if ($is_pro_user && isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['invoice_file'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];

        if ($file['size'] <= $max_size && in_array($file['type'], $allowed_types)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = uniqid() . '-' . basename($file['name']);
            $invoice_path = $upload_dir . $file_name;
            if (!move_uploaded_file($file['tmp_name'], $invoice_path)) {
                $invoice_path = null;
            }
        }
    }

    $final_amount = ($type == 'expense') ? -abs($amount) : abs($amount);

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date, invoice_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidssss", $user_id, $account_id, $category_id, $final_amount, $type, $description, $transaction_date, $invoice_path);
        $stmt->execute();
        $new_transaction_id = $stmt->insert_id;

        if ($is_pro_user && !empty($tags_string)) {
            process_and_link_tags($conn, $user_id, $new_transaction_id, $tags_string);
        }
        
        $stmt->close();
        $conn->commit();

        $full_transaction_data = get_transaction_details_for_ui($conn, $new_transaction_id, $user_id);

        echo json_encode(['success' => true, 'message' => 'Transazione aggiunta!', 'transaction' => $full_transaction_data]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio.']);
    }

    $conn->close();
    exit();
}
?>