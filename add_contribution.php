<?php
// File: add_contribution.php (Versione AJAX)
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $goal_id = trim($_POST['goal_id']);
    $amount = trim($_POST['amount']);
    $account_id = trim($_POST['account_id']);
    $category_id = trim($_POST['category_id']);

    if (empty($goal_id) || empty($account_id) || !is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1. Aggiungi l'importo al totale dell'obiettivo
        $sql_update_goal = "UPDATE saving_goals SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update_goal);
        $stmt_update->bind_param("dii", $amount, $goal_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 2. Crea una transazione di spesa per tracciare il movimento
        if (!empty($category_id)) {
            $goal_name_sql = "SELECT name FROM saving_goals WHERE id = ?";
            $stmt_goal_name = $conn->prepare($goal_name_sql);
            $stmt_goal_name->bind_param("i", $goal_id);
            $stmt_goal_name->execute();
            $goal_name = $stmt_goal_name->get_result()->fetch_assoc()['name'];
            $stmt_goal_name->close();
            
            $description = "Contributo a: " . $goal_name;
            $negative_amount = -abs($amount);
            $today = date('Y-m-d');

            $sql_insert_tx = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, 'expense', ?, ?)";
            $stmt_tx = $conn->prepare($sql_insert_tx);
            $stmt_tx->bind_param("iiidss", $user_id, $account_id, $category_id, $negative_amount, $description, $today);
            $stmt_tx->execute();
            $stmt_tx->close();
        }

        // 3. Recupera il nuovo importo corrente dell'obiettivo
        $goal = get_goal_by_id($conn, $goal_id, $user_id);
        
        $conn->commit();

        // 4. Calcola i nuovi totali per la dashboard
        $totalBalance = get_total_balance($conn, $user_id);
        $monthlySummary = get_monthly_summary($conn, $user_id);

        echo json_encode([
            'success' => true,
            'message' => 'Contributo aggiunto!',
            'goal' => [
                'id' => $goal_id,
                'current_amount' => floatval($goal['current_amount']),
                'target_amount' => floatval($goal['target_amount'])
            ],
            'summary' => [
                'totalBalance' => $totalBalance,
                'monthlyExpenses' => abs($monthlySummary['expenses'])
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'operazione: ' . $e->getMessage()]);
    }
    $conn->close();
    exit();
}
?>