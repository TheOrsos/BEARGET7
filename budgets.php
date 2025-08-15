<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi ai budget con un piano Premium!");
    exit;
}

$budgets = get_user_budgets($conn, $user_id);
$availableCategories = get_spend_categories_without_budget($conn, $user_id);
$view = $_GET['view'] ?? 'list';
$current_page = 'budgets';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget - Bearget</title>
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
                            Budget mensile
                        </h1>
                        <p class="text-gray-400 mt-1">Imposta e monitora i tuoi limiti di spesa.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center bg-gray-800 rounded-lg p-1">
                        <a href="?view=list" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $view === 'list' ? 'bg-primary-600 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>">Lista</a>
                        <a href="?view=graph" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $view === 'graph' ? 'bg-primary-600 text-white' : 'text-gray-400 hover:bg-gray-700'; ?>">Grafico</a>
                    </div>
                    <button onclick="openModal('add-budget-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Nuovo Budget
                    </button>
                </div>
            </header>

            <div id="list-view" class="space-y-4 <?php echo $view !== 'list' ? 'hidden' : ''; ?>">
                <?php if (empty($budgets)): ?>
                    <div id="empty-state-budgets" class="bg-gray-800 rounded-2xl p-10 text-center flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <h3 class="text-lg font-semibold text-white">Nessun budget impostato</h3>
                        <p class="text-gray-400 max-w-sm mx-auto mt-1">Inizia a monitorare le tue spese per una categoria specifica per avere un maggiore controllo.</p>
                        <button onclick="openModal('add-budget-modal')" class="mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Crea il tuo primo budget
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($budgets as $budget): 
                        $spent_amount = $budget['spent'];
                        $budget_amount = $budget['amount'];
                        $percentage = ($budget_amount > 0) ? ($spent_amount / $budget_amount) * 100 : 0;
                        $is_over_budget = $spent_amount > $budget_amount;
                        
                        $progressBarColor = 'bg-primary-500';
                        if ($percentage > 100) $progressBarColor = 'bg-danger';
                        elseif ($percentage > 90) $progressBarColor = 'bg-warning';
                    ?>
                    <div class="bg-gray-800 rounded-2xl p-5 transition-transform duration-200 hover:-translate-y-1" data-budget-id="<?php echo $budget['id']; ?>">
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center">
                                <span class="text-xl mr-3"><?php echo htmlspecialchars($budget['icon'] ?? '💸'); ?></span>
                                <span class="font-bold text-white category-name"><?php echo htmlspecialchars($budget['category_name']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick='openEditBudgetModal(<?php echo json_encode($budget); ?>)' title="Modifica Budget" class="p-1 text-gray-400 hover:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                </button>
                                <form action="delete_budget.php" method="POST" class="delete-form">
                                    <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                    <button type="submit" title="Elimina Budget" class="p-1 text-gray-500 hover:text-red-400">&times;</button>
                                </form>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-400 mb-1">
                            <span class="spent-text <?php echo $is_over_budget ? 'text-danger font-bold' : ''; ?>">€<?php echo number_format($spent_amount, 2, ',', '.'); ?> spesi</span>
                            <span class="target-amount">di €<?php echo number_format($budget_amount, 2, ',', '.'); ?></span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2.5">
                            <div class="progress-bar <?php echo $progressBarColor; ?> h-2.5 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="graph-view" class="bg-gray-800 rounded-2xl p-6 <?php echo $view !== 'graph' ? 'hidden' : ''; ?>">
                <h2 class="text-xl font-bold text-white mb-4">Confronto Budget vs. Speso</h2>
                <div class="h-96"><canvas id="budgetChart"></canvas></div>
            </div>
        </main>
    </div>

    <!-- MODALE AGGIUNGI BUDGET -->
    <div id="add-budget-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-budget-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-4">Imposta Nuovo Budget</h2>
            <form id="add-budget-form" action="add_budget.php" method="POST" class="space-y-4">
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-300 mb-1">Categoria</label>
                    <select name="category_id" id="category_id" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                        <?php if (empty($availableCategories)): ?>
                            <option disabled>Tutte le categorie hanno un budget</option>
                        <?php else: ?>
                            <?php foreach($availableCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-300 mb-1">Limite di Spesa (€)</label>
                    <input type="number" step="0.01" name="amount" id="amount" required placeholder="Es. 150.00" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-budget-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-colors" <?php if (empty($availableCategories)) echo 'disabled'; ?>>
                        Salva Budget
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- NUOVA MODALE MODIFICA BUDGET -->
    <div id="edit-budget-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-budget-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-white">Modifica Budget</h2>
                <span id="edit-budget-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <p id="edit-budget-category-name" class="text-gray-400 mb-6"></p>
            <form id="edit-budget-form" action="update_budget.php" method="POST" class="space-y-4">
                <input type="hidden" name="budget_id" id="edit-budget-id">
                <div>
                    <label for="edit-amount" class="block text-sm font-medium text-gray-300 mb-1">Nuovo Limite di Spesa (€)</label>
                    <input type="number" step="0.01" name="amount" id="edit-amount" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-budget-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-colors">
                        Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODALE DI CONFERMA ELIMINAZIONE -->
    <div id="confirm-delete-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-60 opacity-0 modal-backdrop" onclick="closeModal('confirm-delete-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900">
                <svg class="h-6 w-6 text-red-400" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Budget?</h3>
            <p class="mt-2 text-sm text-gray-400">L'azione è irreversibile. Sei sicuro?</p>
            <div class="mt-8 flex justify-center space-x-4">
                <button id="confirm-delete-btn" type="button" class="bg-danger hover:bg-red-700 text-white font-semibold py-2 px-5 rounded-lg">Elimina</button>
                <button type="button" onclick="closeModal('confirm-delete-modal')" class="bg-gray-700 hover:bg-gray-600 text-gray-300 font-semibold py-2 px-5 rounded-lg">Annulla</button>
            </div>
        </div>
    </div>

    <script>
        // --- FUNZIONI DI BASE (MODALI, TOAST, ESCAPE) ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                if(backdrop) backdrop.classList.remove('opacity-0');
                if(content) content.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            if(backdrop) backdrop.classList.add('opacity-0');
            if(content) content.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            if (!toast) return;
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');
            toastMessage.textContent = message;
            toast.classList.remove('bg-success', 'bg-danger');
            if (type === 'success') {
                toast.classList.add('bg-success');
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>`;
            } else {
                toast.classList.add('bg-danger');
                toastIcon.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"></path></svg>`;
            }
            toast.classList.remove('hidden', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 5000);
        }
        
        function escapeHTML(str) { const div = document.createElement('div'); div.textContent = str; return div.innerHTML; }

        document.addEventListener('DOMContentLoaded', function() {
            const addBudgetForm = document.getElementById('add-budget-form');
            const editBudgetForm = document.getElementById('edit-budget-form');
            const budgetListContainer = document.getElementById('list-view');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            addBudgetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(addBudgetForm);
                fetch(addBudgetForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            addBudgetToUI(data.budget);
                            const categoryOption = document.querySelector(`#category_id option[value="${data.budget.category_id}"]`);
                            if(categoryOption) categoryOption.remove();
                            addBudgetForm.reset();
                            closeModal('add-budget-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            editBudgetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editBudgetForm);
                fetch(editBudgetForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateBudgetInUI(data.budget);
                            closeModal('edit-budget-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            budgetListContainer.addEventListener('submit', function(e) {
                const form = e.target.closest('.delete-form');
                if (form) {
                    e.preventDefault();
                    formToDelete = form;
                    openModal('confirm-delete-modal');
                }
            });

            confirmDeleteBtn.addEventListener('click', function() {
                if (formToDelete) {
                    const formData = new FormData(formToDelete);
                    const card = formToDelete.closest('[data-budget-id]');
                    fetch(formToDelete.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                card.classList.add('row-fade-out');
                                setTimeout(() => {
                                    card.remove();
                                    if (budgetListContainer.children.length === 0 || (budgetListContainer.children.length === 1 && budgetListContainer.querySelector('#empty-state-budgets'))) {
                                        budgetListContainer.innerHTML = `<div id="empty-state-budgets" class="bg-gray-800 rounded-2xl p-10 text-center flex flex-col items-center">...</div>`;
                                    }
                                }, 500);
                            } else {
                                showToast(data.message, 'error');
                            }
                        })
                        .catch(err => showToast('Errore di rete.', 'error'))
                        .finally(() => {
                            closeModal('confirm-delete-modal');
                            formToDelete = null;
                        });
                }
            });

            function addBudgetToUI(budget) {
                const emptyState = document.getElementById('empty-state-budgets');
                if (emptyState) emptyState.remove();

                const newCard = document.createElement('div');
                newCard.className = 'bg-gray-800 rounded-2xl p-5 transition-transform duration-200 hover:-translate-y-1';
                newCard.setAttribute('data-budget-id', budget.id);

                const formattedAmount = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(budget.amount);

                newCard.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center">
                            <span class="text-xl mr-3">${escapeHTML(budget.icon)}</span>
                            <span class="font-bold text-white category-name">${escapeHTML(budget.category_name)}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick='openEditBudgetModal(${JSON.stringify(budget)})' title="Modifica Budget" class="p-1 text-gray-400 hover:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                            </button>
                            <form action="delete_budget.php" method="POST" class="delete-form">
                                <input type="hidden" name="budget_id" value="${budget.id}">
                                <button type="submit" title="Elimina Budget" class="p-1 text-gray-500 hover:text-red-400">&times;</button>
                            </form>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-400 mb-1">
                        <span class="spent-text">€0,00 spesi</span>
                        <span class="target-amount">di ${formattedAmount}</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2.5">
                        <div class="progress-bar bg-primary-500 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                `;
                budgetListContainer.appendChild(newCard);
            }

            function updateBudgetInUI(budgetData) {
                const card = document.querySelector(`[data-budget-id="${budgetData.id}"]`);
                if (card) {
                    const targetAmountEl = card.querySelector('.target-amount');
                    const spentTextEl = card.querySelector('.spent-text');
                    const progressBarEl = card.querySelector('.progress-bar');

                    const spentAmount = parseFloat(spentTextEl.textContent.replace(/[^0-9,.-]/g, '').replace('.', '').replace(',', '.'));
                    const newBudgetAmount = budgetData.amount;
                    
                    targetAmountEl.textContent = 'di ' + new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(newBudgetAmount);

                    const percentage = (newBudgetAmount > 0) ? (spentAmount / newBudgetAmount) * 100 : 0;
                    
                    progressBarEl.style.width = `${Math.min(percentage, 100)}%`;
                    
                    progressBarEl.classList.remove('bg-primary-500', 'bg-warning', 'bg-danger');
                    spentTextEl.classList.remove('text-danger', 'font-bold');

                    if (percentage > 100) {
                        progressBarEl.classList.add('bg-danger');
                        spentTextEl.classList.add('text-danger', 'font-bold');
                    } else if (percentage > 90) {
                        progressBarEl.classList.add('bg-warning');
                    } else {
                        progressBarEl.classList.add('bg-primary-500');
                    }
                }
            }
        });

        function openEditBudgetModal(budget) {
            document.getElementById('edit-budget-prefixed-id').textContent = 'ID: BUD-' + budget.id;
            document.getElementById('edit-budget-id').value = budget.id;
            document.getElementById('edit-amount').value = budget.amount;
            document.getElementById('edit-budget-category-name').textContent = `per la categoria "${escapeHTML(budget.category_name)}"`;
            openModal('edit-budget-modal');
        }

        // --- Logica per il grafico (invariata) ---
        const budgetData = <?php echo json_encode($budgets); ?>;
        const labels = budgetData.map(b => b.category_name);
        const budgetAmounts = budgetData.map(b => b.amount);
        const spentAmounts = budgetData.map(b => b.spent);
        const ctx = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Speso',
                        data: spentAmounts,
                        backgroundColor: 'var(--color-primary-500)',
                        borderColor: 'var(--color-primary-500)',
                        borderWidth: 1
                    },
                    {
                        label: 'Budget',
                        data: budgetAmounts,
                        backgroundColor: 'var(--color-gray-700)',
                        borderColor: 'var(--color-gray-700)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                scales: {
                    x: { ticks: { color: 'var(--color-gray-400)' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                    y: { ticks: { color: 'var(--color-gray-300)' }, grid: { display: false } }
                },
                plugins: {
                    legend: { labels: { color: 'var(--color-gray-300)' } }
                }
            }
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