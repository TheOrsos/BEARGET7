<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$expenseCategories = get_user_categories($conn, $user_id, 'expense');
$incomeCategories = get_user_categories($conn, $user_id, 'income');

$current_page = 'categories';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Categorie - Bearget</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="theme.php">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
        .sortable-ghost { opacity: 0.4; background: var(--color-primary-600); }
        .handle { cursor: grab; }
        .handle:active { cursor: grabbing; }
    </style>
</head>
<body class="text-gray-300">
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
                            Categorie
                        </h1>
                        <p class="text-gray-400 mt-1">Categorie</p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Categorie di Spesa -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Categorie di Spesa</h2>
                    <div id="expense-list-container" class="space-y-3 mb-6">
                        <?php foreach($expenseCategories as $cat): ?>
                            <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg category-item transition-shadow hover:shadow-lg" data-id="<?php echo $cat['id']; ?>">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-gray-500 handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    <span class="text-xl mr-3 category-icon"><?php echo htmlspecialchars($cat['icon'] ?? '💸'); ?></span>
                                    <span class="font-semibold category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick='openEditCategoryModal(<?php echo json_encode($cat); ?>)' title="Modifica" class="p-1 text-gray-400 hover:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                    </button>
                                    <form action="delete_category.php" method="POST" class="delete-form">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit" class="p-1 text-gray-500 hover:text-red-400 transition-colors">&times;</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form action="add_category.php" method="POST" class="add-form space-y-3">
                        <input type="hidden" name="type" value="expense">
                        <h3 class="font-semibold text-white">Aggiungi Nuova Categoria di Spesa</h3>
                        <div class="flex gap-3">
                            <input type="text" name="name" placeholder="Nome Categoria" required class="flex-grow bg-gray-900 border-gray-700 text-white rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                            <input type="text" name="icon" placeholder="Icona" maxlength="2" class="w-24 bg-gray-900 border-gray-700 text-white rounded-lg px-3 py-2 text-center focus:ring-primary-500 focus:border-primary-500">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">Aggiungi</button>
                        </div>
                    </form>
                </div>

                <!-- Categorie di Entrata -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Categorie di Entrata</h2>
                    <div id="income-list-container" class="space-y-3 mb-6">
                        <?php foreach($incomeCategories as $cat): ?>
                             <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg category-item transition-shadow hover:shadow-lg" data-id="<?php echo $cat['id']; ?>">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-gray-500 handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    <span class="text-xl mr-3 category-icon"><?php echo htmlspecialchars($cat['icon'] ?? '💰'); ?></span>
                                    <span class="font-semibold category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick='openEditCategoryModal(<?php echo json_encode($cat); ?>)' title="Modifica" class="p-1 text-gray-400 hover:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                    </button>
                                    <form action="delete_category.php" method="POST" class="delete-form">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit" class="p-1 text-gray-500 hover:text-red-400 transition-colors">&times;</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form action="add_category.php" method="POST" class="add-form space-y-3">
                        <input type="hidden" name="type" value="income">
                        <h3 class="font-semibold text-white">Aggiungi Nuova Categoria di Entrata</h3>
                        <div class="flex gap-3">
                            <input type="text" name="name" placeholder="Nome Categoria" required class="flex-grow bg-gray-900 border-gray-700 text-white rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500">
                            <input type="text" name="icon" placeholder="Icona" maxlength="2" class="w-24 bg-gray-900 border-gray-700 text-white rounded-lg px-3 py-2 text-center focus:ring-primary-500 focus:border-primary-500">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">Aggiungi</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- NUOVA MODALE DI MODIFICA -->
    <div id="edit-category-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-category-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Modifica Categoria</h2>
                <span id="edit-category-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <form id="edit-category-form" action="update_category.php" method="POST" class="space-y-4">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div>
                    <label for="edit-category-name" class="block text-sm font-medium text-gray-300 mb-1">Nome Categoria</label>
                    <input type="text" name="name" id="edit-category-name" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label for="edit-category-icon" class="block text-sm font-medium text-gray-300 mb-1">Icona (Emoji)</label>
                    <input type="text" name="icon" id="edit-category-icon" maxlength="2" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-category-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
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
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Categoria?</h3>
            <p class="mt-2 text-sm text-gray-400">Le transazioni associate non verranno eliminate, ma rimarranno senza categoria.</p>
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
            const editCategoryForm = document.getElementById('edit-category-form');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            // --- GESTIONE AGGIUNTA CATEGORIA ---
            document.querySelectorAll('.add-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    fetch(form.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                addCategoryToUI(data.category);
                                form.reset();
                            } else {
                                showToast(data.message, 'error');
                            }
                        }).catch(err => showToast('Errore di rete.', 'error'));
                });
            });

            // --- GESTIONE MODIFICA CATEGORIA ---
            editCategoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editCategoryForm);
                fetch(editCategoryForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateCategoryInUI(data.category);
                            closeModal('edit-category-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            // --- GESTIONE ELIMINAZIONE CATEGORIA ---
            document.body.addEventListener('submit', function(e) {
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
                    const row = formToDelete.closest('.category-item');
                    fetch(formToDelete.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                row.classList.add('row-fade-out');
                                setTimeout(() => row.remove(), 500);
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
            
            // --- GESTIONE DRAG & DROP (SortableJS) ---
            const expenseList = document.getElementById('expense-list-container');
            const incomeList = document.getElementById('income-list-container');

            function initSortable(listElement) {
                new Sortable(listElement, {
                    handle: '.handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function (evt) {
                        const orderedIds = [];
                        document.querySelectorAll('.category-item').forEach(item => {
                            orderedIds.push(item.dataset.id);
                        });
                        fetch('update_category_order.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order: orderedIds })
                        });
                    }
                });
            }
            initSortable(expenseList);
            initSortable(incomeList);
        });

        // --- FUNZIONI PER MANIPOLARE LA UI ---
        function openEditCategoryModal(category) {
            document.getElementById('edit-category-prefixed-id').textContent = 'ID: CAT-' + category.id;
            document.getElementById('edit-category-id').value = category.id;
            document.getElementById('edit-category-name').value = category.name;
            document.getElementById('edit-category-icon').value = category.icon;
            openModal('edit-category-modal');
        }

        function addCategoryToUI(category) {
            const containerId = category.type === 'expense' ? 'expense-list-container' : 'income-list-container';
            const container = document.getElementById(containerId);

            const newEl = document.createElement('div');
            newEl.className = 'flex items-center justify-between bg-gray-700 p-3 rounded-lg category-item transition-shadow hover:shadow-lg';
            newEl.setAttribute('data-id', category.id);
            newEl.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-gray-500 handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <span class="text-xl mr-3 category-icon">${escapeHTML(category.icon) || (category.type === 'expense' ? '💸' : '💰')}</span>
                    <span class="font-semibold category-name">${escapeHTML(category.name)}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick='openEditCategoryModal(${JSON.stringify(category)})' title="Modifica" class="p-1 text-gray-400 hover:text-blue-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                    </button>
                    <form action="delete_category.php" method="POST" class="delete-form">
                        <input type="hidden" name="category_id" value="${category.id}">
                        <button type="submit" class="p-1 text-gray-500 hover:text-red-400 transition-colors">&times;</button>
                    </form>
                </div>
            `;
            container.appendChild(newEl);
        }

        function updateCategoryInUI(category) {
            const row = document.querySelector(`.category-item[data-id="${category.id}"]`);
            if (row) {
                row.querySelector('.category-name').textContent = escapeHTML(category.name);
                row.querySelector('.category-icon').textContent = escapeHTML(category.icon);
                // Aggiorna anche i dati nel pulsante di modifica
                const editButton = row.querySelector('button[onclick^="openEditCategoryModal"]');
                editButton.setAttribute('onclick', `openEditCategoryModal(${JSON.stringify(category)})`);
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