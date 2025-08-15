<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit("Accesso non autorizzato.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $theme = trim($_POST['theme']);
    
    // Lista dei temi validi per sicurezza
    $valid_themes = ['dark-indigo', 'forest-green', 'ocean-blue', 'sunset-orange', 'royal-purple', 'graphite-gray'];

    if (in_array($theme, $valid_themes)) {
        $sql = "UPDATE users SET theme = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $theme, $user_id);
            if ($stmt->execute()) {
                $_SESSION["theme"] = $theme; // Aggiorna la sessione
                header("location: settings.php?message=Tema aggiornato!&type=success");
            } else {
                header("location: settings.php?message=Errore.&type=error");
            }
            $stmt->close();
        }
    } else {
        header("location: settings.php?message=Tema non valido.&type=error");
    }
    $conn->close();
    exit();
}
?>
