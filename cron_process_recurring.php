<?php
/*
================================================================================
File: cron_process_recurring.php
Descrizione: Esegue l'elaborazione di TUTTE le transazioni ricorrenti scadute
             per TUTTI gli utenti. Questo script è pensato per essere eseguito
             automaticamente dal server (tramite Cron Job) una volta al giorno.
================================================================================
*/

// Imposta il fuso orario per coerenza
date_default_timezone_set('Europe/Rome');

require_once 'db_connect.php';
require_once 'functions.php';

// Logga l'inizio del processo
$log_message = "[" . date('Y-m-d H:i:s') . "] Avvio del processo Cron per le transazioni ricorrenti.\n";
echo $log_message;
file_put_contents('cron_log.txt', $log_message, FILE_APPEND);

// Recupera tutte le transazioni ricorrenti scadute per tutti gli utenti
$due_transactions = get_all_due_recurring_transactions($conn);

if (empty($due_transactions)) {
    $log_message = "Nessuna transazione ricorrente da elaborare.\n";
    echo $log_message;
    file_put_contents('cron_log.txt', $log_message, FILE_APPEND);
    exit;
}

$processed_count = 0;
$failed_count = 0;
$log_message = "Trovate " . count($due_transactions) . " transazioni da elaborare.\n";
echo $log_message;
file_put_contents('cron_log.txt', $log_message, FILE_APPEND);

foreach ($due_transactions as $recurring) {
    $conn->begin_transaction();
    try {
        $user_id = $recurring['user_id'];
        
        // 1. Inserisci la nuova transazione nello storico
        $amount = $recurring['type'] == 'expense' ? -abs($recurring['amount']) : abs($recurring['amount']);
        $sql_insert = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiidsss", $user_id, $recurring['account_id'], $recurring['category_id'], $amount, $recurring['type'], $recurring['description'], $recurring['next_due_date']);
        $stmt_insert->execute();
        $stmt_insert->close();

        // 2. Calcola la prossima data di scadenza
        $next_date = new DateTime($recurring['next_due_date']);
        switch ($recurring['frequency']) {
            case 'weekly': $next_date->modify('+1 week'); break;
            case 'bimonthly': $next_date->modify('+2 months'); break;
            case 'monthly': $next_date->modify('+1 month'); break;
            case 'yearly': $next_date->modify('+1 year'); break;
        }
        $new_next_due_date = $next_date->format('Y-m-d');

        // 3. Aggiorna la transazione ricorrente con la nuova data
        $sql_update = "UPDATE recurring_transactions SET next_due_date = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_next_due_date, $recurring['id']);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();
        $processed_count++;
        
        $log_message = "  - OK: Elaborata transazione ricorrente ID {$recurring['id']} per utente ID {$user_id}. Prossima scadenza: {$new_next_due_date}\n";
        echo $log_message;
        file_put_contents('cron_log.txt', $log_message, FILE_APPEND);

    } catch (Exception $e) {
        $conn->rollback();
        $failed_count++;
        $log_message = "  - ERRORE: Fallita elaborazione transazione ricorrente ID {$recurring['id']}. Errore: " . $e->getMessage() . "\n";
        echo $log_message;
        error_log($log_message); // Logga anche nel file di errore PHP
        file_put_contents('cron_log.txt', $log_message, FILE_APPEND);
    }
}

$log_message = "Processo terminato. Elaborate: {$processed_count}. Fallite: {$failed_count}.\n\n";
echo $log_message;
file_put_contents('cron_log.txt', $log_message, FILE_APPEND);

$conn->close();
?>
