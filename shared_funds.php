<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$current_page = 'shared_funds';

$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi ai fondi comuni con un piano Premium!");
    exit;
}

$shared_funds = get_shared_funds_for_user($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fondi Comuni - Bearget</title>
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
<body class="text-gray-300">
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
                            Fondi Comuni
                        </h1>
                        <p class="text-gray-400 mt-1">Organizza le spese di gruppo con i tuoi amici.</p>
                    </div>
                </div>
                <button onclick="openModal('add-fund-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Crea Fondo
                </button>
            </header>

            <div id="funds-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (empty($shared_funds)): ?>
                    <div id="empty-state-funds" class="md:col-span-2 xl:col-span-3 bg-gray-800 rounded-2xl p-10 text-center flex flex-col items-center">
                        <svg class="w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <h3 class="text-lg font-semibold text-white">Non fai parte di nessun fondo comune</h3>
                        <p class="text-gray-400 max-w-sm mx-auto mt-1">Crea un fondo per raccogliere soldi per una vacanza, un regalo o qualsiasi spesa di gruppo!</p>
                        <button onclick="openModal('add-fund-modal')" class="mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Crea il tuo primo fondo
                        </button>
                    </div>
                <?php else: foreach ($shared_funds as $fund): 
                    $percentage = ($fund['target_amount'] > 0) ? ($fund['total_contributed'] / $fund['target_amount']) * 100 : 0;
                ?>
                <div class="bg-gray-800 rounded-2xl p-5 flex flex-col transition-transform duration-200 hover:-translate-y-1 hover:shadow-2xl" data-fund-id="<?php echo $fund['id']; ?>">
                    <div class="flex-grow">
                        <div class="flex justify-between items-start">
                             <h3 class="text-xl font-bold text-white mb-2 fund-name"><?php echo htmlspecialchars($fund['name']); ?></h3>
                             <?php if ($fund['creator_id'] == $user_id): ?>
                                <div class="flex items-center space-x-1">
                                    <button onclick='openEditFundModal(<?php echo json_encode($fund); ?>)' title="Modifica Fondo" class="p-2 text-gray-400 hover:text-blue-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg></button>
                                    <form action="delete_shared_fund.php" method="POST" class="delete-form">
                                        <input type="hidden" name="fund_id" value="<?php echo $fund['id']; ?>">
                                        <button type="submit" title="Elimina Fondo" class="p-2 text-gray-400 hover:text-red-400">&times;</button>
                                    </form>
                                </div>
                             <?php endif; ?>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-300">
                            <span class="total-contributed">€<?php echo number_format($fund['total_contributed'], 2, ',', '.'); ?></span>
                            <span class="target-amount text-gray-400">di €<?php echo number_format($fund['target_amount'], 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <a href="fund_details.php?id=<?php echo $fund['id']; ?>" class="w-full text-center block bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 rounded-lg transition-colors">Visualizza Dettagli</a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modale Aggiungi Fondo Comune -->
    <div id="add-fund-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-fund-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-6">Crea Nuovo Fondo Comune</h2>
            <form id="add-fund-form" action="add_shared_fund.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nome del Fondo</label>
                    <input type="text" name="name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Es. Vacanza in Sicilia">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Obiettivo di Raccolta (€)</label>
                    <input type="number" step="0.01" name="target_amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Es. 1000.00">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-fund-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Crea</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NUOVA Modale Modifica Fondo Comune -->
    <div id="edit-fund-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-fund-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Modifica Fondo Comune</h2>
                <span id="edit-fund-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <form id="edit-fund-form" action="update_shared_fund.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit-fund-id" name="fund_id">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nome del Fondo</label>
                    <input type="text" id="edit-fund-name" name="name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Obiettivo di Raccolta (€)</label>
                    <input type="number" step="0.01" id="edit-fund-target" name="target_amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-fund-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
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
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Fondo Comune?</h3>
            <p class="mt-2 text-sm text-gray-400">Questa azione è irreversibile e cancellerà anche tutti i contributi e i membri associati.</p>
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

        const currentUserId = <?php echo json_encode($user_id); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const addFundForm = document.getElementById('add-fund-form');
            const editFundForm = document.getElementById('edit-fund-form');
            const fundsGrid = document.getElementById('funds-grid');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            addFundForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(addFundForm);
                fetch(addFundForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            addFundToGrid(data.fund);
                            addFundForm.reset();
                            closeModal('add-fund-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            editFundForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editFundForm);
                fetch(editFundForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateFundInUI(data.fund);
                            closeModal('edit-fund-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            fundsGrid.addEventListener('submit', function(e) {
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
                    const card = formToDelete.closest('[data-fund-id]');
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

        function addFundToGrid(fund) {
            const emptyState = document.getElementById('empty-state-funds');
            if (emptyState) emptyState.remove();

            const grid = document.getElementById('funds-grid');
            const newFundCard = document.createElement('div');
            newFundCard.className = 'bg-gray-800 rounded-2xl p-5 flex flex-col transition-transform duration-200 hover:-translate-y-1 hover:shadow-2xl';
            newFundCard.setAttribute('data-fund-id', fund.id);

            const formattedTarget = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(fund.target_amount);
            const formattedContributed = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(fund.total_contributed);

            let adminButtons = '';
            if (fund.creator_id === currentUserId) {
                adminButtons = `
                    <div class="flex items-center space-x-1">
                        <button onclick='openEditFundModal(${JSON.stringify(fund)})' title="Modifica Fondo" class="p-2 text-gray-400 hover:text-blue-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg></button>
                        <form action="delete_shared_fund.php" method="POST" class="delete-form">
                            <input type="hidden" name="fund_id" value="${fund.id}">
                            <button type="submit" title="Elimina Fondo" class="p-2 text-gray-400 hover:text-red-400">&times;</button>
                        </form>
                    </div>
                `;
            }

            newFundCard.innerHTML = `
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                         <h3 class="text-xl font-bold text-white mb-2 fund-name">${escapeHTML(fund.name)}</h3>
                         ${adminButtons}
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                        <div class="bg-green-500 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-300">
                        <span class="total-contributed">${formattedContributed}</span>
                        <span class="target-amount text-gray-400">di ${formattedTarget}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <a href="fund_details.php?id=${fund.id}" class="w-full text-center block bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 rounded-lg transition-colors">Visualizza Dettagli</a>
                </div>
            `;
            grid.appendChild(newFundCard);
        }

        function openEditFundModal(fund) {
            document.getElementById('edit-fund-prefixed-id').textContent = 'ID: FUND-' + fund.id;
            document.getElementById('edit-fund-id').value = fund.id;
            document.getElementById('edit-fund-name').value = fund.name;
            document.getElementById('edit-fund-target').value = fund.target_amount;
            openModal('edit-fund-modal');
        }

        function updateFundInUI(fund) {
            const card = document.querySelector(`[data-fund-id="${fund.id}"]`);
            if (card) {
                card.querySelector('.fund-name').textContent = escapeHTML(fund.name);
                card.querySelector('.target-amount').textContent = 'di ' + new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(fund.target_amount);
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