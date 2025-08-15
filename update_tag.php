<?php
// File: update_tag.php (Versione AJAX)
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
    $new_name = trim($_POST['name']);
    $new_name = preg_replace('/[^a-zA-Z0-9]/', '', $new_name); 

    if (empty($tag_id) || empty($new_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati mancanti.']);
        exit();
    }

    // Controlla duplicati
    $sql_check = "SELECT id FROM tags WHERE user_id = ? AND name = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("isi", $user_id, $new_name, $tag_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Un\'etichetta con questo nome esiste già.']);
        exit();
    }
    $stmt_check->close();

    // Aggiorna
    $sql_update = "UPDATE tags SET name = ? WHERE id = ? AND user_id = ?";
    if ($stmt_update = $conn->prepare($sql_update)) {
        $stmt_update->bind_param("sii", $new_name, $tag_id, $user_id);
        if ($stmt_update->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Etichetta aggiornata!',
                'tag' => ['id' => $tag_id, 'name' => $new_name]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento.']);
        }
        $stmt_update->close();
    }
    $conn->close();
    exit();
}
?>
