<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Utente non autorizzato.']);
    exit;
}

require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$payload = json_decode(file_get_contents('php://input'), true);
$items = $payload['items'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Nessun articolo da analizzare.']);
    exit;
}

try {
    // 1. Calcola il saldo corrente totale di tutti i conti
    $current_balance = get_total_balance($conn, $user_id);

    // 2. Simula l'impatto degli acquisti
    $final_balance = $current_balance;
    $analysis_points = [];
    
    // Ordina gli acquisti per data
    usort($items, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    $analysis_points[] = [
        'icon' => '💰',
        'text' => "Parti da un saldo totale di <strong>" . number_format($current_balance, 2, ',', '.') . " €</strong>."
    ];

    foreach ($items as $item) {
        $cost = 0;
        $item_name = htmlspecialchars($item['name']);
        $item_date = date("d/m/Y", strtotime($item['date']));

        if ($item['type'] === 'immediate') {
            $cost = $item['cost'];
            $final_balance -= $cost;
            $analysis_points[] = [
                'icon' => '💸',
                'text' => "L'acquisto di <strong>{$item_name}</strong> il {$item_date} ridurrà il tuo saldo di <strong>" . number_format($cost, 2, ',', '.') . " €</strong>."
            ];
        } elseif ($item['type'] === 'installment') {
            $down_payment = $item['details']['downPayment'] ?? 0;
            $cost = $down_payment;
            $final_balance -= $cost;
            $monthly_cost = $item['details']['monthlyCost'] ?? 0;
            $months = $item['details']['months'] ?? 0;
            $analysis_points[] = [
                'icon' => '💳',
                'text' => "Per <strong>{$item_name}</strong>, l'anticipo di <strong>" . number_format($cost, 2, ',', '.') . " €</strong> e le rate da <strong>" . number_format($monthly_cost, 2, ',', '.') . " €</strong> per {$months} mesi incideranno sul tuo budget futuro."
            ];
        }
        // Gli obiettivi 'goal' non hanno un impatto immediato sul saldo perché vengono gestiti dal budget mensile
    }

    $analysis_points[] = [
        'icon' => '📊',
        'text' => "Dopo questi acquisti, il tuo saldo stimato sarà di <strong>" . number_format($final_balance, 2, ',', '.') . " €</strong>."
    ];

    // 3. Determina il verdetto finale
    $verdict = 'positive';
    $summary = 'Sì, puoi permetterti questi acquisti!';
    if ($final_balance < 0) {
        $verdict = 'danger';
        $summary = 'Attenzione, andresti in rosso!';
    } elseif ($final_balance < ($current_balance * 0.1)) { // Se il saldo scende sotto il 10%
        $verdict = 'warning';
        $summary = 'Puoi permettertelo, ma il tuo saldo scenderà notevolmente.';
    }

    // 4. Recupera gli obiettivi di risparmio in corso per dare contesto
    $ongoing_goals = [];
    $stmt = $conn->prepare("SELECT name, target_amount, current_amount, monthly_contribution FROM saving_goals WHERE user_id = ? AND current_amount < target_amount");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ongoing_goals[] = $row;
    }
    $stmt->close();


    echo json_encode([
        'verdict' => $verdict,
        'summary' => $summary,
        'analysis' => $analysis_points,
        'ongoing_goals' => $ongoing_goals // Aggiungo gli obiettivi alla risposta
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Errore del server durante l\'analisi: ' . $e->getMessage()]);
}

$conn->close();
?>