<?php
// Questo file aggiorna effettivamente la password nel database.
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        header("Location: reset_password.php?token=" . urlencode($token) . "&message=Le password non coincidono.");
        exit();
    }
    if (strlen($new_password) < 8) {
        header("Location: reset_password.php?token=" . urlencode($token) . "&message=La password deve essere di almeno 8 caratteri.");
        exit();
    }

    // Trova l'email associata al token
    $sql_get_email = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1";
    if ($stmt_get = $conn->prepare($sql_get_email)) {
        $stmt_get->bind_param("s", $token);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        if ($user_data = $result->fetch_assoc()) {
            $email = $user_data['email'];
            $stmt_get->close();

            // Aggiorna la password dell'utente
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET password = ? WHERE email = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("ss", $hashed_password, $email);
                $stmt_update->execute();
                $stmt_update->close();

                // Elimina il token usato
                $sql_delete = "DELETE FROM password_resets WHERE email = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("s", $email);
                $stmt_delete->execute();
                $stmt_delete->close();

                header("Location: index.php?message=" . urlencode("Password aggiornata con successo! Ora puoi accedere.") . "&type=success");
                exit();
            }
        }
    }
    
    // Se il token non è valido
    header("Location: index.php?message=" . urlencode("Link di reset non valido o scaduto.") . "&type=error");
    exit();
}
?>