<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$recurring_id = $_GET['id'] ?? 0;

$recurring_transaction = get_recurring_transaction_by_id($conn, $recurring_id, $user_id);

if (!$recurring_transaction) {
    header("location: recurring.php?message=Transazione non trovata.&type=error");
    exit;
}

$accounts = get_user_accounts($conn, $user_id);
$expenseCategories = get_user_categories($conn, $user_id, 'expense');
$incomeCategories = get_user_categories($conn, $user_id, 'income');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Modifica Transazione Ricorrente - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #111827; }</style>
</head>
<body class="text-gray-200 p-10">
    <h1 class="text-3xl font-bold text-white">Modifica Transazione Ricorrente</h1>
    <div class="bg-gray-800 rounded-2xl p-6 max-w-2xl mx-auto mt-8">
        <form action="update_recurring.php" method="POST" class="space-y-4">
            <input type="hidden" name="recurring_id" value="<?php echo $recurring_transaction['id']; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label>Descrizione</label>
                    <input type="text" name="description" value="<?php echo htmlspecialchars($recurring_transaction['description']); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label>Importo (€)</label>
                    <input type="number" step="0.01" name="amount" value="<?php echo $recurring_transaction['amount']; ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label>Categoria</label>
                    <select name="category_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <optgroup label="Spese">
                            <?php foreach($expenseCategories as $cat){ $selected = ($cat['id'] == $recurring_transaction['category_id']) ? 'selected' : ''; echo "<option value='{$cat['id']}' {$selected}>{$cat['name']}</option>"; } ?>
                        </optgroup>
                        <optgroup label="Entrate">
                            <?php foreach($incomeCategories as $cat){ $selected = ($cat['id'] == $recurring_transaction['category_id']) ? 'selected' : ''; echo "<option value='{$cat['id']}' {$selected}>{$cat['name']}</option>"; } ?>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label>Conto</label>
                    <select name="account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <?php foreach($accounts as $acc){ $selected = ($acc['id'] == $recurring_transaction['account_id']) ? 'selected' : ''; echo "<option value='{$acc['id']}' {$selected}>{$acc['name']}</option>"; } ?>
                    </select>
                </div>
                <div>
                    <label>Frequenza</label>
                    <select name="frequency" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <option value="weekly" <?php if($recurring_transaction['frequency'] == 'weekly') echo 'selected'; ?>>Settimanale</option>
                        <option value="monthly" <?php if($recurring_transaction['frequency'] == 'monthly') echo 'selected'; ?>>Mensile</option>
                        <option value="bimonthly" <?php if($recurring_transaction['frequency'] == 'bimonthly') echo 'selected'; ?>>Bimensile</option>
                        <option value="yearly" <?php if($recurring_transaction['frequency'] == 'yearly') echo 'selected'; ?>>Annuale</option>
                    </select>
                </div>
                <div>
                    <label>Prossima Data</label>
                    <input type="date" name="next_due_date" required value="<?php echo $recurring_transaction['next_due_date']; ?>" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end space-x-4 pt-4">
                <a href="recurring.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
            </div>
        </form>
    </div>
</body>
</html>