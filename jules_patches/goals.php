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
                <!-- ... header content ... -->
            </header>

            <div id="goals-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Goal cards are rendered by PHP -->
            </div>
        </main>
    </div>

    <!-- All Modals -->
    <?php include 'modals/goals_modals.php'; ?>
    <?php include 'modals/notes_modal.php'; ?>

    <script>
    // --- GLOBAL HELPER FUNCTIONS ---
    function openModal(modalId) { /* ... */ }
    function closeModal(modalId) { /* ... */ }
    function showToast(message, type = 'success') { /* ... */ }
    function escapeHTML(str) { /* ... */ }

    // --- GLOBAL FUNCTIONS FOR ONCLICK ATTRIBUTES ---
    let currentOpenGoalId = null;

    function openEditGoalModal(goal) { /* ... */ }
    function openContributionModal(goalId, goalName) { /* ... */ }
    function openNoteModal(transactionId) { /* ... */ }

    function openHistoryModal(goalId, goalName) {
        currentOpenGoalId = goalId;
        const modal = document.getElementById('history-modal');
        document.getElementById('history-goal-name').textContent = goalName;
        const container = document.getElementById('history-list-container');
        const loadingEl = document.getElementById('history-loading');

        container.innerHTML = '';
        loadingEl.style.display = 'block';
        container.appendChild(loadingEl);
        openModal('history-modal');

        fetch(`ajax_get_goal_contributions.php?goal_id=${goalId}`)
            .then(res => res.json())
            .then(data => {
                loadingEl.style.display = 'none';
                if (data.success && data.contributions.length > 0) {
                    data.contributions.forEach(c => {
                        const date = new Date(c.transaction_date).toLocaleDateString('it-IT', { year: 'numeric', month: 'long', day: 'numeric' });
                        const amount = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(Math.abs(c.amount));

                        const historyItem = document.createElement('div');
                        historyItem.className = 'bg-gray-700/50 p-3 rounded-lg flex justify-between items-center contribution-item';
                        historyItem.setAttribute('data-transaction-id', c.id);
                        historyItem.setAttribute('data-amount', Math.abs(c.amount));
                        historyItem.setAttribute('data-date', c.transaction_date);
                        historyItem.innerHTML = `
                            <div>
                                <p class="font-bold text-white">${amount}</p>
                                <p class="text-sm text-gray-400">versato il ${date} dal conto '${escapeHTML(c.account_name)}'</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="openNoteModal(${c.id})" title="Aggiungi Nota" class="p-2 hover:bg-gray-600 rounded-full"><svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></button>
                                <button class="p-2 hover:bg-gray-600 rounded-full btn-edit-contribution"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg></button>
                                <button class="p-2 hover:bg-gray-600 rounded-full btn-delete-contribution"><svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </div>
                        `;
                        container.appendChild(historyItem);
                    });
                } else {
                    container.innerHTML = '<p class="text-center text-gray-400 p-8">Nessun contributo trovato.</p>';
                }
            })
            .catch(err => {
                loadingEl.style.display = 'none';
                container.innerHTML = '<p class="text-center text-red-400 p-8">Errore nel caricamento della cronologia.</p>';
            });
    }

    // --- DOM-DEPENDENT LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        // ... (all form and button event listeners go here) ...
    });
    </script>
</body>
</html>
