<?php
// File: delete_category.php (Versione AJAX)
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
    $category_id = $_POST['category_id'];

    if (empty($category_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID categoria mancante.']);
        exit();
    }

    $sql = "DELETE FROM categories WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $category_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Categoria eliminata.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare la categoria.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>