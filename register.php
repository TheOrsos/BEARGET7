<?php
/*
================================================================================
File: register.php
Descrizione: Gestisce la logica di registrazione dell'utente,
             creando anche categorie e un conto predefiniti.
================================================================================
*/

session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // --- Validazione Server-Side ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        redirect_with_message("Tutti i campi sono obbligatori.", "error");
    }
    if ($password !== $confirm_password) {
        redirect_with_message("Le password non coincidono.", "error");
    }
    // ... (altri controlli di validazione) ...

    // --- Controlla se l'email esiste già ---
    $sql_check = "SELECT id FROM users WHERE email = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            redirect_with_message("Un account con questa email esiste già.", "error");
        }
        $stmt_check->close();
    }

    // --- Inserisci il nuovo utente nel database ---
    $sql_insert = "INSERT INTO users (username, email, password, verification_token, friend_code) VALUES (?, ?, ?, ?, ?)";
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        
        // Genera un codice amico unico
        do {
            $friend_code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
            $sql_check_code = "SELECT id FROM users WHERE friend_code = ?";
            $stmt_check_code = $conn->prepare($sql_check_code);
            $stmt_check_code->bind_param("s", $friend_code);
            $stmt_check_code->execute();
            $stmt_check_code->store_result();
        } while ($stmt_check_code->num_rows > 0);
        $stmt_check_code->close();

        $stmt_insert->bind_param("sssss", $username, $email, $hashed_password, $verification_token, $friend_code);

        if ($stmt_insert->execute()) {
            // Ottieni l'ID dell'utente appena creato
            $new_user_id = $stmt_insert->insert_id;

            // *** NUOVO: Crea un conto predefinito ***
            $sql_account = "INSERT INTO accounts (user_id, name, initial_balance) VALUES (?, 'Conto Principale', 0.00)";
            $stmt_account = $conn->prepare($sql_account);
            $stmt_account->bind_param("i", $new_user_id);
            $stmt_account->execute();
            $stmt_account->close();

            // *** NUOVO: Crea le categorie predefinite ***
            $default_categories = [
                ['Stipendio', 'income', '💼'],
                ['Altre Entrate', 'income', '💰'],
                ['Spesa', 'expense', '🛒'],
                ['Trasporti', 'expense', '⛽️'],
                ['Casa', 'expense', '🏠'],
                ['Bollette', 'expense', '🧾'],
                ['Svago', 'expense', '🎉'],
                ['Ristoranti', 'expense', '🍔'],
                ['Salute', 'expense', '❤️‍🩹'],
                ['Regali', 'expense', '🎁'],
                ['Risparmi', 'expense', '💾'],
                ['Fondi Comuni', 'expense', '👥'],
                ['Trasferimento', 'expense', '🔄']
            ];

            $sql_category = "INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, ?, ?)";
            $stmt_category = $conn->prepare($sql_category);
            foreach ($default_categories as $cat) {
                $stmt_category->bind_param("isss", $new_user_id, $cat[0], $cat[1], $cat[2]);
                $stmt_category->execute();
            }
            $stmt_category->close();

            // Reindirizza con il link di verifica manuale
            $dev_message = "Registrazione quasi completata! Attiva il tuo account per procedere.";
            redirect_with_message($dev_message, "success", $verification_token);

        } else {
            redirect_with_message("Oops! Qualcosa è andato storto. Riprova più tardi.", "error");
        }
        $stmt_insert->close();
    }
    $conn->close();
}

function redirect_with_message($message, $type, $token = null) {
    $url = "index.php?message=" . urlencode($message) . "&type=" . $type . "&action=register";
    if ($token) {
        $url .= "&token=" . $token;
    }
    header("Location: " . $url);
    exit();
}
?>