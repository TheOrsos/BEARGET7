<?php
session_start();
header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Utente non autorizzato.']);
    exit;
}

require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$data = json_decode(file_get_contents('php://input'), true);

// Validazione input
if (!isset($data['name']) || !isset($data['cost']) || !isset($data['date'])) {
    echo json_encode(['success' => false, 'error' => 'Dati mancanti.']);
    exit;
}

$name = trim($data['name']);
$target_amount = floatval($data['cost']);
$target_date_str = $data['date'];

if (empty($name) || $target_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dati non validi.']);
    exit;
}

try {
    $target_date = new DateTime($target_date_str);
    $current_date = new DateTime();

    // L'obiettivo deve essere nel futuro
    if ($target_date <= $current_date) {
        echo json_encode(['success' => false, 'error' => 'La data obiettivo deve essere futura.']);
        exit;
    }

    // Calcola i mesi mancanti
    $interval = $current_date->diff($target_date);
    $months_away = ($interval->y * 12) + $interval->m;
    if ($interval->d > 0) {
        $months_away++;
    }
    
    if ($months_away <= 0) {
         echo json_encode(['success' => false, 'error' => 'L\'obiettivo è troppo vicino per calcolare un piano di risparmio mensile.']);
         exit;
    }

    $monthly_contribution = round($target_amount / $months_away, 2);

    // --- CONTROLLO DI FATTIBILITÀ MIGLIORATO ---
    // 1. Calcola le entrate mensili ricorrenti totali dell'utente
    $stmt_income = $conn->prepare("SELECT SUM(amount) as total_income FROM recurring_transactions WHERE user_id = ? AND type = 'income' AND frequency = 'monthly'");
    $stmt_income->bind_param("i", $user_id);
    $stmt_income->execute();
    $result_income = $stmt_income->get_result();
    $income_row = $result_income->fetch_assoc();
    $total_monthly_income = $income_row['total_income'] ?? 0;
    $stmt_income->close();

    // 2. Calcola i contributi mensili per gli obiettivi di risparmio GIÀ ESISTENTI
    $stmt_goals = $conn->prepare("SELECT SUM(monthly_contribution) as total_contributions FROM saving_goals WHERE user_id = ?");
    $stmt_goals->bind_param("i", $user_id);
    $stmt_goals->execute();
    $result_goals = $stmt_goals->get_result();
    $goals_row = $result_goals->fetch_assoc();
    $existing_monthly_contributions = $goals_row['total_contributions'] ?? 0;
    $stmt_goals->close();

    // 3. Calcola il totale dei risparmi pianificati (esistenti + nuovo)
    $total_planned_savings = $existing_monthly_contributions + $monthly_contribution;

    // 4. Verifica se il totale dei risparmi è sostenibile (es. non più dell'80% delle entrate)
    if ($total_monthly_income > 0 && $total_planned_savings > ($total_monthly_income * 0.8)) {
        // --- MESSAGGIO DI ERRORE DETTAGLIATO ---
        $error_message = "Impossibile creare l'obiettivo: il totale dei tuoi risparmi mensili pianificati (€".number_format($total_planned_savings, 2, ',', '.').") supererebbe l'80% delle tue entrate mensili (€".number_format($total_monthly_income, 2, ',', '.').")."
                       . "<br><br><small><strong>Dettaglio:</strong> €".number_format($existing_monthly_contributions, 2, ',', '.')." (obiettivi esistenti) + €".number_format($monthly_contribution, 2, ',', '.')." (questo obiettivo).</small>";
        
        echo json_encode([
            'success' => false, 
            'error' => $error_message
        ]);
        exit;
    }
    // --- FINE CONTROLLO ---


    $conn->begin_transaction();

    // 1. Crea una nuova categoria di spesa per questo obiettivo
    $category_name = "Risparmio: " . $name;
    $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, 'expense', 'piggy-bank')");
    $stmt->bind_param("is", $user_id, $category_name);
    $stmt->execute();
    $category_id = $stmt->insert_id;
    $stmt->close();

    // 2. Imposta un budget mensile per questa nuova categoria
    $stmt = $conn->prepare("INSERT INTO budgets (user_id, category_id, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $user_id, $category_id, $monthly_contribution);
    $stmt->execute();
    $stmt->close();

    // 3. Crea l'obiettivo nella tabella 'saving_goals' con il nuovo marcatore
    $stmt = $conn->prepare("INSERT INTO saving_goals (user_id, name, target_amount, current_amount, target_date, monthly_contribution, linked_category_id, created_by_planner) VALUES (?, ?, ?, 0.00, ?, ?, ?, 1)");
    $stmt->bind_param("isdsdi", $user_id, $name, $target_amount, $target_date_str, $monthly_contribution, $category_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Obiettivo '".htmlspecialchars($name)."' creato! Risparmierai ".number_format($monthly_contribution, 2, ',', '.')." €/mese."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    // Mostra l'errore effettivo per il debug
    echo json_encode(['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()]);
}

$conn->close();
?>