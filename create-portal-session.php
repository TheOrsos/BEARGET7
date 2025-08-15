<?php
/*
================================================================================
File: create-portal-session.php (VERSIONE DI DEBUG)
Descrizione: Crea una sessione per il Portale Clienti di Stripe e mostra
             errori dettagliati in caso di problemi.
================================================================================
*/

// Avvia la sessione per accedere ai dati dell'utente loggato
session_start();

// Includi i file di configurazione e le funzioni necessarie
require_once 'db_connect.php'; // Contiene la connessione al DB e l'init di Stripe
require_once 'functions.php';  // Contiene la funzione get_user_by_id

// Sicurezza: Controlla se l'utente è loggato. Se no, reindirizza.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Recupera l'ID dell'utente dalla sessione
$user_id = $_SESSION['id'];
// Recupera tutti i dati dell'utente dal database
$user = get_user_by_id($conn, $user_id);

// Recupera l'ID cliente di Stripe salvato nel nostro database
$stripe_customer_id = $user['stripe_customer_id'];

// Controlla se l'utente ha effettivamente un ID cliente di Stripe.
if (empty($stripe_customer_id)) {
    header("location: settings.php?message=Nessun abbonamento attivo da gestire.&type=error");
    exit;
}

// Definisci l'URL a cui Stripe reindirizzerà l'utente
$return_url = 'https://bearget.kesug.com/settings.php'; 

try {
    // Chiamata all'API di Stripe per creare una sessione del Portale Clienti
    $portalSession = \Stripe\BillingPortal\Session::create([
        'customer' => $stripe_customer_id,
        'return_url' => $return_url,
    ]);

    // Reindirizza l'utente all'URL del portale generato da Stripe
    header("Location: " . $portalSession->url);
    exit();

} catch (\Stripe\Exception\ApiErrorException $e) {
    // --- MODIFICA PER DEBUG ---
    // Mostra l'errore esatto restituito da Stripe invece di un messaggio generico.
    http_response_code(500);
    echo "<h1>Errore API di Stripe:</h1>";
    echo "<p><strong>Messaggio:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>ID Cliente Inviato:</strong> " . htmlspecialchars($stripe_customer_id) . "</p>";
    echo "<p><strong>Suggerimento:</strong> Controlla che questo ID cliente esista nella tua dashboard di Stripe in MODALITÀ TEST.</p>";
    exit();
    // --- FINE MODIFICA ---

} catch (Exception $e) {
    // Cattura qualsiasi altro errore generico
    http_response_code(500);
    echo "Errore Generico: " . $e->getMessage();
    exit();
}
?>