<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$note_id = $_GET['id'] ?? 0;

$note = get_note_by_id($conn, $note_id, $user_id);
if (!$note) {
    header("location: notes.php?message=Nota non trovata.&type=error");
    exit;
}

$todolist_items = (!empty($note['todolist_content'])) ? json_decode($note['todolist_content'], true) : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Dettagli Nota - Bearget</title>
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
    <style> body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); } </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-gray-800 p-4">
            <!-- ... (Codice sidebar completo, con "Note" come pagina attiva) ... -->
        </aside>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto flex flex-col">
            <form action="update_note.php" method="POST" id="note-form" class="flex flex-col flex-grow">
                <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                <input type="hidden" name="content" id="content-input">
                <input type="hidden" name="todolist_content" id="todolist-content-input">

                <header class="flex justify-between items-center mb-6 flex-shrink-0">
                    <input type="text" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" class="text-3xl font-bold text-white bg-transparent border-0 border-b-2 border-gray-700 focus:ring-0 focus:border-primary-500 w-full mr-4">
                    <div class="flex items-center space-x-2">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva</button>
                        <a href="notes.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Indietro</a>
                    </div>
                </header>
                
                <!-- AGGIORNATO: Contenitore a griglia per layout 50/50 -->
                <div class="flex-grow grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-0">
                    <!-- Colonna Testo -->
                    <div class="flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">Testo</h3>
                        <div class="flex-grow bg-gray-800 rounded-lg">
                            <textarea id="text-content" class="w-full h-full bg-gray-800 text-gray-300 rounded-lg p-4 border-0 focus:ring-2 focus:ring-primary-500 resize-none"><?php echo htmlspecialchars($note['content']); ?></textarea>
                        </div>
                    </div>

                    <!-- Colonna To-Do List -->
                    <div class="flex flex-col">
                        <h3 class="text-lg font-semibold text-white mb-2">To-Do List</h3>
                        <div class="flex-grow bg-gray-800 rounded-lg p-4 overflow-y-auto">
                            <div id="todolist-container" class="space-y-2">
                                <?php if (is_array($todolist_items)): foreach($todolist_items as $item): 
                                    $is_completed = isset($item['completed']) && $item['completed'];
                                ?>
                                <div class="flex items-center bg-gray-900 p-2 rounded-lg todolist-item">
                                    <input type="checkbox" class="h-5 w-5 rounded bg-gray-700 border-gray-600 text-primary-600 focus:ring-primary-500" <?php if($is_completed) echo 'checked'; ?>>
                                    <input type="text" value="<?php echo htmlspecialchars($item['task'] ?? ''); ?>" class="flex-grow bg-transparent text-white border-0 focus:ring-0 mx-2 <?php if($is_completed) echo 'line-through text-gray-500'; ?>">
                                    <button type="button" class="text-gray-500 hover:text-danger remove-item-btn">&times;</button>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                            <button type="button" id="add-item-btn" class="mt-4 text-sm text-primary-500 hover:text-primary-400 font-semibold">+ Aggiungi elemento</button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const contentInput = document.getElementById('content-input');
        const todolistContentInput = document.getElementById('todolist-content-input');
        const textContent = document.getElementById('text-content');
        const todolistContainer = document.getElementById('todolist-container');
        const addItemBtn = document.getElementById('add-item-btn');
        const noteForm = document.getElementById('note-form');

        function handleCheckboxChange(checkbox) {
            const textInput = checkbox.nextElementSibling;
            if (checkbox.checked) {
                textInput.classList.add('line-through', 'text-gray-500');
            } else {
                textInput.classList.remove('line-through', 'text-gray-500');
            }
        }

        function createTodoItem(task = '', completed = false) {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'flex items-center bg-gray-900 p-2 rounded-lg todolist-item';
            const textClasses = `flex-grow bg-transparent text-white border-0 focus:ring-0 mx-2 ${completed ? 'line-through text-gray-500' : ''}`;
            itemDiv.innerHTML = `
                <input type="checkbox" class="h-5 w-5 rounded bg-gray-700 border-gray-600 text-primary-600 focus:ring-primary-500" ${completed ? 'checked' : ''}>
                <input type="text" value="${task}" class="${textClasses}">
                <button type="button" class="text-gray-500 hover:text-danger remove-item-btn">&times;</button>
            `;
            todolistContainer.appendChild(itemDiv);
            
            const newCheckbox = itemDiv.querySelector('input[type="checkbox"]');
            newCheckbox.addEventListener('change', () => handleCheckboxChange(newCheckbox));
            itemDiv.querySelector('.remove-item-btn').addEventListener('click', () => itemDiv.remove());
        }

        addItemBtn.addEventListener('click', () => createTodoItem());

        document.querySelectorAll('.todolist-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => handleCheckboxChange(checkbox));
        });
        
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.todolist-item').remove());
        });

        noteForm.addEventListener('submit', function(e) {
            contentInput.value = textContent.value;

            const items = [];
            document.querySelectorAll('.todolist-item').forEach(itemDiv => {
                const taskInput = itemDiv.querySelector('input[type="text"]');
                const completedCheckbox = itemDiv.querySelector('input[type="checkbox"]');
                if (taskInput.value.trim() !== '') {
                    items.push({
                        task: taskInput.value,
                        completed: completedCheckbox.checked
                    });
                }
            });
            todolistContentInput.value = JSON.stringify(items);
        });
    </script>
</body>
</html>