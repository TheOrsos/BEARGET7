<?php
/*
================================================================================
File: verify.php
Descrizione: Verifica l'email dell'utente tramite il token.
================================================================================
*/

// Includi il file di connessione al database
require_once 'db_connect.php';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    
    $token = $_GET['token'];

    $sql = "SELECT id FROM users WHERE verification_token = ? AND is_verified = 0 LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->close();
            
            // Token valido, aggiorna lo stato dell'utente a verificato
            $sql_update = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("s", $token);
                $stmt_update->execute();
                $stmt_update->close();
                
                // Reindirizza alla pagina di login con un messaggio di successo
                header("Location: index.php?message=" . urlencode("Email verificata con successo! Ora puoi accedere.") . "&type=success");
                exit();
            }
        } else {
            // Token non valido o già usato
            header("Location: index.php?message=" . urlencode("Token di verifica non valido o scaduto.") . "&type=error");
            exit();
        }
    }
    $conn->close();
} else {
    // Nessun token fornito
    header("Location: index.php?message=" . urlencode("Nessun token di verifica fornito.") . "&type=error");
    exit();
}
?>
