<?php
/*
================================================================================
File: accept_invite.php (Nuovo File)
================================================================================
*/
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { exit("Accesso non autorizzato."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $notification_id = $_POST['notification_id'];
    $fund_id = $_POST['fund_id'];

    $conn->begin_transaction();
    try {
        // 1. Aggiungi l'utente come membro
        $sql_add = "INSERT INTO shared_fund_members (fund_id, user_id) VALUES (?, ?)";
        $stmt_add = $conn->prepare($sql_add);
        $stmt_add->bind_param("ii", $fund_id, $user_id);
        $stmt_add->execute();
        $stmt_add->close();

        // 2. Segna la notifica come letta (o eliminala)
        $sql_read = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt_read = $conn->prepare($sql_read);
        $stmt_read->bind_param("ii", $notification_id, $user_id);
        $stmt_read->execute();
        $stmt_read->close();

        $conn->commit();
        header("location: fund_details.php?id={$fund_id}");
    } catch (Exception $e) {
        $conn->rollback();
        header("location: notifications.php?message=Errore.&type=error");
    }
    $conn->close();
    exit();
}
?>