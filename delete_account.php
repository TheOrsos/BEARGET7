<?php
// File: delete_account.php (Versione AJAX)
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
    $account_id = $_POST['account_id'];

    if (empty($account_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID conto mancante.']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $sql_delete_acc = "DELETE FROM accounts WHERE id = ? AND user_id = ?";
        $stmt_acc = $conn->prepare($sql_delete_acc);
        $stmt_acc->bind_param("ii", $account_id, $user_id);
        $stmt_acc->execute();
        
        if ($stmt_acc->affected_rows == 0) {
            throw new Exception("Impossibile eliminare il conto o permesso negato.");
        }
        $stmt_acc->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Conto eliminato con successo.']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conn->close();
    exit();
}
?>