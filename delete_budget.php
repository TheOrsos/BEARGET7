<?php
// File: delete_budget.php (Versione AJAX)
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
    $budget_id = $_POST['budget_id'];

    if (empty($budget_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID budget mancante.']);
        exit();
    }

    $sql = "DELETE FROM budgets WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $budget_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Budget eliminato.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare il budget o permesso negato.']);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore di sistema.']);
    }
    $conn->close();
    exit();
}
?>