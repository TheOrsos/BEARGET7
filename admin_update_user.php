<?php
session_start();
require_once 'db_connect.php';

// Sicurezza: solo l'admin (ID 1) può eseguire questa operazione.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["id"] != 1) {
    header("location: dashboard.php?message=Accesso non autorizzato.&type=error");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_to_update = $_POST['user_id'];
    $new_email = trim($_POST['email']);
    $new_friend_code = strtoupper(trim($_POST['friend_code'])); // Converte in maiuscolo
    $new_password = $_POST['new_password'];

    // Validazione base
    if (empty($user_id_to_update) || empty($new_email) || empty($new_friend_code)) {
        header("location: admin.php?message=Dati mancanti (Email e Codice Amico sono obbligatori).&type=error");
        exit;
    }
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        header("location: admin.php?message=Formato email non valido.&type=error");
        exit;
    }

    // NUOVO: Controlla se il codice amico è già in uso da un ALTRO utente
    $sql_check_code = "SELECT id FROM users WHERE friend_code = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check_code);
    $stmt_check->bind_param("si", $new_friend_code, $user_id_to_update);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        header("location: admin.php?message=Il Codice Amico inserito è già utilizzato da un altro utente.&type=error");
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();


    // Costruzione dinamica della query
    $sql_parts = [];
    $params = [];
    $types = '';

    // Aggiungi l'email e il codice amico all'aggiornamento
    $sql_parts[] = "email = ?";
    $types .= 's';
    $params[] = $new_email;

    $sql_parts[] = "friend_code = ?";
    $types .= 's';
    $params[] = $new_friend_code;

    // Se è stata inserita una nuova password, aggiungila all'aggiornamento
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            header("location: admin.php?message=La nuova password deve essere di almeno 8 caratteri.&type=error");
            exit;
        }
        $sql_parts[] = "password = ?";
        $types .= 's';
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Aggiungi l'ID utente per la clausola WHERE
    $types .= 'i';
    $params[] = $user_id_to_update;

    // Componi la query finale
    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            header("location: admin.php?message=Utente aggiornato con successo!&type=success");
        } else {
            header("location: admin.php?message=Errore durante l'aggiornamento.&type=error");
        }
        $stmt->close();
    } else {
        header("location: admin.php?message=Errore di sistema.&type=error");
    }
    
    $conn->close();
    exit();
} else {
    header("location: admin.php");
    exit();
}
?>