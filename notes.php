<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];

// Prepara i filtri dalla query string dell'URL
$filters = [
    'text_search' => $_GET['text_search'] ?? '',
    'id_search' => $_GET['id_search'] ?? '',
    'date_search' => $_GET['date_search'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest'
];

$notes = get_notes_for_user($conn, $user_id, $filters);
$current_page = 'notes';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note - Bearget</title>
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
        textarea:disabled, input:disabled {
            cursor: not-allowed;
            background-color: #374151; /* gray-700 */
        }
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
                            Le tue Note
                        </h1>
                        <p class="text-gray-400 mt-1">Crea note, appunti o liste di cose da fare.</p>
                    </div>
                </div>
                <button id="add-note-btn" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg flex items-center transition-colors shadow-lg hover:shadow-primary-500/50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nuova Nota
                </button>
            </header>

            <!-- Filtri e Ricerca -->
            <div class="bg-gray-800 rounded-2xl p-4 mb-6">
                <form action="notes.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label for="text_search" class="text-sm font-medium text-gray-400">Titolo/Contenuto</label>
                        <input type="text" name="text_search" id="text_search" value="<?php echo htmlspecialchars($filters['text_search'] ?? ''); ?>" placeholder="Cerca testo..." class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label for="id_search" class="text-sm font-medium text-gray-400">ID Nota</label>
                        <input type="number" name="id_search" id="id_search" value="<?php echo htmlspecialchars($filters['id_search'] ?? ''); ?>" placeholder="Es. 123" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label for="date_search" class="text-sm font-medium text-gray-400">Data Creazione</label>
                        <input type="date" name="date_search" id="date_search" value="<?php echo htmlspecialchars($filters['date_search'] ?? ''); ?>" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg">Filtra</button>
                        <a href="notes.php" class="w-full text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg">Resetta</a>
                    </div>
                </form>
            </div>

            <div id="notes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($notes)): ?>
                    <div id="empty-state-notes" class="md:col-span-2 xl:col-span-4 text-center py-10">
                        <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <h3 class="mt-2 text-sm font-medium text-white">Nessuna nota trovata</h3>
                        <p class="mt-1 text-sm text-gray-500">Crea la tua prima nota per iniziare.</p>
                    </div>
                <?php else: foreach ($notes as $note): ?>
                <div class="relative block bg-gray-800 hover:bg-gray-700 p-6 rounded-2xl transition-colors cursor-pointer" onclick='openNoteModal(<?php echo json_encode($note); ?>)' data-note-id="<?php echo $note['id']; ?>">
                    <div class="flex justify-between items-start">
                        <h3 class="text-xl font-bold text-white truncate mb-2 note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                        <div class="tag-container flex items-center gap-2">
                            <?php if ($note['creator_id'] != $user_id): ?>
                                <span class="text-xs bg-green-600 text-white font-semibold px-2 py-1 rounded-full shared-tag">Condivisa</span>
                            <?php endif; ?>
                            <?php
                                $todolist_items = json_decode($note['todolist_content'], true);
                                if (!empty($todolist_items) && is_array($todolist_items)):
                            ?>
                                <span class="text-xs bg-primary-600 text-white font-semibold px-2 py-1 rounded-full todo-tag">To-Do</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm h-12 overflow-hidden note-content-preview">
                        <?php echo htmlspecialchars(substr($note['content'], 0, 100)) . (strlen($note['content']) > 100 ? '...' : ''); ?>
                    </p>
                    <div class="flex justify-between items-center mt-4">
                        <p class="text-xs text-gray-500 note-date">
                            Modificato: <?php echo date("d/m/Y", strtotime($note['updated_at'])); ?>
                        </p>
                        <?php if (!empty($note['transaction_id'])): ?>
                            <a href="transactions.php?description=<?php echo urlencode($note['transaction_description']); ?>" class="text-xs bg-blue-600 text-white font-semibold px-2 py-1 rounded-full hover:bg-blue-700 transition-colors" title="Collegato alla transazione: <?php echo htmlspecialchars($note['transaction_description']); ?>">
                                Collegato
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
    </div>

    <!-- Modale Editor Nota -->
    <div id="note-editor-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-60 opacity-0 modal-backdrop" onclick="closeModal('note-editor-modal')"></div>
        <div class="bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl h-5/6 flex flex-col p-6 transform scale-95 opacity-0 modal-content">
            <form id="note-form" class="flex flex-col flex-grow min-h-0">
                <input type="hidden" name="note_id" id="note-id">
                <header class="flex justify-between items-center mb-6 flex-shrink-0 gap-4">
                    <div class="flex-grow">
                        <span id="modal-note-id-display" class="block text-sm text-gray-500 font-mono mb-1"></span>
                        <input type="text" name="title" id="note-title" class="text-3xl font-bold text-white bg-transparent border-0 border-b-2 border-gray-700 focus:ring-0 focus:border-primary-500 w-full" placeholder="Titolo...">
                    </div>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <button type="button" id="members-note-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg hidden">Membri</button>
                        <button type="button" id="share-note-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg hidden">Condividi</button>
                        <button type="button" id="delete-note-btn" class="bg-danger hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">Elimina</button>
                        <button type="submit" id="save-note-btn" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
                    </div>
                </header>

                <div class="flex-grow grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-0">
                    <div class="flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Testo</h3>
                        <div class="flex-grow bg-gray-900 rounded-lg">
                            <textarea id="text-content" class="w-full h-full bg-gray-900 text-gray-300 rounded-lg p-4 border-0 focus:ring-2 focus:ring-primary-500 resize-none" placeholder="Scrivi qui..."></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">To-Do List</h3>
                        <div class="flex-grow bg-gray-900 rounded-lg p-4 overflow-y-auto">
                            <div id="todolist-container" class="space-y-2"></div>
                            <button type="button" id="add-item-btn" class="mt-4 text-sm text-primary-500 hover:text-primary-400 font-semibold">+ Aggiungi elemento</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include 'modals/note_confirm_delete_modal.php'; ?>
    <?php include 'modals/note_share_modal.php'; ?>
    <?php include 'modals/note_members_modal.php'; ?>
    <?php include 'toast_notification.php'; ?>

    <script>
        const CURRENT_USER_ID = <?php echo json_encode($_SESSION['id']); ?>;

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.modal-backdrop')?.classList.remove('opacity-0');
                modal.querySelector('.modal-content')?.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.querySelector('.modal-backdrop')?.classList.add('opacity-0');
            modal.querySelector('.modal-content')?.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            if (!toast) return;
            const toastMessage = toast.querySelector('#toast-message');
            toast.className = 'fixed top-5 right-5 flex items-center w-full max-w-xs p-4 space-x-4 text-white divide-x rtl:divide-x-reverse divide-gray-700 rounded-lg shadow space-x transition-opacity duration-300';
            toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
            if(toastMessage) toastMessage.textContent = message;
            toast.classList.remove('hidden', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 5000);
        }

        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return str.toString().replace(/[&<>"']/g, match => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[match]);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addNoteBtn = document.getElementById('add-note-btn');
            const noteForm = document.getElementById('note-form');
            const deleteNoteBtn = document.getElementById('delete-note-btn');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const addItemBtn = document.getElementById('add-item-btn');
            const todolistContainer = document.getElementById('todolist-container');
            const shareNoteBtn = document.getElementById('share-note-btn');
            const shareNoteForm = document.getElementById('share-note-form');
            const membersNoteBtn = document.getElementById('members-note-btn');
            const membersList = document.getElementById('members-list');

            addNoteBtn.addEventListener('click', () => {
                fetch('add_note.php', { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            addNoteToGrid(data.note);
                            openNoteModal(data.note);
                        } else {
                            showToast(data.message || 'Errore', 'danger');
                        }
                    }).catch(() => showToast('Errore di rete', 'danger'));
            });

            noteForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData();
                formData.append('note_id', document.getElementById('note-id').value);
                formData.append('title', document.getElementById('note-title').value);
                formData.append('content', document.getElementById('text-content').value);
                formData.append('todolist_content', JSON.stringify(getTodoListData()));

                fetch('update_note.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            updateNoteInGrid(data.note);
                            closeModal('note-editor-modal');
                        } else {
                            showToast(data.message || 'Errore', 'danger');
                        }
                    }).catch(() => showToast('Errore di rete', 'danger'));
            });

            deleteNoteBtn.addEventListener('click', () => {
                const noteId = document.getElementById('note-id').value;
                const isCreator = document.getElementById('delete-note-btn').dataset.isCreator === 'true';

                if (isCreator) {
                    openModal('confirm-delete-modal');
                } else {
                    const formData = new FormData();
                    formData.append('note_id', noteId);
                    formData.append('member_id', CURRENT_USER_ID);

                    fetch('ajax_remove_share.php', { method: 'POST', body: formData})
                        .then(res => res.json())
                        .then(data => {
                            showToast(data.message, data.success ? 'success' : 'danger');
                            if (data.success) {
                                document.querySelector(`[data-note-id="${noteId}"]`)?.remove();
                                closeModal('note-editor-modal');
                            }
                        }).catch(() => showToast('Errore di rete', 'danger'));
                }
            });

            confirmDeleteBtn.addEventListener('click', () => {
                const noteId = document.getElementById('note-id').value;
                fetch('delete_note.php', { method: 'POST', body: new URLSearchParams({note_id: noteId}) })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                            document.querySelector(`[data-note-id="${noteId}"]`)?.remove();
                            closeModal('confirm-delete-modal');
                            closeModal('note-editor-modal');
                        } else {
                            showToast(data.message, 'danger');
                        }
                    }).catch(() => showToast('Errore di rete', 'danger'));
            });

            shareNoteBtn.addEventListener('click', () => {
                document.getElementById('share-note-id').value = document.getElementById('note-id').value;
                openModal('share-note-modal');
            });

            shareNoteForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(shareNoteForm);
                fetch('ajax_share_note.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        showToast(data.message, data.success ? 'success' : 'danger');
                        if (data.success) closeModal('share-note-modal');
                    }).catch(() => showToast('Errore di rete', 'danger'));
            });

            membersNoteBtn.addEventListener('click', () => {
                const noteId = document.getElementById('note-id').value;
                fetch(`ajax_get_note_members.php?note_id=${noteId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            membersList.innerHTML = '';
                            if (data.members.length > 0) {
                                data.members.forEach(member => {
                                    const li = document.createElement('li');
                                    li.className = 'flex justify-between items-center bg-gray-700 p-2 rounded-lg';
                                    li.innerHTML = `
                                        <span>${escapeHTML(member.username)}</span>
                                        <div class="flex items-center gap-2">
                                            <select class="permission-select bg-gray-600 text-xs rounded p-1" data-member-id="${member.id}" data-note-id="${noteId}">
                                                <option value="edit" ${member.permission === 'edit' ? 'selected' : ''}>Modifica</option>
                                                <option value="view" ${member.permission === 'view' ? 'selected' : ''}>Visualizza</option>
                                            </select>
                                            <button class="remove-member-btn text-xs text-red-400 hover:text-red-300" data-member-id="${member.id}" data-note-id="${noteId}">Rimuovi</button>
                                        </div>
                                    `;
                                    membersList.appendChild(li);
                                });
                            } else {
                                membersList.innerHTML = '<p class="text-gray-400 text-sm">Questa nota non è condivisa con nessuno.</p>';
                            }
                            openModal('members-modal');
                        } else {
                            showToast(data.message, 'danger');
                        }
                    }).catch(() => showToast('Errore di rete', 'danger'));
            });

            membersList.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-member-btn')) {
                    const button = e.target;
                    const memberId = button.dataset.memberId;
                    const noteId = button.dataset.noteId;
                    const formData = new FormData();
                    formData.append('note_id', noteId);
                    formData.append('member_id', memberId);

                    fetch('ajax_remove_share.php', { method: 'POST', body: formData})
                        .then(res => res.json())
                        .then(data => {
                            showToast(data.message, data.success ? 'success' : 'danger');
                            if (data.success) button.closest('li').remove();
                        }).catch(() => showToast('Errore di rete', 'danger'));
                }
            });

            membersList.addEventListener('change', (e) => {
                if (e.target.classList.contains('permission-select')) {
                    const select = e.target;
                    const noteId = select.dataset.noteId;
                    const memberId = select.dataset.memberId;
                    const permission = select.value;

                    const formData = new FormData();
                    formData.append('note_id', noteId);
                    formData.append('member_id', memberId);
                    formData.append('permission', permission);

                    fetch('ajax_update_permission.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                             showToast(data.message, data.success ? 'success' : 'danger');
                        }).catch(() => showToast('Errore di rete', 'danger'));
                }
            });

            addItemBtn.addEventListener('click', () => createTodoItem());
            todolistContainer.addEventListener('click', e => e.target.classList.contains('remove-item-btn') && e.target.closest('.todolist-item').remove());
            todolistContainer.addEventListener('change', e => {
                if (e.target.type === 'checkbox') {
                    e.target.nextElementSibling.classList.toggle('line-through', e.target.checked);
                    e.target.nextElementSibling.classList.toggle('text-gray-500', e.target.checked);
                }
            });
        });

        function openNoteModal(note) {
            document.getElementById('modal-note-id-display').textContent = `ID: NOTE-${note.id}`;
            document.getElementById('note-id').value = note.id;
            document.getElementById('note-title').value = note.title;
            document.getElementById('text-content').value = note.content;

            const todolistContainer = document.getElementById('todolist-container');
            todolistContainer.innerHTML = '';
            try {
                const items = JSON.parse(note.todolist_content || '[]');
                if (Array.isArray(items)) items.forEach(item => createTodoItem(item.task, item.completed));
            } catch (e) { console.error("Errore parsing to-do list:", e); }

            const isCreator = note.creator_id == CURRENT_USER_ID;
            const canEdit = isCreator || note.permission === 'edit';

            const deleteBtn = document.getElementById('delete-note-btn');
            const saveBtn = document.getElementById('save-note-btn');
            const shareBtn = document.getElementById('share-note-btn');
            const membersBtn = document.getElementById('members-note-btn');
            const titleInput = document.getElementById('note-title');
            const contentTextarea = document.getElementById('text-content');
            const addItemBtn = document.getElementById('add-item-btn');
            const todolistInputs = todolistContainer.querySelectorAll('input');

            shareBtn.classList.toggle('hidden', !isCreator);
            membersBtn.classList.toggle('hidden', !isCreator);

            deleteBtn.dataset.isCreator = isCreator;
            if (isCreator) {
                deleteBtn.textContent = 'Elimina';
                deleteBtn.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
                deleteBtn.classList.add('bg-danger', 'hover:bg-red-700');
            } else {
                deleteBtn.textContent = 'Abbandona';
                deleteBtn.classList.remove('bg-danger', 'hover:bg-red-700');
                deleteBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
            }
            deleteBtn.classList.remove('hidden');

            titleInput.disabled = !canEdit;
            contentTextarea.disabled = !canEdit;
            addItemBtn.style.display = canEdit ? 'block' : 'none';
            saveBtn.style.display = canEdit ? 'block' : 'none';
            todolistInputs.forEach(input => input.disabled = !canEdit);

            openModal('note-editor-modal');
        }

        function createTodoItem(task = '', completed = false) {
            const container = document.getElementById('todolist-container');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'flex items-center bg-gray-700 p-2 rounded-lg todolist-item';
            const textClasses = `flex-grow bg-transparent text-white border-0 focus:ring-0 mx-2 ${completed ? 'line-through text-gray-500' : ''}`;
            itemDiv.innerHTML = `<input type="checkbox" class="h-5 w-5 rounded bg-gray-600 border-gray-500 text-primary-600 focus:ring-primary-500" ${completed ? 'checked' : ''}><input type="text" value="${escapeHTML(task)}" class="${textClasses}" placeholder="Nuova attività..."><button type="button" class="text-gray-500 hover:text-danger remove-item-btn text-xl">&times;</button>`;
            container.appendChild(itemDiv);
        }

        function getTodoListData() {
            const items = [];
            document.querySelectorAll('#todolist-container .todolist-item').forEach(item => {
                const taskInput = item.querySelector('input[type="text"]');
                const completedCheckbox = item.querySelector('input[type="checkbox"]');
                if (taskInput.value.trim()) {
                    items.push({ task: taskInput.value, completed: completedCheckbox.checked });
                }
            });
            return items;
        }

        function addNoteToGrid(note) {
            document.getElementById('empty-state-notes')?.remove();
            const grid = document.getElementById('notes-grid');
            const newCard = document.createElement('div');
            newCard.className = 'block bg-gray-800 hover:bg-gray-700 p-6 rounded-2xl transition-colors cursor-pointer';
            newCard.dataset.noteId = note.id;
            newCard.onclick = () => openNoteModal(note);

            const contentPreview = escapeHTML(note.content?.substring(0, 100)) + (note.content?.length > 100 ? '...' : '');
            const formattedDate = new Date(note.updated_at).toLocaleDateString('it-IT');
            let tagsHTML = '';
            const todoItems = JSON.parse(note.todolist_content || '[]');
            if (Array.isArray(todoItems) && todoItems.length > 0) {
                tagsHTML += `<span class="text-xs bg-primary-600 text-white font-semibold px-2 py-1 rounded-full todo-tag">To-Do</span>`;
            }

            newCard.innerHTML = `<div class="flex justify-between items-start"><h3 class="text-xl font-bold text-white truncate mb-2 note-title">${escapeHTML(note.title)}</h3><div class="tag-container flex items-center gap-2">${tagsHTML}</div></div><p class="text-gray-400 text-sm h-12 overflow-hidden note-content-preview">${contentPreview}</p><div class="flex justify-between items-center mt-4"><p class="text-xs text-gray-500 note-date">Modificato: ${formattedDate}</p></div>`;
            grid.prepend(newCard);
        }

        function updateNoteInGrid(note) {
            const card = document.querySelector(`[data-note-id="${note.id}"]`);
            if (card) {
                card.querySelector('.note-title').textContent = escapeHTML(note.title);
                card.querySelector('.note-content-preview').textContent = escapeHTML(note.content?.substring(0, 100)) + (note.content?.length > 100 ? '...' : '');
                card.querySelector('.note-date').textContent = 'Modificato: ' + new Date().toLocaleDateString('it-IT');

                const tagContainer = card.querySelector('.tag-container');
                let tagsHTML = tagContainer.querySelector('.shared-tag')?.outerHTML || '';
                const todoItems = JSON.parse(note.todolist_content || '[]');
                if (Array.isArray(todoItems) && todoItems.length > 0) {
                    tagsHTML += `<span class="text-xs bg-primary-600 text-white font-semibold px-2 py-1 rounded-full todo-tag">To-Do</span>`;
                }
                tagContainer.innerHTML = tagsHTML;

                card.onclick = () => openNoteModal(note);
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