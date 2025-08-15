<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$current_page = 'purchase_planner'; 

// Controllo accesso Pro
$user = get_user_by_id($conn, $user_id);
if ($user['subscription_status'] !== 'active' && $user['subscription_status'] !== 'lifetime') {
    header("location: pricing.php?message=Accedi al Pianificatore di Acquisti con un piano Premium!");
    exit;
}

$expenseCategories = get_user_categories($conn, $user_id, 'expense');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pianificatore di Acquisti - Bearget</title>
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
        .modal-backdrop, .modal-content { transition: all 0.3s ease-in-out; }
        .result-item { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .toggle-checkbox:checked { right: 0; border-color: var(--color-primary-600); }
        .toggle-checkbox:checked + .toggle-label { background-color: var(--color-primary-600); }
    </style>
</head>
<body class="text-gray-200">
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
                            Pianificatore di Acquisti
                        </h1>
                        <p class="text-gray-400 mt-1">Trasforma i tuoi desideri in obiettivi raggiungibili.</p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Colonna di input -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">La tua lista dei desideri</h2>
                    <div id="wishlist-items" class="space-y-3 mb-6"></div>
                    <div class="border-t border-gray-700 pt-4">
                        <button type="button" id="add-wish-btn" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">+ Aggiungi Desiderio</button>
                    </div>
                    <div class="mt-8 border-t border-gray-700 pt-6">
                         <button id="analyze-button" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-5 rounded-lg flex items-center justify-center transition-colors text-lg disabled:bg-gray-600 disabled:cursor-not-allowed" disabled>
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Analizza i miei desideri
                        </button>
                    </div>
                </div>

                <!-- Colonna dei risultati -->
                <div id="analysis-result-container" class="bg-gray-800 rounded-2xl p-6 hidden">
                     <div id="analysis-placeholder" class="text-center text-gray-500 flex flex-col justify-center items-center h-full">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <p>I risultati della tua analisi appariranno qui.</p>
                    </div>
                    <div id="result-wrapper"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modale Aggiungi Desiderio -->
    <div id="purchase-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-60 opacity-0 modal-backdrop" onclick="closeModal('purchase-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-lg p-6 transform scale-95 opacity-0 modal-content overflow-y-auto max-h-screen">
            <h2 class="text-2xl font-bold text-white mb-6">Aggiungi un Desiderio</h2>
            <form id="purchase-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nome Oggetto</label>
                    <input type="text" id="purchase-name" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Costo Totale (€)</label>
                        <input type="number" step="0.01" id="purchase-total-cost" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data Obiettivo</label>
                        <input type="date" id="purchase-date" value="<?php echo date('Y-m-d'); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="border-t border-b border-gray-700 py-4">
                    <div class="flex items-center justify-between">
                        <label for="is-goal" class="font-medium text-white flex-grow">Crea un Obiettivo di Risparmio?</label>
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="is-goal" id="is-goal" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                            <label for="is-goal" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-600 cursor-pointer"></label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Attiva per accantonare fondi ogni mese. Disattiva per un acquisto diretto o a rate.</p>
                </div>
                <div id="installment-section">
                    <h3 class="text-lg font-semibold text-white mb-2">Dettagli Acquisto Diretto (Opzionale)</h3>
                    <div class="grid grid-cols-2 gap-4">
                         <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Anticipo</label>
                            <input type="number" step="0.01" id="purchase-down-payment" placeholder="0.00" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Costi Una Tantum</label>
                            <input type="number" step="0.01" id="purchase-additional-costs" placeholder="0.00" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">N. di Rate</label>
                            <input type="number" id="purchase-months" placeholder="0" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Importo Rata (€)</label>
                            <input type="number" step="0.01" id="purchase-monthly-cost" placeholder="0.00" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('purchase-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Aggiungi alla Lista</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modale Info Dettagli -->
    <div id="info-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-60 modal-backdrop" onclick="closeModal('info-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h3 id="info-modal-title" class="text-xl font-bold text-white mb-4"></h3>
            <div id="info-modal-body" class="text-gray-300"></div>
            <div class="mt-6 text-right">
                <button type="button" onclick="closeModal('info-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Chiudi</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
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
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            if(backdrop) backdrop.classList.add('opacity-0');
            if(content) content.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const wishlistContainer = document.getElementById('wishlist-items');
            const analyzeButton = document.getElementById('analyze-button');
            const resultContainer = document.getElementById('analysis-result-container');
            const resultWrapper = document.getElementById('result-wrapper');
            const placeholder = document.getElementById('analysis-placeholder');
            
            let wishlist = [];

            function showInResultPanel(content) {
                placeholder.classList.add('hidden');
                resultContainer.classList.remove('hidden');
                resultWrapper.innerHTML = content;
            }

            function displayPlannerMessage(message, isError = false) {
                const title = isError ? 'Operazione Fallita' : 'Operazione Riuscita';
                const bgColor = isError ? 'bg-red-500/10' : 'bg-green-500/10';
                const textColor = isError ? 'text-red-400' : 'text-green-400';
                const icon = isError ? `<svg class="w-8 h-8 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>` : `<svg class="w-8 h-8 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                const messageHtml = `<div class="text-center p-4 rounded-lg ${bgColor} ${textColor}">${icon}<h3 class="font-bold text-lg mb-2">${title}</h3><p class="text-sm text-gray-300">${message}</p></div>`;
                showInResultPanel(messageHtml);
            }

            const isGoalCheckbox = document.getElementById('is-goal');
            const installmentSection = document.getElementById('installment-section');

            // Funzione per mostrare/nascondere la sezione delle rate
            function toggleInstallmentSection() {
                installmentSection.style.display = isGoalCheckbox.checked ? 'none' : 'block';
            }
            
            document.getElementById('add-wish-btn').addEventListener('click', () => {
                document.getElementById('purchase-form').reset();
                toggleInstallmentSection(); // Chiamata corretta
                openModal('purchase-modal');
            });
            
            isGoalCheckbox.addEventListener('change', toggleInstallmentSection);

            document.getElementById('purchase-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const isGoal = isGoalCheckbox.checked;
                const item = {
                    name: document.getElementById('purchase-name').value,
                    cost: parseFloat(document.getElementById('purchase-total-cost').value),
                    date: document.getElementById('purchase-date').value,
                    details: {
                        downPayment: parseFloat(document.getElementById('purchase-down-payment').value) || 0,
                        additionalCosts: parseFloat(document.getElementById('purchase-additional-costs').value) || 0,
                        months: parseInt(document.getElementById('purchase-months').value) || 0,
                        monthlyCost: parseFloat(document.getElementById('purchase-monthly-cost').value) || 0,
                    }
                };
                closeModal('purchase-modal');

                if (isGoal) {
                    item.type = 'goal';
                    fetch('create_goal_from_wish.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: item.name, cost: item.cost, date: item.date }) })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            displayPlannerMessage(response.message, false);
                            wishlist.push(item);
                            renderWishlist();
                        } else {
                            displayPlannerMessage(response.error, true);
                        }
                    })
                    .catch(() => displayPlannerMessage('Errore di comunicazione con il server.', true));
                } else {
                    item.type = (item.details.months > 0 && item.details.monthlyCost > 0) ? 'installment' : 'immediate';
                    wishlist.push(item);
                    renderWishlist();
                }
                this.reset();
            });

            function renderWishlist() {
                wishlistContainer.innerHTML = '';
                if (wishlist.length === 0) {
                    wishlistContainer.innerHTML = '<p class="text-sm text-gray-500">La tua lista è vuota.</p>';
                    analyzeButton.disabled = true;
                } else {
                    wishlist.sort((a, b) => new Date(a.date) - new Date(b.date));
                    wishlist.forEach((item, index) => {
                        const itemEl = document.createElement('div');
                        itemEl.className = 'flex items-center justify-between bg-gray-700 p-2 rounded-lg';
                        let typeLabel, typeClass;
                        switch(item.type) {
                            case 'goal': typeLabel = 'Obiettivo'; typeClass = 'bg-yellow-600'; break;
                            case 'installment': typeLabel = 'A Rate'; typeClass = 'bg-indigo-600'; break;
                            default: typeLabel = 'Unica Soluzione'; typeClass = 'bg-primary-600';
                        }
                        const formattedDate = new Date(item.date + 'T00:00:00').toLocaleDateString('it-IT', { year: 'numeric', month: 'long', day: 'numeric' });
                        itemEl.innerHTML = `<div><span class="font-semibold text-white">${escapeHTML(item.name)}</span><span class="text-xs text-gray-400"> - ${formattedDate}</span><span class="text-xs font-medium px-2 py-1 rounded-full ml-2 ${typeClass}">${typeLabel}</span></div><div class="flex items-center gap-4"><span class="font-bold text-white">€${item.cost.toFixed(2)}</span><button type="button" class="text-gray-500 hover:text-red-400" data-index="${index}">&times;</button></div>`;
                        wishlistContainer.appendChild(itemEl);
                    });
                    analyzeButton.disabled = false;
                }
            }

            wishlistContainer.addEventListener('click', e => { if (e.target.tagName === 'BUTTON' && e.target.dataset.index) { wishlist.splice(e.target.dataset.index, 1); renderWishlist(); } });

            analyzeButton.addEventListener('click', function() {
                const loadingSpinner = `<div class="text-center text-gray-500 flex flex-col justify-center items-center h-full"><svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-4">Analizzo le tue finanze...</p></div>`;
                showInResultPanel(loadingSpinner);

                fetch('analyze_purchase.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ items: wishlist }) })
                .then(res => res.json())
                .then(data => {
                    let analysisHtml = `<h2 class="text-2xl font-bold mb-4 ${data.verdict === 'positive' ? 'text-success' : (data.verdict === 'warning' ? 'text-warning' : 'text-danger')}">${data.summary}</h2><div class="space-y-3">`;
                    data.analysis.forEach(point => {
                        analysisHtml += `<div class="flex items-start gap-3 bg-gray-700/50 p-3 rounded-lg result-item"><span class="text-xl mt-1">${point.icon}</span><p class="text-gray-300">${point.text}</p></div>`;
                    });
                    analysisHtml += '</div>';

                    if (data.ongoing_goals && data.ongoing_goals.length > 0) {
                        analysisHtml += `<div class="mt-6 border-t border-gray-700 pt-4"><h3 class="text-lg font-semibold text-white mb-3">Ricorda i tuoi impegni</h3><div class="space-y-2">`;
                        data.ongoing_goals.forEach(goal => {
                            const detailText = `Hai accumulato <strong>€${parseFloat(goal.current_amount).toFixed(2)}</strong> su un totale di <strong>€${parseFloat(goal.target_amount).toFixed(2)}</strong>.`;
                            analysisHtml += `<div class="flex items-center justify-between text-sm text-gray-400">
                                                <span>🎯 Stai risparmiando <strong>€${parseFloat(goal.monthly_contribution).toFixed(2)}/mese</strong> per '${escapeHTML(goal.name)}'</span>
                                                <button class="info-button inline-flex items-center px-2 py-1 bg-primary-600 text-white text-xs font-medium rounded-md hover:bg-primary-700 transition-colors" data-title="Dettaglio: ${escapeHTML(goal.name)}" data-details="${escapeHTML(detailText)}">
                                                    <svg class="w-4 h-4 -ml-0.5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                                    Esamina
                                                </button>
                                            </div>`;
                        });
                        analysisHtml += `</div></div>`;
                    }
                    showInResultPanel(analysisHtml);
                })
                .catch(err => displayPlannerMessage('Si è verificato un errore durante l\'analisi. Riprova.', true));
            });
            
            resultWrapper.addEventListener('click', e => {
                const infoButton = e.target.closest('.info-button');
                if (infoButton) {
                    document.getElementById('info-modal-title').innerHTML = infoButton.dataset.title;
                    document.getElementById('info-modal-body').innerHTML = infoButton.dataset.details;
                    openModal('info-modal');
                }
            });

            function escapeHTML(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            renderWishlist();
            toggleInstallmentSection();
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