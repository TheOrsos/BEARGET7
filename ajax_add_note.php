<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php'; // Required for get_note_by_id

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

$user_id = $_SESSION["id"];
$creator_id = $user_id; // The creator is the current user
$title = "Nuova Nota";
$content = "";
$todolist_content = "[]";

// The user_id column is now redundant but we fill it for backward compatibility.
// The creator_id is the source of truth for ownership.
$sql = "INSERT INTO notes (user_id, creator_id, title, content, todolist_content) VALUES (?, ?, ?, ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iisss", $user_id, $creator_id, $title, $content, $todolist_content);
    if ($stmt->execute()) {
        $new_note_id = $conn->insert_id;
        // Fetch the newly created note to ensure all fields are correct
        $new_note = get_note_by_id($conn, $new_note_id, $user_id);
        echo json_encode(['success' => true, 'note' => $new_note]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore del database durante la creazione della nota.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore nella preparazione della query.']);
}

$conn->close();
exit();
?>