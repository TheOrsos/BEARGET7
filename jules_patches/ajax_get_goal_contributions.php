<?php
// File: ajax_get_goal_contributions.php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

$user_id = $_SESSION["id"];
$goal_id = $_GET['goal_id'] ?? null;

if (empty($goal_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID obiettivo mancante.']);
    exit();
}

try {
    // 1. Ottieni il nome dell'obiettivo per costruire la descrizione da cercare
    $goal = get_goal_by_id($conn, $goal_id, $user_id);
    if (!$goal) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Obiettivo non trovato.']);
        exit();
    }
    $goal_name = $goal['name'];
    $search_description = "Contributo a: " . $goal_name;

    // 2. Trova tutte le transazioni che corrispondono a questo contributo
    $sql = "SELECT
                t.id,
                t.amount,
                t.transaction_date,
                a.name as account_name
            FROM transactions t
            JOIN accounts a ON t.account_id = a.id
            WHERE t.user_id = ?
            AND t.description = ?
            AND t.type = 'expense'
            ORDER BY t.transaction_date DESC, t.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $search_description);
    $stmt->execute();
    $result = $stmt->get_result();

    $contributions = [];
    while ($row = $result->fetch_assoc()) {
        $contributions[] = $row;
    }

    $stmt->close();

    echo json_encode(['success' => true, 'contributions' => $contributions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
}

$conn->close();
?>
