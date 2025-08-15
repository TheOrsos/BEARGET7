<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$current_page = 'tags'; // Per la sidebar

// Controlla se l'utente è Pro per accedere alla pagina
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Gestisci le etichette con un piano Premium!");
    exit;
}

$user_tags = get_user_tags($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Etichette - Bearget</title>
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
                            Gestisci le Etichette
                        </h1>
                        <p class="text-gray-400 mt-1">Aggiungi, modifica o rimuovi le etichette per organizzare le transazioni.</p>
                    </div>
                </div>
                 <button onclick="openModal('add-tag-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nuova Etichetta
                </button>
            </header>
            
            <div class="bg-gray-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-4">Le Tue Etichette</h2>
                <div id="tags-list-container" class="space-y-3">
                    <?php if (empty($user_tags)): ?>
                        <div id="empty-state-tags" class="text-center py-10">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8V3z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-white">Nessuna etichetta creata</h3>
                            <p class="mt-1 text-sm text-gray-500">Inizia creando la tua prima etichetta per organizzare le spese.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($user_tags as $tag): ?>
                            <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg transition-shadow hover:shadow-lg" data-tag-id="<?php echo $tag['id']; ?>">
                                <span class="font-semibold text-indigo-400">#<?php echo htmlspecialchars($tag['name']); ?></span>
                                <div class="flex items-center space-x-2">
                                    <button onclick='openEditTagModal(<?php echo json_encode($tag); ?>)' title="Modifica Etichetta" class="p-1 text-gray-400 hover:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                                    </button>
                                    <form action="delete_tag.php" method="POST" class="delete-form">
                                        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                        <button type="submit" title="Elimina Etichetta" class="p-1 text-gray-500 hover:text-red-400 transition-colors">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi Etichetta -->
    <div id="add-tag-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-tag-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-6">Crea Nuova Etichetta</h2>
            <form id="add-tag-form" action="add_tag.php" method="POST" class="space-y-4">
                <div>
                    <label for="tag_name" class="block text-sm font-medium text-gray-300 mb-1">Nome Etichetta</label>
                    <input type="text" name="name" id="tag_name" required placeholder="Es. ViaggioSpagna2025" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Senza #, spazi o caratteri speciali.</p>
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-tag-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-colors">Salva Etichetta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Modifica Etichetta -->
    <div id="edit-tag-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('edit-tag-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Modifica Etichetta</h2>
                <span id="edit-tag-prefixed-id" class="text-sm font-mono text-gray-400"></span>
            </div>
            <form id="edit-tag-form" action="update_tag.php" method="POST">
                <input type="hidden" name="tag_id" id="edit-tag-id">
                <div class="space-y-4">
                    <div>
                        <label for="edit-tag-name" class="block text-sm font-medium text-gray-300 mb-1">Nuovo Nome</label>
                        <input type="text" name="name" id="edit-tag-name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Senza #, spazi o caratteri speciali.</p>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('edit-tag-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
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
            <h3 class="text-lg leading-6 font-bold text-white mt-4">Eliminare Etichetta?</h3>
            <p class="mt-2 text-sm text-gray-400">Questa azione rimuoverà l'etichetta da tutte le transazioni associate. L'azione è irreversibile.</p>
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
            const addTagForm = document.getElementById('add-tag-form');
            const editTagForm = document.getElementById('edit-tag-form');
            const tagsListContainer = document.getElementById('tags-list-container');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let formToDelete = null;

            // --- GESTIONE AGGIUNTA ETICHETTA ---
            addTagForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(addTagForm);
                fetch(addTagForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            addTagToUI(data.tag);
                            addTagForm.reset();
                            closeModal('add-tag-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });

            // --- GESTIONE MODIFICA ETICHETTA ---
            editTagForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editTagForm);
                fetch(editTagForm.action, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateTagInUI(data.tag);
                            closeModal('edit-tag-modal');
                        } else {
                            showToast(data.message, 'error');
                        }
                    }).catch(err => showToast('Errore di rete.', 'error'));
            });
            
            // --- GESTIONE ELIMINAZIONE ETICHETTA ---
            tagsListContainer.addEventListener('submit', function(e) {
                if (e.target.closest('.delete-form')) {
                    e.preventDefault();
                    formToDelete = e.target.closest('.delete-form');
                    openModal('confirm-delete-modal');
                }
            });

            confirmDeleteBtn.addEventListener('click', function() {
                if (formToDelete) {
                    const formData = new FormData(formToDelete);
                    const row = formToDelete.closest('[data-tag-id]');
                    
                    fetch(formToDelete.action, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message);
                                row.classList.add('row-fade-out');
                                setTimeout(() => {
                                    row.remove();
                                    if (tagsListContainer.children.length === 0) {
                                        tagsListContainer.innerHTML = `<div id="empty-state-tags" class="text-center py-10">...</div>`; // Mostra di nuovo lo stato vuoto
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

            // --- FUNZIONI HELPER PER MANIPOLARE LA UI ---
            function addTagToUI(tag) {
                const emptyState = document.getElementById('empty-state-tags');
                if (emptyState) emptyState.remove();

                const newTagEl = document.createElement('div');
                newTagEl.className = 'flex items-center justify-between bg-gray-700 p-3 rounded-lg transition-shadow hover:shadow-lg';
                newTagEl.setAttribute('data-tag-id', tag.id);
                newTagEl.innerHTML = `
                    <span class="font-semibold text-indigo-400">#${escapeHTML(tag.name)}</span>
                    <div class="flex items-center space-x-2">
                        <button onclick='openEditTagModal(${JSON.stringify(tag)})' title="Modifica Etichetta" class="p-1 text-gray-400 hover:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path></svg>
                        </button>
                        <form action="delete_tag.php" method="POST" class="delete-form">
                            <input type="hidden" name="tag_id" value="${tag.id}">
                            <button type="submit" title="Elimina Etichetta" class="p-1 text-gray-500 hover:text-red-400 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </button>
                        </form>
                    </div>
                `;
                tagsListContainer.appendChild(newTagEl);
            }

            function updateTagInUI(tag) {
                const tagEl = document.querySelector(`[data-tag-id="${tag.id}"]`);
                if (tagEl) {
                    const nameSpan = tagEl.querySelector('span');
                    nameSpan.textContent = `#${escapeHTML(tag.name)}`;
                }
            }
        });

        // Funzione globale per aprire la modale di modifica
        function openEditTagModal(tag) {
            document.getElementById('edit-tag-prefixed-id').textContent = 'ID: TAG-' + tag.id;
            document.getElementById('edit-tag-id').value = tag.id;
            document.getElementById('edit-tag-name').value = tag.name;
            openModal('edit-tag-modal');
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