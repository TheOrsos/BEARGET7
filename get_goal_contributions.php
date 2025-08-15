<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Utente non autorizzato.']);
    exit;
}

require_once 'db_connect.php';

$user_id = $_SESSION["id"];
$goal_id = isset($_GET['goal_id']) ? intval($_GET['goal_id']) : 0;

if ($goal_id === 0) {
    echo json_encode(['success' => false, 'error' => 'ID obiettivo non valido.']);
    exit;
}

try {
    // Seleziona tutte le transazioni collegate direttamente all'obiettivo tramite goal_id
    $sql = "SELECT 
                ABS(t.amount) as amount,
                t.transaction_date,
                a.name as account_name
            FROM 
                transactions t
            JOIN 
                accounts a ON t.account_id = a.id
            WHERE 
                t.user_id = ? AND t.goal_id = ?
            ORDER BY 
                t.transaction_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $goal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contributions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'contributions' => $contributions]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()]);
}

$conn->close();
?>
