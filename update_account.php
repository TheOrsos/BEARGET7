<?php
// File: update_account.php (NUOVO FILE - Versione AJAX)
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
    $account_id = trim($_POST['account_id']);
    $name = trim($_POST['name']);
    $initial_balance = trim($_POST['initial_balance']);

    if (empty($account_id) || empty($name) || !is_numeric($initial_balance)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "UPDATE accounts SET name = ?, initial_balance = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdii", $name, $initial_balance, $account_id, $user_id);
        if ($stmt->execute()) {
            $updated_balance = get_account_balance($conn, $account_id);
            echo json_encode([
                'success' => true,
                'message' => 'Conto aggiornato!',
                'account' => [
                    'id' => intval($account_id),
                    'name' => $name,
                    'initial_balance' => floatval($initial_balance),
                    'balance' => $updated_balance
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