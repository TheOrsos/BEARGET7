<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$transaction_id = $_GET['id'] ?? 0;

$transaction = get_transaction_by_id($conn, $transaction_id, $user_id);
if (!$transaction) {
    header("location: transactions.php?message=Transazione non trovata.&type=error");
    exit;
}

// NUOVO: Controlla se l'utente è Pro
$user = get_user_by_id($conn, $user_id);
$is_pro_user = ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'lifetime');

$userAccounts = get_user_accounts($conn, $user_id);
$expenseCategories = get_user_categories($conn, $user_id, 'expense');
$incomeCategories = get_user_categories($conn, $user_id, 'income');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Transazione - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111827; }
    </style>
</head>
<body class="text-gray-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-gray-800 p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center mb-10">
                    <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center font-bold text-xl">B</div>
                    <span class="ml-3 text-2xl font-extrabold text-white">Bearget</span>
                </div>
                <nav class="space-y-2">
                    <?php if (isset($_SESSION['id']) && $_SESSION['id'] == 1): ?>
                        <a href="admin.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Admin
                        </a>
                    <?php endif; ?>                   
                    <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>
                    <a href="transactions.php" class="flex items-center px-4 py-2.5 text-white bg-gray-900 rounded-lg font-semibold">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Transazioni
                    </a>
                    <a href="accounts.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Conti
                    </a>
                    <a href="categories.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Categorie
                    </a>
                    <a href="reports.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Report
                    </a>
                    <a href="budgets.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        Budget
                    </a>
                    <a href="goals.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.25278C12 6.25278 10.8333 5 9.5 5C8.16667 5 7 6.25278 7 6.25278V9.74722C7 9.74722 8.16667 11 9.5 11C10.8333 11 12 9.74722 12 9.74722V6.25278ZM12 6.25278C12 6.25278 13.1667 5 14.5 5C15.8333 5 17 6.25278 17 6.25278V9.74722C17 9.74722 15.8333 11 14.5 11C13.1667 11 12 9.74722 12 9.74722V6.25278Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V14"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14H15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17H15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20H14"></path></svg>
                        Obiettivi
                    </a>
                    <a href="recurring.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5m11 2a9 9 0 11-2.93-6.93"></path></svg>
                        Ricorrenti
                    </a>
                    <a href="shared_funds.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Fondi Comuni
                    </a>
                    <a href="notifications.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors relative">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Notifiche
                        <?php if($notification_count > 0): ?>
                        <span class="absolute top-1 right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="notes.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Note
                    </a>
                </nav>
            </div>
            <div class="border-t border-gray-700 pt-4">
                <a href="settings.php" class="flex items-center px-4 py-2.5 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.096 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Impostazioni
                </a>
                <a href="bearget_info.html" target="_blank" class="flex items-center px-4 py-2.5 mt-2 text-gray-400 hover:bg-gray-700 hover:text-white rounded-lg transition-colors">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Info & Supporto
                </a>
                <a href="logout.php" class="flex items-center px-4 py-2.5 mt-2 text-red-400 hover:bg-red-500 hover:text-white rounded-lg transition-colors">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-white">Modifica Transazione</h1>
                <p class="text-gray-400">Correggi i dettagli del movimento.</p>
            </header>

            <div class="bg-gray-800 rounded-2xl p-6 max-w-2xl mx-auto">
                <form action="update_transaction.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-300 mb-1">Importo</label>
                            <!-- Usiamo abs() per mostrare sempre un valore positivo nel campo -->
                            <input type="number" step="0.01" name="amount" id="amount" required value="<?php echo abs($transaction['amount']); ?>" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-300 mb-1">Data</label>
                            <input type="date" name="transaction_date" id="transaction_date" value="<?php echo $transaction['transaction_date']; ?>" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Descrizione</label>
                            <input type="text" name="description" id="description" required value="<?php echo htmlspecialchars($transaction['description']); ?>" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="account_id" class="block text-sm font-medium text-gray-300 mb-1">Conto</label>
                            <select name="account_id" id="account_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <?php foreach($userAccounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>" <?php echo ($account['id'] == $transaction['account_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-300 mb-1">Categoria</label>
                            <select name="category_id" id="category_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <optgroup label="Spese">
                                    <?php foreach($expenseCategories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $transaction['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Entrate">
                                     <?php foreach($incomeCategories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $transaction['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <!-- NUOVO: Sezione Tag (solo per utenti Pro) -->
                        <?php if ($is_pro_user): ?>
                        <div class="mt-6">
                            <label for="tags" class="block text-sm font-medium text-gray-300 mb-1">Etichette (separate da virgola)</label>
                            <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($tags_string); ?>" placeholder="Es. vacanze, lavoro, regali" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        </div>
                        <?php endif; ?>                        
                        <!-- NUOVO: Sezione per l'upload della fattura (solo per utenti Pro) -->
                        <?php if ($is_pro_user): ?>
                            
                        <div class="mt-6 border-t border-gray-700 pt-6">
                            <label for="invoice_file" class="block text-sm font-medium text-gray-300 mb-1">Allega Fattura (Max 2MB)</label>
                            <?php if (!empty($transaction['invoice_path'])): ?>
                                <div class="flex items-center justify-between bg-gray-700 p-2 rounded-lg mb-2">
                                    <a href="<?php echo htmlspecialchars($transaction['invoice_path']); ?>" target="_blank" class="text-indigo-400 hover:underline text-sm">Visualizza allegato corrente</a>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="delete_invoice" id="delete_invoice" class="h-4 w-4 rounded bg-gray-900 border-gray-600 text-primary-600 focus:ring-primary-500">
                                        <label for="delete_invoice" class="ml-2 text-sm text-gray-400">Elimina</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="invoice_file" id="invoice_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-600 file:text-white hover:file:bg-primary-700">
                        </div>
                        <?php endif; ?>                        
                        </div>

                    <div class="mt-8 flex justify-end space-x-4">
                        <a href="transactions.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg transition-colors">Annulla</a>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg transition-colors">Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>