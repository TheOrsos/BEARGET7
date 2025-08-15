<?php
// File: add_account.php (Versione AJAX)
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
    $initial_balance = trim($_POST['initial_balance']);

    if (empty($name) || !is_numeric($initial_balance)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
        exit();
    }

    $sql = "INSERT INTO accounts (user_id, name, initial_balance) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isd", $user_id, $name, $initial_balance);
        if ($stmt->execute()) {
            $new_account_id = $conn->insert_id;
            $new_balance = get_account_balance($conn, $new_account_id);
            echo json_encode([
                'success' => true,
                'message' => 'Conto aggiunto con successo!',
                'account' => [
                    'id' => $new_account_id,
                    'name' => $name,
                    'balance' => $new_balance
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio.']);
        }
        $stmt->close();
    }
    $conn->close();
    exit();
}
?>