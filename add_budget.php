<?php
// File: add_budget.php (Versione AJAX)
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
    $category_id = trim($_POST['category_id']);
    $amount = trim($_POST['amount']);

    if (empty($category_id) || !is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "INSERT INTO budgets (user_id, category_id, amount) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iid", $user_id, $category_id, $amount);
        if ($stmt->execute()) {
            $new_budget_id = $conn->insert_id;
            $category_details = get_category_by_id($conn, $category_id, $user_id);

            echo json_encode([
                'success' => true,
                'message' => 'Budget creato con successo!',
                'budget' => [
                    'id' => $new_budget_id,
                    'amount' => floatval($amount),
                    'category_id' => intval($category_id),
                    'category_name' => $category_details['name'] ?? 'N/A',
                    'icon' => $category_details['icon'] ?? '💸',
                    'spent' => 0.00
                ]
            ]);
        } else {
            if ($conn->errno == 1062) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Esiste già un budget per questa categoria.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio del budget.']);
            }
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>