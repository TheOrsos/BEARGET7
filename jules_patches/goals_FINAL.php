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

$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi agli obiettivi con un piano Premium!");
    exit;
}

$goals = get_saving_goals($conn, $user_id);
$accounts = get_user_accounts($conn, $user_id);
$savingCategory = get_category_by_name_for_user($conn, $user_id, 'Risparmi');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obiettivi di Risparmio - Bearget</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); }
        .modal-backdrop { transition: opacity 0.3s ease-in-out; }
        .modal-content { transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; }
        .row-fade-out { transition: opacity 0.5s ease-out; opacity: 0; }
    </style>
</head>
<body class="text-gray-200">

    <div class="flex h-screen">
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <button id="menu-button" type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Apri menu principale</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Obiettivi di Risparmio
                        </h1>
                        <p class="text-gray-400 mt-1">Traccia i progressi verso i tuoi sogni.</p>
                    </div>
                </div>
                <button onclick="openModal('add-goal-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Crea Obiettivo
                </button>
            </header>

            <div id="goals-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (empty($goals)): ?>
                    <div id="empty-state-goals" class="md:col-span-2 xl:col-span-3 bg-gray-800 rounded-2xl p-10 text-center flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.25278C12 6.25278 10.8333 5 9.5 5C8.16667 5 7 6.25278 7 6.25278V9.74722C7 9.74722 8.16667 11 9.5 11C10.8333 11 12 9.74722 12 9.74722V6.25278ZM12 6.25278C12 6.25278 13.1667 5 14.5 5C15.8333 5 17 6.25278 17 6.25278V9.74722C17 9.74722 15.8333 11 14.5 11C13.1667 11 12 9.74722 12 9.74722V6.25278Z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11V14"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14H15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17H15"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20H14"></path></svg>
                        <h3 class="text-lg font-semibold text-white">Nessun obiettivo impostato</h3>
                        <p class="text-gray-400 max-w-sm mx-auto mt-1">Creare un obiettivo di risparmio è il primo passo per realizzarlo. Inizia ora!</p>
                        <button onclick="openModal('add-goal-modal')" class="mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Crea il tuo primo obiettivo
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($goals as $goal):
                        $percentage = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                    ?>
                    <div id="goal-<?php echo $goal['id']; ?>" class="bg-gray-800 rounded-2xl p-5 flex flex-col transition-transform duration-200 hover:-translate-y-1 hover:shadow-2xl" data-goal-id="<?php echo $goal['id']; ?>">
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold text-white mb-2 goal-name"><?php echo htmlspecialchars($goal['name']); ?></h3>
                                <div class="flex items-center space-x-1">
                                    <button onclick='openEditGoalModal(<?php echo json_encode($goal); ?>)' title="Modifica Obiettivo" class="p-2 text-gray-400 hover:text-blue-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg></button>
                                    <button onclick="openHistoryModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars(addslashes($goal['name'])); ?>')" title="Cronologia Contributi" class="p-2 text-gray-400 hover:text-green-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></button>
                                    <form action="delete_goal.php" method="POST" class="delete-form">
                                        <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                        <button type="submit" title="Elimina Obiettivo" class="p-2 text-gray-400 hover:text-red-400">&times;</button>
                                    </form>
                                </div>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                                <div class="bg-green-500 h-2.5 rounded-full progress-bar" style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>
                            <div class="flex justify-between text-sm text-gray-300">
                                <span class="current-amount">€<?php echo number_format($goal['current_amount'], 2, ',', '.'); ?></span>
                                <span class="target-amount text-gray-400">di €<?php echo number_format($goal['target_amount'], 2, ',', '.'); ?></span>
                            </div>
                            <?php if (!empty($goal['target_date'])): ?>
                            <div class="text-sm text-gray-400 mt-3 flex items-center gap-2 goal-date">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Scadenza: <?php echo date('d/m/Y', strtotime($goal['target_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <button onclick="openContributionModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars(addslashes($goal['name'])); ?>')" class="w-full bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 rounded-lg">Aggiungi Fondi</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi Obiettivo -->
    <div id="add-goal-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-goal-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-6">Crea Nuovo Obiettivo</h2>
            <form id="add-goal-form" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Nome Obiettivo</label>
                    <input type="text" name="name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="target_amount" class="block text-sm font-medium text-gray-300 mb-1">Importo Obiettivo (€)</label>
                    <input type="number" step="0.01" name="target_amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="target_date" class="block text-sm font-medium text-gray-300 mb-1">Data di Scadenza (Opzionale)</label>
                    <input type="date" name="target_date" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-goal-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Crea</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Modifica Obiettivo -->
    <div id="edit-goal-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-goal-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Modifica Obiettivo</h2>
                <span id="edit-goal-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <form id="edit-goal-form" class="space-y-4">
                <input type="hidden" name="goal_id" id="edit-goal-id">
                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-300 mb-1">Nome Obiettivo</label>
                    <input type="text" name="name" id="edit-name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="edit-target-amount" class="block text-sm font-medium text-gray-300 mb-1">Importo Obiettivo (€)</label>
                    <input type="number" step="0.01" name="target_amount" id="edit-target-amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="edit-target-date" class="block text-sm font-medium text-gray-300 mb-1">Data di Scadenza (Opzionale)</label>
                    <input type="date" name="target_date" id="edit-target-date" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-goal-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Aggiungi Contributo -->
    <div id="add-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-contribution-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-2">Aggiungi Fondi</h2>
            <p id="contribution-goal-name" class="text-gray-400 mb-6"></p>
            <form id="add-contribution-form" class="space-y-4">
                <input type="hidden" name="goal_id" id="contribution-goal-id">
                <input type="hidden" name="category_id" value="<?php echo $savingCategory['id'] ?? ''; ?>">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-300 mb-1">Importo da Aggiungere (€)</label>
                    <input type="number" step="0.01" name="amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="account_id" class="block text-sm font-medium text-gray-300 mb-1">Preleva da Conto</label>
                    <select name="account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <?php foreach($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!isset($savingCategory['id'])): ?>
                    <p class="text-sm text-yellow-400">Attenzione: Categoria 'Risparmi' non trovata. Il contributo non creerà una transazione di spesa.</p>
                <?php endif; ?>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-contribution-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Aggiungi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale di Conferma Eliminazione Obiettivo -->
    <div id="confirm-delete-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... content from original goals.php ... -->
    </div>

    <!-- Modale Cronologia Contributi -->
    <div id="history-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... content from previous implementation ... -->
    </div>

    <!-- Modale Modifica Contributo -->
    <div id="edit-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... content from previous implementation ... -->
    </div>

    <!-- Modale di Conferma Eliminazione Contributo -->
    <div id="confirm-delete-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... content from previous implementation ... -->
    </div>

    <!-- Modale per Note (da transactions.php) -->
    <div id="note-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop" onclick="closeModal('note-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-4">Nota del Contributo</h2>
            <form id="note-form">
                <input type="hidden" id="note-transaction-id" name="transaction_id">
                <textarea id="note-content" name="note_content" rows="6" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Scrivi qui la tua nota..."></textarea>
                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('note-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Nota</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'toast_notification.php'; ?>

    <script>
    // --- GLOBAL HELPER & ONCLICK FUNCTIONS ---
    function openModal(modalId) { /* ... */ }
    function closeModal(modalId) { /* ... */ }
    function showToast(message, type = 'success') { /* ... */ }
    function escapeHTML(str) { /* ... */ }
    function openEditGoalModal(goal) { /* ... */ }
    function openContributionModal(goalId, goalName) { /* ... */ }
    function openNoteModal(transactionId) { /* ... */ }
    function openHistoryModal(goalId, goalName) { /* ... */ }
    function updateGoalCard(goalData) { /* ... */ }

    // --- DOM-DEPENDENT LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        // All event listeners for forms and delegated clicks go here
    });
    </script>
</body>
</html>
