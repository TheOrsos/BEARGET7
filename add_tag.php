<?php
// File: add_tag.php (Versione AJAX)
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
    $name = trim($_POST['name']);
    $name = preg_replace('/[^a-zA-Z0-9]/', '', $name); 

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Il nome dell\'etichetta non può essere vuoto.']);
        exit();
    }

    $sql = "INSERT INTO tags (user_id, name) VALUES (?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $name);
        if ($stmt->execute()) {
            $new_tag_id = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Etichetta creata!',
                'tag' => ['id' => $new_tag_id, 'name' => $name]
            ]);
        } else {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'Un\'etichetta con questo nome esiste già.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>