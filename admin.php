<?php
session_start();
// Sicurezza: solo l'utente con ID 1 può accedere a questa pagina.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["id"] != 1) {
    header("location: dashboard.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

// --- LOGICA DI PAGINAZIONE E RICERCA ---
$users_per_page = 20; // Quanti utenti mostrare per pagina
$current_page_number = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = $_GET['search'] ?? '';

// Recupera gli utenti paginati e il numero totale di pagine
$user_data = get_users_paginated_and_searched($conn, $search_term, $current_page_number, $users_per_page);
$users_list = $user_data['users'];
$total_pages = $user_data['total_pages'];

// Recupera le statistiche generali
$stats = get_admin_stats($conn);

$current_page = 'admin'; // Per evidenziare il link nella sidebar
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - Bearget</title>
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
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
            <header class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <button id="menu-button" type="button" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Apri menu principale</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Pannello Admin
                        </h1>
                        <p class="text-gray-400 mt-1">Statistiche e gestione utenti.</p>
                    </div>
                </div>
            </header>

            <!-- SEZIONE STATISTICHE -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-2xl">
                    <h3 class="text-gray-400 text-sm font-medium">Utenti Totali</h3>
                    <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="bg-gray-800 p-6 rounded-2xl">
                    <h3 class="text-gray-400 text-sm font-medium">Utenti Pro</h3>
                    <p class="text-3xl font-bold text-green-400 mt-1"><?php echo $stats['pro_users']; ?></p>
                </div>
                <div class="bg-gray-800 p-6 rounded-2xl">
                    <h3 class="text-gray-400 text-sm font-medium">Utenti Free</h3>
                    <p class="text-3xl font-bold text-yellow-400 mt-1"><?php echo $stats['free_users']; ?></p>
                </div>
                <div class="bg-gray-800 p-6 rounded-2xl">
                    <h3 class="text-gray-400 text-sm font-medium">Nuovi (30 giorni)</h3>
                    <p class="text-3xl font-bold text-indigo-400 mt-1"><?php echo $stats['new_users_last_30_days']; ?></p>
                </div>
            </div>

            <!-- FORM DI RICERCA -->
            <div class="bg-gray-800 rounded-2xl p-4 mb-6">
                <form action="admin.php" method="GET" class="flex items-center gap-4">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Cerca per username o email..." class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg">Cerca</button>
                    <a href="admin.php" class="bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg">Resetta</a>
                </form>
            </div>

            <div class="bg-gray-800 rounded-2xl p-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-sm text-gray-400 uppercase">
                            <tr>
                                <th class="p-4">ID</th>
                                <th class="p-4">Username</th>
                                <th class="p-4">Email</th>
                                <th class="p-4">Stato</th>
                                <th class="p-4">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="text-white">
                            <?php if (empty($users_list)): ?>
                                <tr><td colspan="6" class="text-center p-6 text-gray-400">Nessun utente trovato.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users_list as $user): ?>
                                <tr class="border-b border-gray-700 last:border-b-0">
                                    <td class="p-4"><?php echo $user['id']; ?></td>
                                    <td class="p-4 font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="p-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full <?php 
                                            switch($user['subscription_status']) {
                                                case 'active': echo 'bg-green-700 text-green-100'; break;
                                                case 'lifetime': echo 'bg-indigo-700 text-indigo-100'; break;
                                                default: echo 'bg-gray-700 text-gray-100';
                                            }
                                        ?>">
                                            <?php echo ucfirst($user['subscription_status']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <form action="update_user_status.php" method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="user_id_to_update" value="<?php echo $user['id']; ?>">
                                            <select name="new_status" class="bg-gray-700 text-white rounded-md px-2 py-1 text-sm">
                                                <option value="free" <?php if($user['subscription_status'] == 'free') echo 'selected'; ?>>Free</option>
                                                <option value="active" <?php if($user['subscription_status'] == 'active') echo 'selected'; ?>>Active</option>
                                                <option value="lifetime" <?php if($user['subscription_status'] == 'lifetime') echo 'selected'; ?>>Lifetime</option>
                                            </select>
                                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-3 py-1 rounded-md text-sm">Salva</button>
                                        </form>
                                    </td>
                                    <td class="p-4 text-center">
                                        <!-- MODIFICATO: Aggiunto pulsante Impersonate -->
                                        <div class="flex items-center justify-center space-x-2">
                                            <button onclick='openUserInfoModal(<?php echo json_encode($user); ?>)' class="p-2 hover:bg-gray-700 rounded-full" title="Mostra dettagli utente">
                                                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                            <?php if ($user['id'] != 1): // Non mostrare il pulsante per l'admin stesso ?>
                                                <a href="impersonate.php?id=<?php echo $user['id']; ?>" class="p-2 hover:bg-gray-700 rounded-full" title="Accedi come <?php echo htmlspecialchars($user['username']); ?>">
                                                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CONTROLLI DI PAGINAZIONE -->
            <div class="flex justify-between items-center mt-6">
                <span class="text-sm text-gray-400">Pagina <?php echo $current_page_number; ?> di <?php echo $total_pages > 0 ? $total_pages : 1; ?></span>
                <div class="flex gap-2">
                    <?php if ($current_page_number > 1): ?>
                        <a href="?page=<?php echo $current_page_number - 1; ?>&search=<?php echo urlencode($search_term); ?>" class="bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg">&laquo; Precedente</a>
                    <?php endif; ?>
                    <?php if ($current_page_number < $total_pages): ?>
                        <a href="?page=<?php echo $current_page_number + 1; ?>&search=<?php echo urlencode($search_term); ?>" class="bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg">Successivo &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modale Dettagli Utente -->
    <div id="user-info-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('user-info-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-lg transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-4">Dettagli Utente: <span id="modal-username" class="text-primary-400"></span></h2>
            <div class="space-y-2 text-gray-300">
                <p><strong>ID Utente:</strong> <span id="modal-userid" class="font-mono"></span></p>
                <p><strong>Email:</strong> <span id="modal-email"></span></p>
                <p><strong>Stato Abbonamento:</strong> <span id="modal-status" class="font-semibold"></span></p>
                <p><strong>Codice Amico:</strong> <span id="modal-friendcode" class="font-mono"></span></p>
                <hr class="border-gray-600 my-3">
                <p><strong>Stripe Customer ID:</strong> <span id="modal-stripe-customer" class="font-mono text-sm"></span></p>
                <p><strong>Stripe Subscription ID:</strong> <span id="modal-stripe-sub" class="font-mono text-sm"></span></p>
                <p><strong>Fine/Rinnovo Abbonamento:</strong> <span id="modal-sub-end"></span></p>
                <hr class="border-gray-600 my-3">
                <p><strong>Account Creato il:</strong> <span id="modal-created-at"></span></p>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <button onclick="openUserEditModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg">Modifica Utente</button>
                <button onclick="closeModal('user-info-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Chiudi</button>
            </div>
        </div>
    </div>

    <!-- Modale per Modificare l'Utente -->
    <div id="user-edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('user-edit-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-lg transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-6">Modifica Utente</h2>
            <form id="edit-user-form" action="admin_update_user.php" method="POST">
                <input type="hidden" name="user_id" id="edit-user-id">
                <div class="space-y-4">
                    <div>
                        <label for="edit-email" class="block text-sm font-medium text-gray-300 mb-1">Indirizzo Email</label>
                        <input type="email" name="email" id="edit-email" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="edit-friend-code" class="block text-sm font-medium text-gray-300 mb-1">Codice Amico</label>
                        <input type="text" name="friend_code" id="edit-friend-code" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 font-mono uppercase" maxlength="8">
                    </div>
                    <div>
                        <label for="edit-password" class="block text-sm font-medium text-gray-300 mb-1">Nuova Password (lasciare vuoto per non modificare)</label>
                        <input type="password" name="new_password" id="edit-password" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('user-edit-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentUserData = null;

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop?.classList.remove('opacity-0');
                content?.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            backdrop?.classList.add('opacity-0');
            content?.classList.add('opacity-0', 'scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function formatDate(dateString) {
            if (!dateString || dateString === '0000-00-00 00:00:00') return 'N/A';
            const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString('it-IT', options);
        }

        function openUserInfoModal(user) {
            currentUserData = user;
            
            document.getElementById('modal-username').textContent = user.username;
            document.getElementById('modal-userid').textContent = user.id;
            document.getElementById('modal-email').textContent = user.email;
            document.getElementById('modal-status').textContent = user.subscription_status;
            document.getElementById('modal-friendcode').textContent = user.friend_code || 'N/A';
            
            const customerIdSpan = document.getElementById('modal-stripe-customer');
            if (user.stripe_customer_id) {
                const stripeUrl = `https://dashboard.stripe.com/test/customers/${user.stripe_customer_id}`;
                customerIdSpan.innerHTML = `<a href="${stripeUrl}" target="_blank" class="text-indigo-400 hover:underline">${user.stripe_customer_id}</a>`;
            } else {
                customerIdSpan.textContent = 'N/A';
            }

            document.getElementById('modal-stripe-sub').textContent = user.stripe_subscription_id || 'N/A';
            document.getElementById('modal-sub-end').textContent = formatDate(user.subscription_end_date);
            document.getElementById('modal-created-at').textContent = formatDate(user.created_at);
            
            openModal('user-info-modal');
        }

        function openUserEditModal() {
            if (!currentUserData) return;
            
            document.getElementById('edit-user-id').value = currentUserData.id;
            document.getElementById('edit-email').value = currentUserData.email;
            document.getElementById('edit-friend-code').value = currentUserData.friend_code;
            document.getElementById('edit-password').value = '';

            closeModal('user-info-modal');
            openModal('user-edit-modal');
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