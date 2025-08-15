<?php
// Inizializza la sessione
session_start();

// Sicurezza
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit("Accesso non autorizzato.");
}

// Includi i file necessari
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];

// Recupera gli stessi filtri usati nella pagina delle transazioni
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'description' => $_GET['description'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'account_id' => $_GET['account_id'] ?? ''
];

// Recupera le transazioni filtrate
$transactions = get_all_transactions($conn, $user_id, $filters);

// Imposta gli header per forzare il download del file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transazioni_bearget_' . date('Y-m-d') . '.csv');

// Apri lo stream di output di PHP
$output = fopen('php://output', 'w');

// Scrivi la riga di intestazione del CSV
fputcsv($output, ['Data', 'Descrizione', 'Categoria', 'Conto', 'Importo']);

// Scrivi ogni transazione nel file CSV
if (!empty($transactions)) {
    foreach ($transactions as $tx) {
        $row = [
            $tx['transaction_date'],
            $tx['description'],
            $tx['category_name'] ?? 'N/A',
            $tx['account_name'],
            number_format($tx['amount'], 2, '.', '') // Formato numerico standard
        ];
        fputcsv($output, $row);
    }
}

fclose($output);
exit();

?>
