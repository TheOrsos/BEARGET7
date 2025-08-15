<?php
/*
================================================================================
File: db_connect.php
Descrizione: Stabilisce la connessione al database usando variabili da .env
================================================================================
*/

// Carica Composer autoload per usare vlucas/phpdotenv
require_once __DIR__ . '/vendor/autoload.php';

// Carica variabili d'ambiente dal file .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Legge le variabili
$db_server   = $_ENV['DB_SERVER'];
$db_user     = $_ENV['DB_USERNAME'];
$db_password = $_ENV['DB_PASSWORD'];
$db_name     = $_ENV['DB_NAME'];

// Crea la connessione
$conn = new mysqli($db_server, $db_user, $db_password, $db_name);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Imposta il charset
$conn->set_charset("utf8mb4");

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
?>