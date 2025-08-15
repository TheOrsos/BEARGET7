<?php
// File: update_recurring.php (NUOVO FILE - Versione AJAX)
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
    $recurring_id = trim($_POST['recurring_id']);
    $description = trim($_POST['description']);
    $amount = trim($_POST['amount']);
    $category_id = trim($_POST['category_id']);
    $account_id = trim($_POST['account_id']);
    $frequency = trim($_POST['frequency']);
    $next_due_date = trim($_POST['next_due_date']);

    // Validazione
    if (empty($recurring_id) || empty($description) || !is_numeric($amount) || $amount <= 0 || empty($category_id) || empty($account_id) || empty($frequency) || empty($next_due_date)) {
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

    $sql = "UPDATE recurring_transactions SET description = ?, amount = ?, type = ?, category_id = ?, account_id = ?, frequency = ?, next_due_date = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdsiisssi", $description, $amount, $type, $category_id, $account_id, $frequency, $next_due_date, $recurring_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Transazione ricorrente aggiornata!',
                'transaction' => [
                    'id' => $recurring_id,
                    'description' => $description,
                    'amount' => floatval($amount),
                    'type' => $type,
                    'frequency' => $frequency,
                    'next_due_date' => $next_due_date
                ]
            ]);
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