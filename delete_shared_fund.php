<?php
// File: delete_shared_fund.php (NUOVO FILE - Versione AJAX)
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
    $fund_id = $_POST['fund_id'];

    if (empty($fund_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID fondo mancante.']);
        exit();
    }

    $sql = "DELETE FROM shared_funds WHERE id = ? AND creator_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $fund_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Fondo eliminato con successo!']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare il fondo o non sei il creatore.']);
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