<?php
// File: update_transaction.php (Versione AJAX con Gestione Ricevuta)
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
    $transaction_id = trim($_POST['transaction_id']);
    $amount = trim($_POST['amount']);
    $transaction_date = trim($_POST['transaction_date']);
    $description = trim($_POST['description']);
    $account_id = trim($_POST['account_id']);
    $category_id = trim($_POST['category_id']);
    $tags_string = trim($_POST['tags'] ?? '');
    $delete_invoice = isset($_POST['delete_invoice']);

    // Validazione...
    if (empty($transaction_id) || empty($amount) || !is_numeric($amount) || $amount <= 0 || empty($transaction_date) || empty($description) || empty($account_id) || empty($category_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori.']);
        exit();
    }

    // Recupera la transazione esistente per controllare il vecchio file
    $current_transaction = get_transaction_by_id($conn, $transaction_id, $user_id);
    if (!$current_transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transazione non trovata.']);
        exit();
    }
    $current_invoice_path = $current_transaction['invoice_path'];
    $new_invoice_path = $current_invoice_path;

    $user = get_user_by_id($conn, $user_id);
    $is_pro_user = ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'lifetime');

    // Logica di gestione del file
    if ($is_pro_user) {
        // 1. Se l'utente vuole eliminare il file esistente
        if ($delete_invoice && !empty($current_invoice_path) && file_exists($current_invoice_path)) {
            unlink($current_invoice_path);
            $new_invoice_path = null;
        }

        // 2. Se viene caricato un nuovo file
        if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] == UPLOAD_ERR_OK) {
            // Elimina il vecchio file se esiste, per sostituirlo
            if (!empty($current_invoice_path) && file_exists($current_invoice_path)) {
                unlink($current_invoice_path);
            }
            // Carica il nuovo file
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $file_name = uniqid() . '-' . basename($_FILES['invoice_file']['name']);
            $new_invoice_path = $upload_dir . $file_name;
            if (!move_uploaded_file($_FILES['invoice_file']['tmp_name'], $new_invoice_path)) {
                $new_invoice_path = $current_invoice_path; // Ripristina se l'upload fallisce
            }
        }
    }

    $category_type = get_category_type($conn, $category_id, $user_id);
    $final_amount = ($category_type == 'expense') ? -abs($amount) : abs($amount);

    $sql = "UPDATE transactions SET amount = ?, transaction_date = ?, description = ?, account_id = ?, category_id = ?, type = ?, invoice_path = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("dssiissii", $final_amount, $transaction_date, $description, $account_id, $category_id, $category_type, $new_invoice_path, $transaction_id, $user_id);
        
        if ($stmt->execute()) {
            if ($is_pro_user) {
                process_and_link_tags($conn, $user_id, $transaction_id, $tags_string);
            }
            $full_transaction_data = get_transaction_details_for_ui($conn, $transaction_id, $user_id);
            echo json_encode(['success' => true, 'message' => 'Transazione aggiornata!', 'transaction' => $full_transaction_data]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>