<?php
// File: add_goal.php (Versione AJAX Modificata)
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
    $name = trim($_POST['name']);
    $target_amount = trim($_POST['target_amount']);
    // Recupera la data di scadenza. Se è vuota, la imposta a NULL.
    $target_date = !empty($_POST['target_date']) ? trim($_POST['target_date']) : NULL;

    if (empty($name) || !is_numeric($target_amount) || $target_amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    // Query SQL aggiornata per includere target_date
    $sql = "INSERT INTO saving_goals (user_id, name, target_amount, target_date) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        // Bind dei parametri aggiornato (i: integer, s: string, d: double, s: string)
        $stmt->bind_param("isds", $user_id, $name, $target_amount, $target_date);
        
        if ($stmt->execute()) {
            $new_goal_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Obiettivo creato!',
                'goal' => [
                    'id' => $new_goal_id,
                    'name' => $name,
                    'target_amount' => floatval($target_amount),
                    'current_amount' => 0.00,
                    'target_date' => $target_date // Restituisce la data al frontend
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante la creazione dell\'obiettivo.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>