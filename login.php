<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        login_redirect_with_message("Email e password sono obbligatori.", "error");
    }

    // AGGIORNATO: Seleziona anche la colonna 'theme'
    $sql = "SELECT id, username, password, is_verified, theme FROM users WHERE email = ? LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // AGGIORNATO: Aggiungi $theme al bind_result
            $stmt->bind_result($id, $username, $hashed_password, $is_verified, $theme);
            if ($stmt->fetch()) {
                if (password_verify($password, $hashed_password)) {
                    if ($is_verified == 1) {
                        session_regenerate_id();
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $id;
                        $_SESSION['username'] = $username;
                        // AGGIORNATO: Salva il tema nella sessione
                        $_SESSION['theme'] = $theme;

                        header("Location: dashboard.php"); 
                        exit();
                    } else {
                        login_redirect_with_message("Il tuo account non è stato ancora verificato. Controlla la tua email.", "error");
                    }
                } else {
                    login_redirect_with_message("Credenziali non valide.", "error");
                }
            }
        } else {
            login_redirect_with_message("Credenziali non valide.", "error");
        }
        $stmt->close();
    }
    $conn->close();
}

function login_redirect_with_message($message, $type) {
    header("Location: index.php?message=" . urlencode($message) . "&type=" . $type);
    exit();
}
?>