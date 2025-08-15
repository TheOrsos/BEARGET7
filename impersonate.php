<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Sicurezza: Solo l'admin può impersonare e non può impersonare se stesso.
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["id"]) || $_SESSION["id"] != 1) {
    header("location: dashboard.php");
    exit;
}

$user_id_to_impersonate = $_GET['id'] ?? 0;

if ($user_id_to_impersonate && $user_id_to_impersonate != 1) {
    
    // Recupera i dati dell'utente da impersonare per assicurarsi che esista
    $user_to_impersonate = get_user_by_id($conn, $user_id_to_impersonate);

    if ($user_to_impersonate) {
        // Salva l'ID dell'admin in una variabile di sessione separata
        $_SESSION['admin_id'] = $_SESSION['id'];

        // Sovrascrivi la sessione attuale con i dati dell'utente da impersonare
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user_to_impersonate['id'];
        $_SESSION['username'] = $user_to_impersonate['username'];
        $_SESSION['theme'] = $user_to_impersonate['theme'];
        
        // Reindirizza alla dashboard dell'utente
        header("location: dashboard.php");
        exit;
    }
}

// Se qualcosa va storto, torna al pannello admin con un errore
header("location: admin.php?message=Impossibile impersonare l'utente.&type=error");
exit;
?>
