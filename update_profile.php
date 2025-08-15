<?php
/*
================================================================================
File: update_profile.php
================================================================================
*/
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { exit("Accesso non autorizzato."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $new_username = trim($_POST['username']);

    if (empty($new_username)) {
        header("location: settings.php?message=L'username non può essere vuoto.&type=error");
        exit();
    }

    $sql = "UPDATE users SET username = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_username, $user_id);
        if ($stmt->execute()) {
            $_SESSION["username"] = $new_username; // Aggiorna la sessione!
            header("location: settings.php?message=Profilo aggiornato!&type=success");
        } else {
            header("location: settings.php?message=Errore durante l'aggiornamento.&type=error");
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>