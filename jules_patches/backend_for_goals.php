<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$current_page = 'goals';

// CONTROLLO ACCESSO PRO
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi agli obiettivi con un piano Premium!");
    exit;
}

$goals = get_saving_goals($conn, $user_id);
$accounts = get_user_accounts($conn, $user_id);
$savingCategory = get_category_by_name_for_user($conn, $user_id, 'Risparmi');
?>
