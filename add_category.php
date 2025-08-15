<?php
// File: add_category.php (Versione AJAX)
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
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $type = trim($_POST['type']);

    if (empty($name) || !in_array($type, ['expense', 'income'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $next_order = get_next_category_order($conn, $user_id);
        $sql = "INSERT INTO categories (user_id, name, icon, type, category_order) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $user_id, $name, $icon, $type, $next_order);
        $stmt->execute();
        
        $new_category_id = $conn->insert_id;
        $stmt->close();
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Categoria aggiunta!',
            'category' => [
                'id' => $new_category_id,
                'name' => $name,
                'icon' => $icon,
                'type' => $type
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio.']);
    }
    $conn->close();
    exit();
}
?>