<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$fund_id = $_GET['id'] ?? 0;
$current_page = 'shared_funds';

// Recupera i dettagli del fondo, ma solo se l'utente attuale ne è membro
$fund = get_shared_fund_details($conn, $fund_id, $user_id);
if (!$fund) {
    header("location: shared_funds.php?message=Fondo non trovato o accesso non autorizzato.&type=error");
    exit;
}

// Get data for charts
$expenses_by_category = get_expenses_by_category_for_fund($conn, $fund_id);
$member_stats = get_member_stats_for_fund($conn, $fund_id);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Fondo - <?php echo htmlspecialchars($fund['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">Statistiche: <?php echo htmlspecialchars($fund['name']); ?></h1>
                    <p class="text-gray-400 mt-1">Analisi delle spese e dei contributi del fondo.</p>
                </div>
                <a href="fund_details.php?id=<?php echo $fund_id; ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg">
                    &larr; Torna al Fondo
                </a>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Expenses by Category Chart -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Spese per Categoria</h2>
                    <canvas id="expensesByCategoryChart"></canvas>
                </div>

                <!-- Member Stats Chart -->
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Riepilogo Membri</h2>
                    <canvas id="memberStatsChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart 1: Expenses by Category
            const ctxCategory = document.getElementById('expensesByCategoryChart').getContext('2d');
            new Chart(ctxCategory, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($expenses_by_category, 'category_name')); ?>,
                    datasets: [{
                        label: 'Spese per Categoria',
                        data: <?php echo json_encode(array_column($expenses_by_category, 'total_amount')); ?>,
                        backgroundColor: [
                            '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
                            '#6366F1', '#EC4899', '#F97316', '#06B6D4', '#14B8A6'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#D1D5DB'
                            }
                        }
                    }
                }
            });

            // Chart 2: Member Stats
            const ctxMembers = document.getElementById('memberStatsChart').getContext('2d');
            new Chart(ctxMembers, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($member_stats, 'username')); ?>,
                    datasets: [
                        {
                            label: 'Totale Pagato',
                            data: <?php echo json_encode(array_column($member_stats, 'total_paid')); ?>,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Totale Contribuito',
                            data: <?php echo json_encode(array_column($member_stats, 'total_contributed')); ?>,
                            backgroundColor: 'rgba(16, 185, 129, 0.5)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#D1D5DB' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        x: {
                            ticks: { color: '#D1D5DB' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#D1D5DB'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>