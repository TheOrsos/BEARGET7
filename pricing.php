<?php
/*
================================================================================
File: pricing.php (Versione Semplificata con Payment Link)
================================================================================
*/
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Recupera l'ID dell'utente per passarlo a Stripe
$user_id = $_SESSION['id'];

// *** INCOLLA QUI IL TUO PAYMENT LINK DI STRIPE ***
$stripe_payment_link = "https://buy.stripe.com/test_28E9ATfAN0HY5aj9I4ejK00";

// Aggiungiamo l'ID dell'utente al link per poterlo identificare dopo il pagamento
$final_link = $stripe_payment_link . "?client_reference_id=" . $user_id;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Abbonati a Bearget Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="theme.php">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: { 500: 'var(--color-primary-500)', 600: 'var(--color-primary-600)', 700: 'var(--color-primary-700)' },
              gray: { 900: 'var(--color-gray-900)', 800: 'var(--color-gray-800)' },
            }
          }
        }
      }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); } </style>
</head>
<body class="text-gray-300">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-2xl p-8 max-w-md w-full text-center">
            <h1 class="text-3xl font-bold text-white">Passa a Bearget Pro</h1>
            <p class="text-gray-400 mt-2">Sblocca tutte le funzionalità per un controllo totale.</p>
            
            <div class="my-8">
                <span class="text-5xl font-extrabold text-white">€4,99</span>
                <span class="text-gray-400">/ mese</span>
            </div>

            <ul class="space-y-3 text-left text-gray-300 mb-8">
                <li class="flex items-center">✓ Report, Budget e Obiettivi</li>
                <li class="flex items-center">✓ Transazioni Ricorrenti</li>
                <li class="flex items-center">✓ Fondi Comuni con gli amici</li>
                <li class="flex items-center">✓ Note con To-Do List</li>
            </ul>

            <!-- Il pulsante ora è un semplice link al tuo Payment Link di Stripe -->
            <a href="<?php echo htmlspecialchars($final_link); ?>" class="block w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-lg text-lg transition-colors">
                Abbonati Ora
            </a>
            
            <a href="dashboard.php" class="block mt-4 text-sm text-gray-500 hover:text-gray-400">Torna alla dashboard</a>
        </div>
    </div>
</body>
</html>