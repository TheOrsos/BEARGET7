<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato.']);
    exit;
}

require_once 'db_connect.php';
require_once 'functions.php';

$current_user_id = $_SESSION["id"];
$note_id = $_POST['note_id'] ?? 0;
$member_id = $_POST['member_id'] ?? 0;

if (empty($note_id) || empty($member_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dati mancanti.']);
    exit;
}

$result = remove_note_share($conn, $note_id, $member_id, $current_user_id);

echo json_encode($result);

if (isset($conn)) {
    $conn->close();
}
?>