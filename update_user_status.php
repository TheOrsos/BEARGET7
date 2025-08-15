<?php
session_start();
require_once 'db_connect.php';

// Sicurezza: solo l'admin (ID 1) può eseguire questa operazione
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["id"] != 1) {
    exit("Accesso non autorizzato.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_to_update = trim($_POST['user_id_to_update']);
    $new_status = trim($_POST['new_status']);
    
    // Lista degli stati validi per sicurezza
    $valid_statuses = ['free', 'active', 'lifetime', 'canceled', 'past_due'];

    if (!empty($user_id_to_update) && in_array($new_status, $valid_statuses)) {
        $sql = "UPDATE users SET subscription_status = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_status, $user_id_to_update);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->close();
    header("location: admin.php?message=Stato utente aggiornato!");
    exit();
}
?>
