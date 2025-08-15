<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: index.php"); exit; }
require_once 'db_connect.php';
require_once 'functions.php';
$user_id = $_SESSION["id"];
$notifications = get_unread_notifications($conn, $user_id);
$notification_count = count($notifications); // Calcoliamo per la sidebar

$current_page = 'notifications'; 

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifiche - Bearget</title>
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
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); } </style>
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
                            Notifiche
                        </h1>
                        <p class="text-gray-400 mt-1">Qui troverai inviti e altri avvisi importanti.</p>
                    </div>
                </div>
            </header>
            <div class="space-y-4 max-w-3xl mx-auto">
                <?php if (empty($notifications)): ?>
                    <div class="bg-gray-800 rounded-lg p-6 text-center">
                        <p class="text-gray-400">Nessuna nuova notifica al momento.</p>
                    </div>
                <?php else: foreach ($notifications as $notification): ?>
                <div class="bg-gray-800 rounded-lg p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <!-- Icone dinamiche in base al tipo di notifica -->
                        <?php if ($notification['type'] == 'fund_invite'): ?>
                            <svg class="w-6 h-6 mr-4 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <?php elseif ($notification['type'] == 'budget_warning'): ?>
                            <svg class="w-6 h-6 mr-4 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <?php elseif ($notification['type'] == 'budget_exceeded'): ?>
                            <svg class="w-6 h-6 mr-4 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        <?php endif; ?>
                        
                        <!-- MODIFICATO: Aggiunto un div per contenere messaggio e data -->
                        <div>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <!-- NUOVA RIGA: Visualizza la data e l'ora formattate -->
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date("d/m/Y H:i", strtotime($notification['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($notification['type'] == 'fund_invite'): ?>
                    <div class="flex space-x-2 flex-shrink-0 ml-4">
                        <form action="accept_invite.php" method="POST">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <input type="hidden" name="fund_id" value="<?php echo $notification['related_id']; ?>">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-3 py-1 rounded-md text-sm">Accetta</button>
                        </form>
                        <form action="decline_invite.php" method="POST">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1 rounded-md text-sm">Rifiuta</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
    </div>
    <script>
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
