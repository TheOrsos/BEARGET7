<?php
// File: update_goal.php (Versione AJAX Modificata)
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $goal_id = trim($_POST['goal_id']);
    $name = trim($_POST['name']);
    $target_amount = trim($_POST['target_amount']);
    // Recupera la data di scadenza. Se è vuota, la imposta a NULL.
    $target_date = !empty($_POST['target_date']) ? trim($_POST['target_date']) : NULL;

    if (empty($goal_id) || empty($name) || !is_numeric($target_amount) || $target_amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    // Query SQL aggiornata per includere target_date
    $sql = "UPDATE saving_goals SET name = ?, target_amount = ?, target_date = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        // Bind dei parametri aggiornato (s: string, d: double, s: string, i: integer, i: integer)
        $stmt->bind_param("sdsii", $name, $target_amount, $target_date, $goal_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Obiettivo aggiornato!',
                'goal' => [
                    'id' => intval($goal_id),
                    'name' => $name,
                    'target_amount' => floatval($target_amount),
                    'target_date' => $target_date // Restituisce la data al frontend
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>