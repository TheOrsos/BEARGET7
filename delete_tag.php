<?php
// File: delete_tag.php (Versione AJAX)
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
    $tag_id = $_POST['tag_id'];

    if (empty($tag_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID etichetta mancante.']);
        exit();
    }

    $sql = "DELETE FROM tags WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $tag_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Etichetta eliminata con successo.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare l\'etichetta o permesso negato.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>