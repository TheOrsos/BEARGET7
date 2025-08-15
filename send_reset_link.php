<?php
// Questo file gestisce la logica di creazione del token di reset.
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Controlla se l'email esiste nel database
    $sql_check = "SELECT id FROM users WHERE email = ? LIMIT 1";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows == 1) {
            // Email trovata, genera un token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Il token scade tra 1 ora

            // Salva il token nel database
            $sql_insert = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $stmt_insert->bind_param("sss", $email, $token, $expires_at);
                $stmt_insert->execute();
                $stmt_insert->close();

                // Simula l'invio dell'email mostrando il link
                $reset_link = "https://bearget.kesug.com/reset_password.php?token=" . $token;
                $message = "Link di reset generato. Clicca per procedere.";
                header("Location: forgot_password.php?message=" . urlencode($message) . "&type=success&reset_link=" . urlencode($reset_link));
                exit();
            }
        }
        $stmt_check->close();
    }
    
    // Se l'email non viene trovata, mostriamo un messaggio generico per sicurezza
    $message = "Se un account con questa email esiste, abbiamo inviato un link di recupero.";
    header("Location: forgot_password.php?message=" . urlencode($message) . "&type=success");
    exit();
}
?>
