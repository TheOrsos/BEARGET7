<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'functions.php';

// Imposta un gestore di errori per catturare anche i warning/notice
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Utente non autenticato.']);
        exit;
    }

    $user_id = $_SESSION["id"];
    $note_id = $_GET['note_id'] ?? 0;

    if (empty($note_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID nota mancante.']);
        exit;
    }

    $members = get_note_members($conn, $note_id, $user_id);

    echo json_encode(['success' => true, 'members' => $members]);

} catch (Throwable $e) {
    // Cattura qualsiasi errore o eccezione
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Si è verificato un errore interno del server.',
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>