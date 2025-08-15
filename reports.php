<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];

// CONTROLLO ACCESSO PRO
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi ai report avanzati con un piano Premium!");
    exit;
}

// GESTIONE FILTRI
$today = date('Y-m-d');
$six_months_ago = date('Y-m-d', strtotime('-5 months'));
// Recupera il singolo ID del conto selezionato
$selected_account_id = $_GET['account_id'] ?? ''; 

$filters = [
    'start_date' => $_GET['start_date'] ?? $six_months_ago,
    'end_date' => $_GET['end_date'] ?? $today,
    'tag_id' => $_GET['tag_id'] ?? '',
    'account_ids' => [] // Inizia con un array vuoto
];

// Se un conto specifico è stato selezionato, lo aggiungiamo all'array dei filtri.
// Altrimenti, l'array rimane vuoto e le funzioni considereranno tutti i conti.
if (!empty($selected_account_id)) {
    $filters['account_ids'] = [$selected_account_id];
}

// Recupera i dati per i grafici, applicando i filtri
$expensesByCategory = get_expenses_by_category($conn, $user_id, $filters);
$incomeExpenseTrend = get_income_expense_trend($conn, $user_id, $filters);
$netWorthTrend = get_net_worth_trend($conn, $user_id, $filters);

// Recupera i conti per il menu a tendina del filtro
$userAccounts = get_user_accounts($conn, $user_id);
$userTags = get_user_tags($conn, $user_id); // Recupera tutti i tag dell'utente
$current_page = 'reports';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - Bearget</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); }
    </style>
</head>
<body class="text-gray-200">
    <div class="flex h-screen">
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="mb-8">
                <div class="flex items-center gap-4">
                    <button id="menu-button" type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Apri menu principale</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Report finanziari avanzati
                        </h1>
                        <p class="text-gray-400 mt-1">Analizza le tue finanze con i grafici</p>
                    </div>
                </div>
            </header>

            <!-- MODULO FILTRI -->
            <div class="bg-gray-800 rounded-2xl p-4 mb-8">
                <!-- MODIFICATO: Layout griglia a 4 colonne -->
                <form action="reports.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="start_date" class="text-sm font-medium text-gray-400">Da</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="text-sm font-medium text-gray-400">A</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <!-- MODIFICATO: Menu a tendina singolo -->
                    <div>
                        <label for="account_id" class="text-sm font-medium text-gray-400">Conto</label>
                        <select name="account_id" id="account_id" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                            <option value="">Tutti i conti</option>
                            <?php foreach($userAccounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" <?php echo ($account['id'] == $selected_account_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tag_id" class="text-sm font-medium text-gray-400">Etichetta</label>
                        <select name="tag_id" id="tag_id" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                            <option value="">Tutte le etichette</option>
                            <?php foreach($userTags as $tag): ?>
                                <option value="<?php echo $tag['id']; ?>" <?php echo ($tag['id'] == $filters['tag_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg">Applica Filtri</button>
                        <a href="reports.php" class="w-full text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg">Resetta</a>
                    </div>
                </form>
            </div>

            <div class="space-y-8">
                <!-- Grafico Spese per Categoria -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Spese per Categoria</h2>
                    <div class="h-80"><canvas id="expensesChart"></canvas></div>
                </div>
                <!-- Grafico Entrate vs Uscite -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Entrate vs. Uscite</h2>
                    <div class="h-80"><canvas id="incomeExpenseChart"></canvas></div>
                </div>
                <!-- Grafico Andamento Patrimonio -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Andamento Patrimonio Netto</h2>
                    <div class="h-80"><canvas id="netWorthChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const chartColors = {
            purple: '#8b5cf6', blue: '#3b82f6', green: '#10b981', red: '#ef4444',
            grid: 'rgba(255, 255, 255, 0.1)', text: '#d1d5db'
        };

        // --- GRAFICO SPESE PER CATEGORIA ---
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
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: chartColors.text } } } }
        });

        // --- GRAFICO ENTRATE VS USCITE ---
        const incomeExpenseData = <?php echo json_encode($incomeExpenseTrend); ?>;
        const ieCtx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(ieCtx, {
            type: 'line',
            data: {
                labels: incomeExpenseData.labels,
                datasets: [{
                    label: 'Entrate', data: incomeExpenseData.income, borderColor: chartColors.green, backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.4
                }, {
                    label: 'Uscite', data: incomeExpenseData.expenses, borderColor: chartColors.red, backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { color: chartColors.text }, grid: { color: chartColors.grid } }, x: { ticks: { color: chartColors.text }, grid: { color: chartColors.grid } } }, plugins: { legend: { labels: { color: chartColors.text } } } }
        });

        // --- GRAFICO PATRIMONIO NETTO ---
        const netWorthData = <?php echo json_encode($netWorthTrend); ?>;
        const nwCtx = document.getElementById('netWorthChart').getContext('2d');
        new Chart(nwCtx, {
            type: 'bar',
            data: {
                labels: netWorthData.labels,
                datasets: [{
                    label: 'Patrimonio Netto', data: netWorthData.values, backgroundColor: chartColors.blue, borderColor: chartColors.blue, borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false, ticks: { color: chartColors.text }, grid: { color: chartColors.grid } }, x: { ticks: { color: chartColors.text }, grid: { display: false } } }, plugins: { legend: { display: false } } }
        });

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
</body>
</html>