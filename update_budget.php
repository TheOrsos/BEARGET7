<?php
// File: update_budget.php (NUOVO FILE - Versione AJAX)
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
    $budget_id = trim($_POST['budget_id']);
    $amount = trim($_POST['amount']);

    if (empty($budget_id) || !is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "UPDATE budgets SET amount = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("dii", $amount, $budget_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Budget aggiornato!',
                'budget' => [
                    'id' => intval($budget_id),
                    'amount' => floatval($amount)
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