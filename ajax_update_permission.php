<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'functions.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato.']);
    exit;
}

$user_id = $_SESSION["id"];
$note_id = $_POST['note_id'] ?? 0;
$member_id = $_POST['member_id'] ?? 0;
$permission = $_POST['permission'] ?? '';

if (empty($note_id) || empty($member_id) || empty($permission)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dati mancanti.']);
    exit;
}

$result = update_note_permission($conn, $note_id, $member_id, $permission, $user_id);

echo json_encode($result);
$conn->close();
?>