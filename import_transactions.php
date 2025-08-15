<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

// Sicurezza
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit("Accesso non autorizzato.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $account_id = $_POST['account_id'];
    $success_count = 0;
    $skipped_count = 0;

    // Controlla se il file è stato caricato correttamente
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['csv_file']['tmp_name'];

        // Apri il file CSV
        if (($handle = fopen($file_tmp_path, "r")) !== FALSE) {
            
            $conn->begin_transaction();
            try {
                // Prepara la query di inserimento una sola volta
                $sql = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                // Leggi il file riga per riga
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Estrai i dati dalla riga
                    $date = $data[0] ?? null;
                    $description = $data[1] ?? null;
                    $amount = $data[2] ?? null;
                    $category_name = $data[3] ?? null;

                    // --- Validazione della riga ---
                    if (empty($date) || empty($description) || !is_numeric($amount) || empty($category_name)) {
                        $skipped_count++;
                        continue; // Salta alla riga successiva
                    }

                    // Cerca la categoria nel database
                    $category = get_category_by_name_for_user($conn, $user_id, $category_name);
                    if (!$category) {
                        $skipped_count++;
                        continue; // Salta se la categoria non esiste
                    }
                    $category_id = $category['id'];
                    $type = $category['type'];
                    
                    // Formatta l'importo (le spese devono essere negative)
                    $final_amount = ($type == 'expense') ? -abs($amount) : abs($amount);

                    // Esegui la query preparata
                    $stmt->bind_param("iiidsss", $user_id, $account_id, $category_id, $final_amount, $type, $description, $date);
                    $stmt->execute();
                    $success_count++;
                }

                $stmt->close();
                $conn->commit(); // Conferma tutte le transazioni se non ci sono stati errori

            } catch (Exception $e) {
                $conn->rollback(); // Annulla tutto in caso di errore
                header("location: transactions.php?message=Errore durante l'importazione: " . $e->getMessage() . "&type=error");
                exit();
            }

            fclose($handle);
            $message = "Importazione completata! Transazioni aggiunte: {$success_count}. Righe saltate: {$skipped_count}.";
            header("location: transactions.php?message=" . urlencode($message) . "&type=success");
            exit();
        }
    }
}

// Se qualcosa va storto
header("location: transactions.php?message=Errore nel caricamento del file.&type=error");
exit();

?>