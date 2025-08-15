<?php
/*
================================================================================
File: logout.php
Descrizione: Gestisce il logout dell'utente, distruggendo la sessione
             e reindirizzando alla pagina di login.
================================================================================
*/

// Inizializza la sessione.
session_start();
 
// Unset di tutte le variabili di sessione.
$_SESSION = array();
 
// Distrugge la sessione.
session_destroy();
 
// Reindirizza alla pagina di login.
header("location: index.php");
exit;
?>
