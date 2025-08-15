<?php
// File: delete_recurring.php (Versione AJAX)
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
    $recurring_id = $_POST['recurring_id'];

    if (empty($recurring_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID mancante.']);
        exit();
    }

    $sql = "DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $recurring_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Transazione ricorrente eliminata.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>