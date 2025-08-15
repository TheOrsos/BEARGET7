<?php
/*
================================================================================
File: decline_invite.php (Nuovo File)
================================================================================
*/
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { exit("Accesso non autorizzato."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $notification_id = $_POST['notification_id'];

    // Segna la notifica come letta (o eliminala)
    $sql_read = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt_read = $conn->prepare($sql_read);
    $stmt_read->bind_param("ii", $notification_id, $user_id);
    $stmt_read->execute();
    $stmt_read->close();
    
    $conn->close();
    header("location: notifications.php");
    exit();
}
?>