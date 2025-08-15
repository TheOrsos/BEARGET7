<?php
// File: add_note.php (Versione AJAX)
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

$user_id = $_SESSION["id"];
$title = "Nuova Nota";
$content = "";
$todolist_content = "[]"; // Inizia con una to-do list vuota

$sql = "INSERT INTO notes (user_id, creator_id, title, content, todolist_content) VALUES (?, ?, ?, ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    // Quando una nota viene creata, l'utente è anche il creatore.
    $stmt->bind_param("iisss", $user_id, $user_id, $title, $content, $todolist_content);
    if ($stmt->execute()) {
        $new_note_id = $conn->insert_id;
        // Recupera la nota completa per restituirla, così tutti i campi (es. creator_id) sono disponibili nel frontend
        $sql_get = "SELECT * FROM notes WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param('i', $new_note_id);
        $stmt_get->execute();
        $new_note = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        echo json_encode([
            'success' => true,
            'message' => 'Nota creata!',
            'note' => $new_note
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante la creazione della nota.']);
    }
    $stmt->close();
}
$conn->close();
exit();
?>