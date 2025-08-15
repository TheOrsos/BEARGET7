<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Sicurezza: Controlla se una sessione di impersonificazione è attiva
if (!isset($_SESSION['admin_id'])) {
    header("location: dashboard.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Recupera i dati originali dell'admin
$admin_user = get_user_by_id($conn, $admin_id);

if ($admin_user) {
    // Ripristina la sessione originale dell'admin
    $_SESSION['loggedin'] = true;
    $_SESSION['id'] = $admin_user['id'];
    $_SESSION['username'] = $admin_user['username'];
    $_SESSION['theme'] = $admin_user['theme'];

    // Rimuovi l'ID dell'admin dalla sessione per terminare l'impersonificazione
    unset($_SESSION['admin_id']);

    // Torna al pannello di amministrazione
    header("location: admin.php");
    exit;
}

// Fallback in caso di problemi
header("location: logout.php");
exit;
?>
