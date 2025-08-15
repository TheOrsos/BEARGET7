<?php
// File: add_recurring.php (Versione AJAX)
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
    $description = trim($_POST['description']);
    $amount = trim($_POST['amount']);
    $category_id = trim($_POST['category_id']);
    $account_id = trim($_POST['account_id']);
    $frequency = trim($_POST['frequency']);
    $start_date = trim($_POST['start_date']);

    // Validazione
    if (empty($description) || !is_numeric($amount) || $amount <= 0 || empty($category_id) || empty($account_id) || empty($frequency) || empty($start_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori.']);
        exit();
    }

    $cat_type_sql = "SELECT type FROM categories WHERE id = ? LIMIT 1";
    $stmt_cat = $conn->prepare($cat_type_sql);
    $stmt_cat->bind_param("i", $category_id);
    $stmt_cat->execute();
    $type = $stmt_cat->get_result()->fetch_assoc()['type'];
    $stmt_cat->close();

    $sql = "INSERT INTO recurring_transactions (user_id, description, amount, type, category_id, account_id, frequency, start_date, next_due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isdsiisss", $user_id, $description, $amount, $type, $category_id, $account_id, $frequency, $start_date, $start_date);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Transazione ricorrente aggiunta!',
                'transaction' => [
                    'id' => $new_id,
                    'description' => $description,
                    'amount' => floatval($amount),
                    'type' => $type,
                    'category_id' => $category_id,
                    'account_id' => $account_id,
                    'frequency' => $frequency,
                    'next_due_date' => $start_date
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>