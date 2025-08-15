<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
check_and_process_recurring_transactions($conn, $user_id);
check_budget_alerts($conn, $user_id);

$username = htmlspecialchars($_SESSION["username"]);
$totalBalance = get_total_balance($conn, $user_id);
$monthlySummary = get_monthly_summary($conn, $user_id);
$monthlyIncome = $monthlySummary['income'];
$monthlyExpenses = abs($monthlySummary['expenses']); // Usiamo abs per visualizzazione
$recentTransactions = get_recent_transactions($conn, $user_id, 4);
$expensesByCategory = get_expenses_by_category($conn, $user_id);
$userAccounts = get_user_accounts($conn, $user_id);
$expenseCategories = get_user_categories($conn, $user_id, 'expense');
$incomeCategories = get_user_categories($conn, $user_id, 'income');
$user = get_user_by_id($conn, $user_id);
$is_pro_user = ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'lifetime');

$current_page = 'dashboard'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="theme.php">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 500: 'var(--color-primary-500)', 600: 'var(--color-primary-600)', 700: 'var(--color-primary-700)' },
                        gray: { 100: 'var(--color-gray-100)', 200: 'var(--color-gray-200)', 300: 'var(--color-gray-300)', 400: 'var(--color-gray-400)', 700: 'var(--color-gray-700)', 800: 'var(--color-gray-800)', 900: 'var(--color-gray-900)' },
                        success: 'var(--color-success)', danger: 'var(--color-danger)', warning: 'var(--color-warning)'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); }
        .modal-backdrop { transition: opacity 0.3s ease; }
        .modal-content { transition: transform 0.3s ease; }
    </style>
</head>
<body class="text-gray-300">
    
    <?php if (isset($_SESSION['admin_id'])): ?>
        <div class="bg-yellow-500 text-black text-center p-2 font-semibold fixed top-0 left-0 right-0 z-50">
            Stai navigando come "<?php echo htmlspecialchars($_SESSION['username']); ?>". 
            <a href="stop_impersonating.php" class="underline hover:text-gray-800">Torna al tuo account Admin</a>
        </div>
        <div class="pt-10">
    <?php endif; ?>

    <div class="flex h-screen">
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <button id="menu-button" type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Apri menu principale</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Ciao, <?php echo $username; ?>!</h1>
                        <p class="text-gray-400 mt-1">Ecco il riepilogo delle tue finanze.</p>
                    </div>
                </div>
                <button onclick="openModal('add-transaction-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Aggiungi Movimento
                </button>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-800 p-6 rounded-2xl"><h3 class="text-gray-400 text-sm font-medium">Saldo Totale</h3><p id="total-balance" class="text-3xl font-extrabold text-white mt-1">€<?php echo number_format($totalBalance, 2, ',', '.'); ?></p></div>
                        <div class="bg-gray-800 p-6 rounded-2xl"><h3 class="text-gray-400 text-sm font-medium">Entrate (Mese)</h3><p id="monthly-income" class="text-3xl font-bold text-success mt-1">+€<?php echo number_format($monthlyIncome, 2, ',', '.'); ?></p></div>
                        <div class="bg-gray-800 p-6 rounded-2xl"><h3 class="text-gray-400 text-sm font-medium">Uscite (Mese)</h3><p id="monthly-expenses" class="text-3xl font-bold text-danger mt-1">-€<?php echo number_format($monthlyExpenses, 2, ',', '.'); ?></p></div>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-2xl">
                        <h3 class="text-xl font-bold text-white mb-4">Transazioni Recenti</h3>
                        <div id="recent-transactions-list" class="space-y-4">
                            <?php if (empty($recentTransactions)): ?>
                                <div id="empty-state-transactions" class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    <h3 class="mt-2 text-sm font-medium text-white">Nessuna transazione</h3>
                                    <p class="mt-1 text-sm text-gray-500">Inizia aggiungendo il tuo primo movimento.</p>
                                </div>
                            <?php else: foreach ($recentTransactions as $tx): ?>
                            <div class="flex items-center justify-between p-2 rounded-lg transition-colors hover:bg-gray-700/50">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-xl mr-4"><?php echo htmlspecialchars($tx['icon'] ?? '💸'); ?></div>
                                    <div>
                                        <p class="font-semibold text-white"><?php echo htmlspecialchars($tx['description']); ?></p>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($tx['category_name']); ?> • <?php echo date("d/m/Y", strtotime($tx['transaction_date'])); ?></p>
                                    </div>
                                </div>
                                <p class="font-bold <?php echo $tx['amount'] > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($tx['amount'] > 0 ? '+' : '') . '€' . number_format($tx['amount'], 2, ',', '.'); ?></p>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1 bg-gray-800 p-6 rounded-2xl flex flex-col">
                    <h3 class="text-xl font-bold text-white mb-4">Spese per Categoria</h3>
                    <div class="flex-grow flex items-center justify-center h-64 lg:h-auto"><canvas id="expensesChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi Movimento -->
    <div id="add-transaction-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-transaction-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-2">Aggiungi Movimento</h2>
            <p class="text-gray-400 mb-6">Inserisci i dettagli del movimento.</p>

            <div class="mb-4">
                <div class="flex border-b border-gray-700">
                    <button type="button" id="tab-expense" class="tab-btn flex-1 py-2 font-semibold text-white border-b-2 border-primary-500">Uscita</button>
                    <button type="button" id="tab-income" class="tab-btn flex-1 py-2 font-semibold text-gray-400 border-b-2 border-transparent">Entrata</button>
                    <button type="button" id="tab-transfer" class="tab-btn flex-1 py-2 font-semibold text-gray-400 border-b-2 border-transparent">Trasferimento</button>
                </div>
            </div>

            <form id="form-expense-income" action="add_transaction.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" id="transaction-type" value="expense">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Importo</label>
                        <input type="number" step="0.01" name="amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Descrizione</label>
                        <input type="text" name="description" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Conto</label>
                        <select name="account_id" id="account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <?php foreach($userAccounts as $account): ?><option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Categoria</label>
                        <select name="category_id" id="category_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2"></select>
                    </div>
                    <?php if ($is_pro_user): ?>
                    <div class="md:col-span-2">
                        <label for="tags" class="block text-sm font-medium text-gray-300 mb-1">Etichette (separate da virgola)</label>
                        <input type="text" name="tags" placeholder="Es. vacanze, lavoro, regali" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label for="invoice_file" class="block text-sm font-medium text-gray-300 mb-1">Allega Fattura (Max 2MB)</label>
                        <input type="file" name="invoice_file" id="invoice_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-600 file:text-white hover:file:bg-primary-700">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-transaction-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
                </div>
            </form>

            <form id="form-transfer" action="add_transfer.php" method="POST" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Importo</label>
                        <input type="number" step="0.01" name="amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Descrizione</label>
                        <input type="text" name="description" placeholder="Es. Spostamento su conto risparmi" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Da Conto</label>
                        <select name="from_account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <?php foreach($userAccounts as $account): ?><option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">A Conto</label>
                        <select name="to_account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <?php foreach($userAccounts as $account): ?><option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-transaction-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Trasferimento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            backdrop.classList.add('opacity-0');
            content.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        const tabExpense = document.getElementById('tab-expense');
        const tabIncome = document.getElementById('tab-income');
        const tabTransfer = document.getElementById('tab-transfer');
        const formExpenseIncome = document.getElementById('form-expense-income');
        const formTransfer = document.getElementById('form-transfer');
        const categorySelect = document.getElementById('category_id');
        const expenseCategories = <?php echo json_encode($expenseCategories); ?>;
        const incomeCategories = <?php echo json_encode($incomeCategories); ?>;

        function populateCategories(type) {
            categorySelect.innerHTML = '';
            const categories = (type === 'expense') ? expenseCategories : incomeCategories;
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                categorySelect.appendChild(option);
            });
        }
        
        function switchTab(activeTab) {
            [tabExpense, tabIncome, tabTransfer].forEach(tab => {
                tab.classList.remove('text-white', 'border-primary-500');
                tab.classList.add('text-gray-400', 'border-transparent');
            });
            activeTab.classList.add('text-white', 'border-primary-500');
            activeTab.classList.remove('text-gray-400', 'border-transparent');

            if (activeTab === tabTransfer) {
                formExpenseIncome.classList.add('hidden');
                formTransfer.classList.remove('hidden');
            } else {
                formExpenseIncome.classList.remove('hidden');
                formTransfer.classList.add('hidden');
                document.getElementById('transaction-type').value = activeTab === tabIncome ? 'income' : 'expense';
                populateCategories(activeTab === tabIncome ? 'income' : 'expense');
            }
        }

        tabExpense.addEventListener('click', () => switchTab(tabExpense));
        tabIncome.addEventListener('click', () => switchTab(tabIncome));
        tabTransfer.addEventListener('click', () => switchTab(tabTransfer));
        
        // --- GESTIONE GRAFICO ---
        const expensesData = <?php echo json_encode($expensesByCategory); ?>;
        const expCtx = document.getElementById('expensesChart').getContext('2d');
        new Chart(expCtx, {
            type: 'doughnut',
            data: {
                labels: expensesData.labels,
                datasets: [{
                    data: expensesData.values,
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7'],
                    borderColor: 'var(--color-gray-800)',
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // --- NUOVA LOGICA AJAX PER LA DASHBOARD ---

        // Funzione per mostrare le notifiche "toast"
        function showToast(message, type = 'success') { /* ... (codice da accounts.php) ... */ }

        // Gestione invio form Entrate/Uscite
        formExpenseIncome.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formExpenseIncome);
            
            fetch(formExpenseIncome.action, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        addTransactionToRecentList(data.transaction);
                        updateDashboardSummaries(data.transaction.amount);
                        formExpenseIncome.reset();
                        populateCategories('expense'); // Ripristina le categorie di spesa
                        closeModal('add-transaction-modal');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => showToast('Errore di rete.', 'error'));
        });

        // Gestione invio form Trasferimento
        formTransfer.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formTransfer);

            fetch(formTransfer.action, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        // Per un trasferimento, il saldo totale non cambia, quindi non lo aggiorniamo.
                        // Aggiungiamo solo la transazione di uscita alla lista per semplicità.
                        addTransactionToRecentList({
                            ...data.transfer,
                            amount: -Math.abs(data.transfer.amount) // Assicurati sia negativo
                        });
                        formTransfer.reset();
                        closeModal('add-transaction-modal');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => showToast('Errore di rete.', 'error'));
        });

        // Aggiunge dinamicamente una transazione alla lista "Recenti"
        function addTransactionToRecentList(tx) {
            const list = document.getElementById('recent-transactions-list');
            const emptyState = document.getElementById('empty-state-transactions');
            if (emptyState) emptyState.remove(); // Rimuovi il messaggio "Nessuna transazione"

            const newTxElement = document.createElement('div');
            newTxElement.className = 'flex items-center justify-between p-2 rounded-lg transition-colors hover:bg-gray-700/50';

            const amountColor = tx.amount > 0 ? 'text-success' : 'text-danger';
            const amountSign = tx.amount > 0 ? '+' : '';
            const formattedAmount = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(tx.amount);
            const formattedDate = new Date(tx.transaction_date + 'T00:00:00').toLocaleDateString('it-IT');

            newTxElement.innerHTML = `
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-xl mr-4">${escapeHTML(tx.icon)}</div>
                    <div>
                        <p class="font-semibold text-white">${escapeHTML(tx.description)}</p>
                        <p class="text-sm text-gray-400">${escapeHTML(tx.category_name)} • ${formattedDate}</p>
                    </div>
                </div>
                <p class="font-bold ${amountColor}">${amountSign}${formattedAmount}</p>
            `;
            list.prepend(newTxElement); // Aggiunge il nuovo elemento in cima alla lista
        }

        // Aggiorna i riquadri di riepilogo (Saldo, Entrate, Uscite)
        function updateDashboardSummaries(amount) {
            const totalBalanceEl = document.getElementById('total-balance');
            const monthlyIncomeEl = document.getElementById('monthly-income');
            const monthlyExpensesEl = document.getElementById('monthly-expenses');

            // Funzione helper per estrarre il valore numerico
            const parseCurrency = (el) => parseFloat(el.textContent.replace(/[€.\s+]/g, '').replace(',', '.')) || 0;

            // Aggiorna Saldo Totale
            let currentBalance = parseCurrency(totalBalanceEl);
            totalBalanceEl.textContent = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(currentBalance + amount);
            
            // Aggiorna Entrate/Uscite del mese
            if (amount > 0) {
                let currentIncome = parseCurrency(monthlyIncomeEl);
                monthlyIncomeEl.textContent = '+' + new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(currentIncome + amount);
            } else {
                let currentExpenses = parseCurrency(monthlyExpensesEl);
                monthlyExpensesEl.textContent = '-' + new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(currentExpenses + Math.abs(amount));
            }
        }

        function escapeHTML(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // --- NUOVA LOGICA PER LA SIDEBAR RESPONSIVE ---
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const menuButton = document.getElementById('menu-button');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');

            const toggleSidebar = () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebarBackdrop.classList.toggle('hidden');
            };

            if (menuButton) {
                menuButton.addEventListener('click', toggleSidebar);
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', toggleSidebar);
            }
        });
    </script>
    
    <?php if (isset($_SESSION['admin_id'])): ?>
        </div>
    <?php endif; ?>
</body>
</html>