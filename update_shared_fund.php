<?php
// File: update_shared_fund.php (NUOVO FILE)
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
    $fund_id = trim($_POST['fund_id']);
    $name = trim($_POST['name']);
    $target_amount = trim($_POST['target_amount']);

    if (empty($fund_id) || empty($name) || !is_numeric($target_amount) || $target_amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "UPDATE shared_funds SET name = ?, target_amount = ? WHERE id = ? AND creator_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdii", $name, $target_amount, $fund_id, $user_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                 echo json_encode([
                    'success' => true, 
                    'message' => 'Fondo aggiornato con successo!',
                    'fund' => ['id' => $fund_id, 'name' => $name, 'target_amount' => floatval($target_amount)]
                ]);
            } else {
                 echo json_encode(['success' => true, 'message' => 'Nessuna modifica rilevata.', 'fund' => ['id' => $fund_id, 'name' => $name, 'target_amount' => floatval($target_amount)]]);
            }
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