<?php
// Questo file mostra il modulo per richiedere il reset della password.
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupera Password - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-2xl shadow-lg">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">Recupera la tua password</h2>
            <p class="mt-2 text-sm text-gray-600">Inserisci la tua email e ti invieremo un link per resettarla.</p>
        </div>

        <!-- Messaggi di Errore/Successo -->
        <?php if (isset($_GET['message'])): ?>
            <div class="p-4 text-sm rounded-lg <?php echo $_GET['type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
            </div>
        <?php endif; ?>

        <!-- Link per il reset (per debug) -->
        <?php if (isset($_GET['reset_link'])): ?>
             <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">L'invio dell'email potrebbe non funzionare. Usa questo link per procedere:</p>
                <a href="<?php echo htmlspecialchars(urldecode($_GET['reset_link'])); ?>" class="inline-block w-full justify-center rounded-md border border-transparent bg-green-600 py-3 px-4 text-sm font-semibold text-white hover:bg-green-700">
                    Resetta la tua password
                </a>
            </div>
        <?php else: ?>
            <form class="mt-8 space-y-6" action="send_reset_link.php" method="POST">
                <div>
                    <label for="email" class="sr-only">Indirizzo email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="relative block w-full appearance-none rounded-md border border-gray-300 px-3 py-3 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Il tuo indirizzo email">
                </div>
                <div>
                    <button type="submit" class="group relative flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-3 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Invia link di recupero
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="text-sm text-center">
            <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500">Torna al Login</a>
        </div>
    </div>
</body>
</html>