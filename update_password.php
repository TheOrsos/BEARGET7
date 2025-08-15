<?php
/*
================================================================================
File: update_password.php
================================================================================
*/
session_start();
require_once 'db_connect.php';
require_once 'functions.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { exit("Accesso non autorizzato."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validazione
    if ($new_password !== $confirm_password) {
        header("location: settings.php?message=Le nuove password non coincidono.&type=error");
        exit();
    }
    if (strlen($new_password) < 8) {
        header("location: settings.php?message=La nuova password deve essere di almeno 8 caratteri.&type=error");
        exit();
    }

    // Verifica password attuale
    $user = get_user_by_id($conn, $user_id);
    if ($user && password_verify($current_password, $user['password'])) {
        // La password attuale è corretta, procedi con l'aggiornamento
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt->execute()) {
                header("location: settings.php?message=Password cambiata con successo!&type=success");
            } else {
                header("location: settings.php?message=Errore durante l'aggiornamento.&type=error");
            }
            $stmt->close();
        }
    } else {
        // Password attuale non corretta
        header("location: settings.php?message=La password attuale non è corretta.&type=error");
    }
    $conn->close();
    exit();
}
?>