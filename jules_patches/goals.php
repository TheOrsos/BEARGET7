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
                <!-- Goal cards are rendered here by PHP -->
            </div>
        </main>
    </div>

    <!-- ... Modals for Add/Edit Goal ... -->

    <!-- Modale Cronologia Contributi -->
    <div id="history-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... modal content ... -->
    </div>

    <!-- Modale Modifica Contributo -->
    <div id="edit-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... modal content ... -->
    </div>

    <!-- Modale di Conferma Eliminazione Contributo -->
    <div id="confirm-delete-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <!-- ... modal content ... -->
    </div>

    <!-- Modale per Note Transazione (riutilizzato) -->
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

    <script>
        // --- (Existing modal, toast, helper functions) ---
        // ...

        let currentOpenGoalId = null;

        function openHistoryModal(goalId, goalName) {
            // ... (existing history modal logic) ...
        }

        // --- Note Modal Logic (reused from transactions) ---
        function openNoteModal(transactionId) {
            document.getElementById('note-transaction-id').value = transactionId;
            const noteContentTextarea = document.getElementById('note-content');
            noteContentTextarea.value = 'Caricamento...';

            fetch(`ajax_note_handler.php?action=get_note&transaction_id=${transactionId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        noteContentTextarea.value = data.content;
                    } else {
                        noteContentTextarea.value = '';
                        showToast(data.message || 'Impossibile caricare la nota.', 'error');
                    }
                })
                .catch(() => {
                    noteContentTextarea.value = '';
                    showToast('Errore di rete nel caricare la nota.', 'error');
                });

            openModal('note-modal');
        }


        document.addEventListener('DOMContentLoaded', function() {
            // ... (existing form handlers for goals) ...

            const historyContainer = document.getElementById('history-list-container');
            let transactionIdToDelete = null;

            historyContainer.addEventListener('click', function(e) {
                const deleteButton = e.target.closest('.btn-delete-contribution');
                const editButton = e.target.closest('.btn-edit-contribution');
                const noteButton = e.target.closest('.btn-note-contribution');

                if (deleteButton) {
                    // ... (delete logic) ...
                }

                if (editButton) {
                    // ... (edit logic) ...
                }

                if (noteButton) {
                    const item = noteButton.closest('.contribution-item');
                    const txId = item.getAttribute('data-transaction-id');
                    openNoteModal(txId);
                }
            });

            // ... (handler for edit contribution form) ...

            // --- Note Form Submit Handler ---
            const noteForm = document.getElementById('note-form');
            if (noteForm) {
                noteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(noteForm);
                    formData.append('action', 'save_note');

                    fetch('ajax_note_handler.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message || 'Nota salvata.');
                                closeModal('note-modal');
                            } else {
                                showToast(data.message || 'Errore.', 'error');
                            }
                        })
                        .catch(() => showToast('Errore di rete.', 'error'));
                });
            }

            // ... (other handlers) ...
        });

        // ... (updateGoalCard function) ...
    </script>
</body>
</html>
