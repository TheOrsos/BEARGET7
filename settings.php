<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$user = get_user_by_id($conn, $user_id);

$current_page = 'settings'; 
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - Bearget</title>
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
<body class="text-gray-200">

    <div class="flex h-screen">
        <div id="sidebar-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        <?php 
        // 2. INCLUDI LA SIDEBAR
        include 'sidebar.php'; 
        ?>

        <!-- Main Content -->
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
                            Impostazioni
                        </h1>
                        <p class="text-gray-400 mt-1">Gestisci il tuo profilo.</p>
                    </div>
                </div>
            </header>

            <div class="space-y-8">
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Seleziona Tema</h2>
                    <form action="update_theme.php" method="POST">
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                            <?php 
                            $themes = [
                                'dark-indigo' => 'bg-indigo-500', 'forest-green' => 'bg-green-500', 'ocean-blue' => 'bg-blue-500',
                                'sunset-orange' => 'bg-orange-500', 'royal-purple' => 'bg-purple-500', 'graphite-gray' => 'bg-gray-500',
                            ];
                            foreach($themes as $theme_key => $color_class):
                            ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="theme" value="<?php echo $theme_key; ?>" class="sr-only" onchange="this.form.submit()" <?php if ($_SESSION['theme'] == $theme_key) echo 'checked'; ?>>
                                <div class="w-16 h-16 <?php echo $color_class; ?> rounded-full mx-auto ring-2 ring-offset-4 ring-offset-gray-800 <?php echo ($_SESSION['theme'] == $theme_key) ? 'ring-white' : 'ring-transparent'; ?>"></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-bold text-white mb-4">Modifica Profilo</h2>
                        <form action="update_profile.php" method="POST" class="space-y-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Indirizzo Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="w-full bg-gray-900 text-gray-400 rounded-lg px-3 py-2 cursor-not-allowed">
                            </div>
                            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg">Salva Profilo</button>
                        </form>
                    </div>

                    <div class="bg-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-bold text-white mb-4">Cambia Password</h2>
                        <form action="update_password.php" method="POST" class="space-y-4" id="password-form">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-300 mb-1">Password Attuale</label>
                                <input type="password" name="current_password" id="current_password" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-300 mb-1">Nuova Password</label>
                                <input type="password" name="new_password" id="new_password" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-1">Conferma Nuova Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            </div>
                            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg">Cambia Password</button>
                        </form>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Il Tuo Codice Amico</h2>
                    <p class="text-gray-400 mb-2">Condividi questo codice per farti invitare nei fondi comuni.</p>
                    <div class="bg-gray-900 text-white text-center font-mono text-2xl tracking-widest py-3 rounded-lg">
                        <?php echo htmlspecialchars($user['friend_code']); ?>
                    </div>
                </div>

                <!-- Gestione Abbonamento -->
                <div class="bg-gray-800 rounded-2xl p-6 lg:col-span-2">
                    <h2 class="text-xl font-bold text-white mb-4">Gestisci Abbonamento</h2>
                    
                    <?php if (!empty($user['stripe_customer_id']) && $user['subscription_status'] == 'active'): ?>
                        <!-- Mostra questo se l'utente ha un abbonamento Stripe attivo -->
                        <p class="text-gray-400 mb-4">
                            Clicca qui sotto per andare al nostro portale sicuro dove potrai aggiornare il tuo metodo di pagamento o annullare il tuo abbonamento Pro.
                        </p>
                        <a href="create-portal-session.php" class="block w-full text-center bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg">
                            Vai al Portale Clienti
                        </a>
                    <?php elseif ($user['subscription_status'] == 'lifetime' || $user['subscription_status'] == 'active'): ?>
                        <!-- Mostra questo se l'utente è Pro ma senza un ID Stripe (attivato manualmente) -->
                        <p class="text-gray-400">
                            Il tuo accesso Pro è stato attivato manualmente. Non è collegato a un abbonamento Stripe, quindi non c'è nulla da gestire qui.
                        </p>
                    <?php else: ?>
                        <!-- Mostra questo se l'utente è sul piano Free -->
                        <p class="text-gray-400 mb-4">
                            Attualmente sei sul piano Free. Passa a Pro per sbloccare tutte le funzionalità.
                        </p>
                        <a href="pricing.php" class="block w-full text-center bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-lg">
                            Passa a Bearget Pro
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('La nuova password e la conferma non coincidono.');
            }
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