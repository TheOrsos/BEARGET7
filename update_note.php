<?php
// File: update_note.php (Versione AJAX)
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
    $note_id = trim($_POST['note_id']);
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $todolist_content = $_POST['todolist_content'];

    if (empty($note_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID nota mancante.']);
        exit();
    }
    if (empty($title)) {
        $title = "Senza Titolo";
    }

    // --- CONTROLLO DI SICUREZZA MIGLIORATO ---
    // Verifica che l'utente sia il creatore o abbia il permesso di 'modifica'
    $sql_auth = "SELECT n.id FROM notes n LEFT JOIN note_shares ns ON n.id = ns.note_id
                 WHERE n.id = ? AND (n.creator_id = ? OR (ns.user_id = ? AND ns.permission = ?))";
    $stmt_auth = $conn->prepare($sql_auth);
    $edit_permission = 'edit';
    $stmt_auth->bind_param("iiis", $note_id, $user_id, $user_id, $edit_permission);
    $stmt_auth->execute();
    $stmt_auth->store_result();

    if ($stmt_auth->num_rows == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questa nota.']);
        $stmt_auth->close();
        $conn->close();
        exit();
    }
    $stmt_auth->close();
    // --- FINE CONTROLLO DI SICUREZZA ---


    $sql = "UPDATE notes SET title = ?, content = ?, todolist_content = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssi", $title, $content, $todolist_content, $note_id);
        if ($stmt->execute()) {
            // Dopo l'aggiornamento, recupera i dati completi della nota per la risposta
            $updated_note = get_note_by_id($conn, $note_id, $user_id);

            echo json_encode([
                'success' => true,
                'message' => 'Nota salvata!',
                'note' => $updated_note
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>