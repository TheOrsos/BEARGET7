<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$current_page = 'recurring';

// CONTROLLO ACCESSO PRO
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi alle transazioni ricorrenti con un piano Premium!");
    exit;
}

$recurring_transactions = get_recurring_transactions($conn, $user_id);
$accounts = get_user_accounts($conn, $user_id);
$expenseCategories = get_user_categories($conn, $user_id, 'expense');
$incomeCategories = get_user_categories($conn, $user_id, 'income');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transazioni Ricorrenti - Bearget</title>
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
                            Transazioni Ricorrenti
                        </h1>
                        <p class="text-gray-400 mt-1">Automatizza le tue entrate e uscite regolari.</p>
                    </div>
                </div>
                <button onclick="openAddRecurringModal()" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Aggiungi
                </button>
            </header>

            <div class="bg-gray-800 rounded-2xl p-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-sm text-gray-400 uppercase">
                            <tr>
                                <th class="p-4">Descrizione</th>
                                <th class="p-4">Prossima Data</th>
                                <th class="p-4">Frequenza</th>
                                <th class="p-4 text-right">Importo</th>
                                <th class="p-4 text-center">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="recurring-table-body" class="text-white">
                            <?php if (empty($recurring_transactions)): ?>
                                <tr id="empty-state-recurring"><td colspan="5" class="text-center p-10">
                                    <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5m11 2a9 9 0 11-2.93-6.93"></path></svg>
                                    <h3 class="mt-2 text-sm font-medium text-white">Nessuna transazione ricorrente</h3>
                                    <p class="mt-1 text-sm text-gray-500">Aggiungi uno stipendio, un abbonamento o altre spese fisse.</p>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($recurring_transactions as $tx): ?>
                                <tr class="border-b border-gray-700 last:border-b-0 transition-colors hover:bg-gray-700/50" data-recurring-id="<?php echo $tx['id']; ?>">
                                    <td class="p-4 font-semibold description-cell"><?php echo htmlspecialchars($tx['description']); ?></td>
                                    <td class="p-4 date-cell"><?php echo date("d/m/Y", strtotime($tx['next_due_date'])); ?></td>
                                    <td class="p-4 capitalize frequency-cell"><?php echo htmlspecialchars($tx['frequency']); ?></td>
                                    <td class="p-4 text-right font-bold amount-cell <?php echo $tx['type'] == 'income' ? 'text-green-400' : 'text-red-400'; ?>">
                                        <?php echo ($tx['type'] == 'income' ? '+' : '-') . '€' . number_format(abs($tx['amount']), 2, ',', '.'); ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <button onclick='openEditRecurringModal(<?php echo json_encode($tx); ?>)' title="Modifica" class="p-2 hover:bg-gray-700 rounded-full">
                                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                            </button>
                                            <form action="delete_recurring.php" method="POST" class="delete-form">
                                                <input type="hidden" name="recurring_id" value="<?php echo $tx['id']; ?>">
                                                <button type="submit" title="Elimina" class="p-2 hover:bg-gray-700 rounded-full">
                                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi/Modifica Transazione Ricorrente -->
    <div id="recurring-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('recurring-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 id="recurring-modal-title" class="text-2xl font-bold text-white">Nuova Transazione Ricorrente</h2>
                <span id="recurring-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <form id="recurring-form" action="" method="POST" class="space-y-4">
                <input type="hidden" name="recurring_id" id="recurring-id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Descrizione</label>
                        <input type="text" name="description" id="recurring-description" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Importo (€)</label>
                        <input type="number" step="0.01" name="amount" id="recurring-amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Categoria</label>
                        <select name="category_id" id="recurring-category" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <optgroup label="Spese"><?php foreach($expenseCategories as $cat){ echo "<option value='{$cat['id']}'>{$cat['name']}</option>"; } ?></optgroup>
                            <optgroup label="Entrate"><?php foreach($incomeCategories as $cat){ echo "<option value='{$cat['id']}'>{$cat['name']}</option>"; } ?></optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Conto</label>
                        <select name="account_id" id="recurring-account" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <?php foreach($accounts as $acc){ echo "<option value='{$acc['id']}'>{$acc['name']}</option>"; } ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Frequenza</label>
                        <select name="frequency" id="recurring-frequency" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <option value="weekly">Settimanale</option>
                            <option value="monthly">Mensile</option>
                            <option value="bimonthly">Bimensile</option>
                            <option value="yearly">Annuale</option>
                        </select>
                    </div>
                    <div id="start-date-container">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data di Inizio</label>
                        <input type="date" name="start_date" id="recurring-start-date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div id="next-date-container" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Prossima Data</label>
                        <input type="date" name="next_due_date" id="recurring-next-date" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeModal('recurring-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" id="recurring-submit-btn" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale di Conferma Eliminazione -->
    <div id="confirm-delete-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-60 opacity-0 modal-backdrop" onclick="closeModal('confirm-delete-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900">
                <svg class="h-6 w-6 text-red-400" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Transazione?</h3>
            <p class="mt-2 text-sm text-gray-400">Sei sicuro di voler eliminare questa transazione ricorrente?</p>
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
            const recurringForm = document.getElementById('recurring-form');
            const tableBody = document.getElementById('recurring-table-body');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            // --- GESTIONE AGGIUNTA E MODIFICA ---
            recurringForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(recurringForm);
                fetch(recurringForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            if (formData.get('recurring_id')) { // Se è una modifica
                                updateRecurringInUI(data.transaction);
                            } else { // Se è un'aggiunta
                                addRecurringToUI(data.transaction);
                            }
                            closeModal('recurring-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            // --- GESTIONE ELIMINAZIONE ---
            tableBody.addEventListener('submit', function(e) {
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
                    const row = formToDelete.closest('tr');
                    fetch(formToDelete.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                row.classList.add('row-fade-out');
                                setTimeout(() => {
                                    row.remove();
                                    if (tableBody.getElementsByTagName('tr').length === 0) {
                                        tableBody.innerHTML = `<tr id="empty-state-recurring"><td colspan="5" class="text-center p-10"><svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5m11 2a9 9 0 11-2.93-6.93"></path></svg><h3 class="mt-2 text-sm font-medium text-white">Nessuna transazione ricorrente</h3><p class="mt-1 text-sm text-gray-500">Aggiungi uno stipendio, un abbonamento o altre spese fisse.</p></td></tr>`;
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
        });

        // --- FUNZIONI PER MANIPOLARE LA UI ---
        
        function openAddRecurringModal() {
            const form = document.getElementById('recurring-form');
            form.reset();
            form.action = 'add_recurring.php';
            document.getElementById('recurring-modal-title').textContent = 'Nuova Transazione Ricorrente';
            document.getElementById('recurring-prefixed-id').textContent = '';
            document.getElementById('recurring-id').value = '';
            document.getElementById('start-date-container').classList.remove('hidden');
            document.getElementById('next-date-container').classList.add('hidden');
            document.getElementById('recurring-submit-btn').textContent = 'Salva';
            document.getElementById('recurring-submit-btn').classList.remove('bg-green-600', 'hover:bg-green-700');
            document.getElementById('recurring-submit-btn').classList.add('bg-primary-600', 'hover:bg-primary-700');
            openModal('recurring-modal');
        }

        function openEditRecurringModal(tx) {
            const form = document.getElementById('recurring-form');
            form.reset();
            form.action = 'update_recurring.php';
            document.getElementById('recurring-modal-title').textContent = 'Modifica Transazione Ricorrente';
            document.getElementById('recurring-prefixed-id').textContent = 'ID: REC-' + tx.id;
            document.getElementById('recurring-id').value = tx.id;
            document.getElementById('recurring-description').value = tx.description;
            document.getElementById('recurring-amount').value = tx.amount;
            document.getElementById('recurring-category').value = tx.category_id;
            document.getElementById('recurring-account').value = tx.account_id;
            document.getElementById('recurring-frequency').value = tx.frequency;
            document.getElementById('recurring-next-date').value = tx.next_due_date;
            
            document.getElementById('start-date-container').classList.add('hidden');
            document.getElementById('next-date-container').classList.remove('hidden');
            document.getElementById('recurring-submit-btn').textContent = 'Salva Modifiche';
            document.getElementById('recurring-submit-btn').classList.add('bg-green-600', 'hover:bg-green-700');
            document.getElementById('recurring-submit-btn').classList.remove('bg-primary-600', 'hover:bg-primary-700');
            openModal('recurring-modal');
        }

        function addRecurringToUI(tx) {
            const emptyState = document.getElementById('empty-state-recurring');
            if (emptyState) emptyState.remove();

            const tableBody = document.getElementById('recurring-table-body');
            const newRow = document.createElement('tr');
            newRow.className = 'border-b border-gray-700 last:border-b-0 transition-colors hover:bg-gray-700/50';
            newRow.setAttribute('data-recurring-id', tx.id);

            const formattedAmount = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(tx.amount);
            const formattedDate = new Date(tx.next_due_date + 'T00:00:00').toLocaleDateString('it-IT');
            const amountClass = tx.type === 'income' ? 'text-green-400' : 'text-red-400';
            const amountSign = tx.type === 'income' ? '+' : '-';

            newRow.innerHTML = `
                <td class="p-4 font-semibold description-cell">${escapeHTML(tx.description)}</td>
                <td class="p-4 date-cell">${formattedDate}</td>
                <td class="p-4 capitalize frequency-cell">${escapeHTML(tx.frequency)}</td>
                <td class="p-4 text-right font-bold amount-cell ${amountClass}">${amountSign}${formattedAmount}</td>
                <td class="p-4 text-center">
                    <div class="flex justify-center items-center space-x-2">
                        <button onclick='openEditRecurringModal(${JSON.stringify(tx)})' title="Modifica" class="p-2 hover:bg-gray-700 rounded-full">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                        </button>
                        <form action="delete_recurring.php" method="POST" class="delete-form">
                            <input type="hidden" name="recurring_id" value="${tx.id}">
                            <button type="submit" title="Elimina" class="p-2 hover:bg-gray-700 rounded-full">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </td>
            `;
            tableBody.appendChild(newRow);
        }

        function updateRecurringInUI(tx) {
            const row = document.querySelector(`tr[data-recurring-id="${tx.id}"]`);
            if (row) {
                const formattedAmount = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(tx.amount);
                const formattedDate = new Date(tx.next_due_date + 'T00:00:00').toLocaleDateString('it-IT');
                const amountCell = row.querySelector('.amount-cell');
                
                row.querySelector('.description-cell').textContent = escapeHTML(tx.description);
                row.querySelector('.date-cell').textContent = formattedDate;
                row.querySelector('.frequency-cell').textContent = escapeHTML(tx.frequency);
                amountCell.textContent = (tx.type === 'income' ? '+' : '-') + formattedAmount;
                amountCell.className = `p-4 text-right font-bold amount-cell ${tx.type === 'income' ? 'text-green-400' : 'text-red-400'}`;
                
                const editButton = row.querySelector('button[onclick^="openEditRecurringModal"]');
                if(editButton) {
                    // Ricostruisci l'oggetto completo per il prossimo click
                    const fullTxData = {
                        id: tx.id,
                        description: tx.description,
                        amount: tx.amount,
                        type: tx.type,
                        category_id: tx.category_id,
                        account_id: tx.account_id,
                        frequency: tx.frequency,
                        next_due_date: tx.next_due_date
                    };
                    editButton.setAttribute('onclick', `openEditRecurringModal(${JSON.stringify(fullTxData)})`);
                }
            }
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
</body>
</html>
