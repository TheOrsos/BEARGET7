<?php
/*
================================================================================
File: ajax_note_handler.php
Descrizione: Gestisce le richieste AJAX per creare, leggere, aggiornare ed
             eliminare le note associate alle transazioni.
================================================================================
*/
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Controlla se l'utente è loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION['id']; // Assicurati che la chiave sia corretta
$response = ['success' => false, 'message' => 'Azione non valida.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- Azione: Salva Nota ---
    if ($action === 'save_note' && isset($_POST['transaction_id'], $_POST['note_content'])) {
        $transaction_id = intval($_POST['transaction_id']);
        $note_content = trim($_POST['note_content']);

        // Verifica che la transazione appartenga all'utente
        if (!verify_transaction_owner($conn, $transaction_id, $user_id)) {
            $response['message'] = 'Transazione non trovata o non autorizzata.';
        } else {
            // Controlla se una nota esiste già per questa transazione
            $sql_check = "SELECT id, content FROM notes WHERE transaction_id = ? AND user_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $transaction_id, $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $existing_note = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($existing_note) { // La nota esiste già
                $note_id = $existing_note['id'];
                if (empty($note_content)) {
                    // Contenuto vuoto: elimina la nota
                    $sql_delete = "DELETE FROM notes WHERE id = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->bind_param("i", $note_id);
                    if ($stmt_delete->execute()) {
                        $response = ['success' => true, 'message' => 'Nota eliminata.'];
                    } else {
                        $response['message'] = 'Errore durante l\'eliminazione della nota.';
                    }
                    $stmt_delete->close();
                } else {
                    // Contenuto presente: aggiorna la nota
                    $sql_update = "UPDATE notes SET content = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("si", $note_content, $note_id);
                    if ($stmt_update->execute()) {
                        $response = ['success' => true, 'message' => 'Nota aggiornata.'];
                    } else {
                        $response['message'] = 'Errore durante l\'aggiornamento della nota.';
                    }
                    $stmt_update->close();
                }
            } else { // La nota non esiste
                if (!empty($note_content)) {
                    // Contenuto presente: crea una nuova nota
                    $title = "Nota per la transazione #" . $transaction_id;
                    // Aggiunto creator_id per coerenza con il nuovo schema DB
                    $sql_insert = "INSERT INTO notes (user_id, transaction_id, title, content, creator_id) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    // Il creator_id è lo stesso user_id poiché la nota è personale della transazione
                    $stmt_insert->bind_param("iissi", $user_id, $transaction_id, $title, $note_content, $user_id);
                    if ($stmt_insert->execute()) {
                        $response = ['success' => true, 'message' => 'Nota creata.'];
                    } else {
                        $response['message'] = 'Errore durante la creazione della nota.';
                    }
                    $stmt_insert->close();
                } else {
                    // Nessun contenuto e nessuna nota esistente, non fare nulla
                    $response = ['success' => true, 'message' => 'Nessuna azione richiesta.'];
                }
            }
        }
    }
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];

    // --- Azione: Leggi Nota ---
    if ($action === 'get_note' && isset($_GET['transaction_id'])) {
        $transaction_id = intval($_GET['transaction_id']);

        // Verifica che la transazione appartenga all'utente
        if (!verify_transaction_owner($conn, $transaction_id, $user_id)) {
            $response['message'] = 'Transazione non trovata o non autorizzata.';
        } else {
            $sql_get = "SELECT content FROM notes WHERE transaction_id = ? AND user_id = ?";
            $stmt_get = $conn->prepare($sql_get);
            $stmt_get->bind_param("ii", $transaction_id, $user_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            if ($note = $result_get->fetch_assoc()) {
                $response = ['success' => true, 'content' => $note['content']];
            } else {
                $response = ['success' => true, 'content' => '']; // Nessuna nota trovata
            }
            $stmt_get->close();
        }
    }
}

// Funzione helper per verificare il proprietario della transazione
function verify_transaction_owner($conn, $transaction_id, $user_id) {
    $sql = "SELECT id FROM transactions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $is_owner = $stmt->num_rows > 0;
    $stmt->close();
    return $is_owner;
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>