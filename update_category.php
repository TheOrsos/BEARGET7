<?php
// File: update_category.php (NUOVO FILE - Versione AJAX)
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
    $category_id = trim($_POST['category_id']);
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);

    if (empty($category_id) || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "UPDATE categories SET name = ?, icon = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssii", $name, $icon, $category_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Categoria aggiornata!',
                'category' => [
                    'id' => intval($category_id),
                    'name' => $name,
                    'icon' => $icon
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