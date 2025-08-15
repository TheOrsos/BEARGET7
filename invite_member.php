<?php
/*
================================================================================
File: invite_member.php (Nuovo File)
Gestisce l'invio di una notifica di invito.
================================================================================
*/
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_user_id = $_SESSION["id"];
    $fund_id = trim($_POST['fund_id']);
    $friend_code = trim($_POST['friend_code']);

    // Trova l'utente da invitare
    $user_to_add = find_user_by_friend_code($conn, $friend_code);
    if (!$user_to_add) {
        header("location: fund_details.php?id={$fund_id}&message=Codice amico non valido.&type=error");
        exit();
    }
    $user_to_add_id = $user_to_add['id'];
    
    // Crea la notifica di invito
    $fund = get_shared_fund_details($conn, $fund_id, $current_user_id);
    $message = htmlspecialchars($_SESSION['username']) . " ti ha invitato a partecipare al fondo '" . htmlspecialchars($fund['name']) . "'.";
    create_notification($conn, $user_to_add_id, 'fund_invite', $message, $fund_id);

    header("location: fund_details.php?id={$fund_id}&message=Invito inviato!&type=success");
    exit();
}
?>
