<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$accounts = get_user_accounts($conn, $user_id); // Questa funzione ora deve restituire tutti i dati
$current_page = 'accounts';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conti - Bearget</title>
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
                            Conti
                        </h1>
                        <p class="text-gray-400 mt-1">Gestisci tutti i tuoi conti.</p>
                    </div>
                </div>
                <button onclick="openModal('add-account-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Aggiungi Conto
                </button>
            </header>

            <div id="accounts-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($accounts)): ?>
                    <div id="empty-state" class="md:col-span-2 lg:col-span-3 bg-gray-800 rounded-2xl p-10 text-center flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <h3 class="text-lg font-semibold text-white">Nessun conto trovato</h3>
                        <p class="text-gray-400 max-w-sm mx-auto mt-1">Inizia ad aggiungere i tuoi conti bancari, carte di credito o contanti per tracciare le tue finanze.</p>
                        <button onclick="openModal('add-account-modal')" class="mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Crea il tuo primo conto
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                        <div class="bg-gray-800 p-6 rounded-2xl flex flex-col justify-between transition-transform duration-200 hover:-translate-y-1" data-account-id="<?php echo $account['id']; ?>">
                            <div>
                                <h3 class="text-xl font-bold text-white account-name"><?php echo htmlspecialchars($account['name']); ?></h3>
                                <p class="text-2xl font-semibold text-gray-300 mt-1 account-balance">€<?php echo number_format(get_account_balance($conn, $account['id']), 2, ',', '.'); ?></p>
                            </div>
                            <div class="flex items-center justify-end space-x-2 mt-4">
                                <button onclick='openEditAccountModal(<?php echo json_encode($account); ?>)' title="Modifica Conto" class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-700 rounded-full transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                </button>
                                <form action="delete_account.php" method="POST" class="delete-form">
                                    <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                    <button type="submit" title="Elimina Conto" class="p-2 text-gray-400 hover:text-red-500 hover:bg-gray-700 rounded-full transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi Conto -->
    <div id="add-account-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-account-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <form id="add-account-form" action="add_account.php" method="POST">
                <h2 class="text-2xl font-bold text-white mb-6">Nuovo Conto</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Nome Conto</label>
                        <input type="text" name="name" id="name" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2" placeholder="Es. Conto Corrente">
                    </div>
                    <div>
                        <label for="initial_balance" class="block text-sm font-medium text-gray-300 mb-1">Saldo Iniziale</label>
                        <input type="number" step="0.01" name="initial_balance" id="initial_balance" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2" placeholder="0.00">
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-account-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Conto</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- NUOVA Modale Modifica Conto -->
    <div id="edit-account-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-account-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <form id="edit-account-form" action="update_account.php" method="POST">
                <input type="hidden" name="account_id" id="edit-account-id">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Modifica Conto</h2>
                    <span id="edit-account-prefixed-id" class="text-sm font-mono text-gray-400"></span>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-300 mb-1">Nome Conto</label>
                        <input type="text" name="name" id="edit-name" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="edit-initial_balance" class="block text-sm font-medium text-gray-300 mb-1">Saldo Iniziale</label>
                        <input type="number" step="0.01" name="initial_balance" id="edit-initial_balance" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-account-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
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
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Conto?</h3>
            <p class="mt-2 text-sm text-gray-400">Tutte le transazioni associate a questo conto verranno eliminate definitivamente. L'azione è irreversibile.</p>
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
            const addAccountForm = document.getElementById('add-account-form');
            const editAccountForm = document.getElementById('edit-account-form');
            const accountsGrid = document.getElementById('accounts-grid');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            // --- GESTIONE AGGIUNTA CONTO ---
            addAccountForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(addAccountForm);
                fetch(addAccountForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            addAccountToGrid(data.account);
                            addAccountForm.reset();
                            closeModal('add-account-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            // --- GESTIONE MODIFICA CONTO ---
            editAccountForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editAccountForm);
                fetch(editAccountForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateAccountInGrid(data.account);
                            closeModal('edit-account-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            // --- GESTIONE ELIMINAZIONE CONTO ---
            accountsGrid.addEventListener('submit', function(e) {
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
                    const card = formToDelete.closest('[data-account-id]');
                    fetch(formToDelete.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                card.classList.add('row-fade-out');
                                setTimeout(() => card.remove(), 500);
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
        function openEditAccountModal(account) {
            document.getElementById('edit-account-prefixed-id').textContent = 'ID: ACC-' + account.id;
            document.getElementById('edit-account-id').value = account.id;
            document.getElementById('edit-name').value = account.name;
            document.getElementById('edit-initial_balance').value = account.initial_balance;
            openModal('edit-account-modal');
        }

        function addAccountToGrid(account) {
            const emptyState = document.getElementById('empty-state');
            if (emptyState) emptyState.remove();

            const grid = document.getElementById('accounts-grid');
            const newCard = document.createElement('div');
            newCard.className = 'bg-gray-800 p-6 rounded-2xl flex flex-col justify-between transition-transform duration-200 hover:-translate-y-1';
            newCard.setAttribute('data-account-id', account.id);

            const formattedBalance = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(account.balance);

            newCard.innerHTML = `
                <div>
                    <h3 class="text-xl font-bold text-white account-name">${escapeHTML(account.name)}</h3>
                    <p class="text-2xl font-semibold text-gray-300 mt-1 account-balance">${formattedBalance}</p>
                </div>
                <div class="flex items-center justify-end space-x-2 mt-4">
                    <button onclick='openEditAccountModal(${JSON.stringify(account)})' title="Modifica Conto" class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-700 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                    </button>
                    <form action="delete_account.php" method="POST" class="delete-form">
                        <input type="hidden" name="account_id" value="${account.id}">
                        <button type="submit" title="Elimina Conto" class="p-2 text-gray-400 hover:text-red-500 hover:bg-gray-700 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        </button>
                    </form>
                </div>
            `;
            grid.appendChild(newCard);
        }

        function updateAccountInGrid(account) {
            const card = document.querySelector(`[data-account-id="${account.id}"]`);
            if (card) {
                card.querySelector('.account-name').textContent = escapeHTML(account.name);
                card.querySelector('.account-balance').textContent = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(account.balance);
                
                // Aggiorna anche i dati nel pulsante di modifica per la prossima apertura
                const editButton = card.querySelector('button[onclick^="openEditAccountModal"]');
                const updatedAccountData = {
                    id: account.id,
                    name: account.name,
                    initial_balance: account.initial_balance 
                };
                editButton.setAttribute('onclick', `openEditAccountModal(${JSON.stringify(updatedAccountData)})`);
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