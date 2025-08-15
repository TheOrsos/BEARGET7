<?php
/*
================================================================================
File: edit_goal.php
================================================================================
*/
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
require_once 'db_connect.php';
require_once 'functions.php';
$user_id = $_SESSION["id"];
$goal_id = $_GET['id'] ?? 0;
$goal = get_goal_by_id($conn, $goal_id, $user_id);
if (!$goal) { header("location: goals.php?message=Obiettivo non trovato.&type=error"); exit; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Obiettivo - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #111827; }</style>
</head>
<body class="text-gray-200 p-10">
    <h1 class="text-3xl font-bold text-white">Modifica Obiettivo</h1>
    <div class="bg-gray-800 rounded-2xl p-6 max-w-lg mx-auto mt-8">
        <form action="update_goal.php" method="POST" class="space-y-4">
            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
            <div>
                <label for="name">Nome Obiettivo</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($goal['name']); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
            </div>
            <div>
                <label for="target_amount">Importo Obiettivo (€)</label>
                <input type="number" step="0.01" name="target_amount" value="<?php echo $goal['target_amount']; ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
            </div>
            <div class="flex justify-end space-x-4">
                <a href="goals.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
            </div>
        </form>
    </div>
</body>
</html>
?>