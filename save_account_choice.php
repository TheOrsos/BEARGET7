<?php
session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';
require_once 'functions.php';

$response = ['success' => false, 'message' => 'Richiesta non valida.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $payment_id = $_POST['payment_id'] ?? 0;
    $account_id = $_POST['account_id'] ?? 0;
    $type = $_POST['type'] ?? ''; // 'from' or 'to'

    if (empty($payment_id) || empty($account_id) || ($type !== 'from' && $type !== 'to')) {
        $response['message'] = 'Dati mancanti o non validi.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Get payment details and verify user permission
        $sql_get_payment = "SELECT * FROM settlement_payments WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get_payment);
        $stmt_get->bind_param("i", $payment_id);
        $stmt_get->execute();
        $payment = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$payment) {
            throw new Exception("Pagamento non trovato.");
        }

        $is_payer = ($user_id == $payment['from_user_id']);
        $is_payee = ($user_id == $payment['to_user_id']);

        if (($type === 'from' && !$is_payer) || ($type === 'to' && !$is_payee)) {
            throw new Exception("Non hai i permessi per modificare questo pagamento.");
        }

        // 2. Verify the account belongs to the user
        $account = get_account_by_id($conn, $account_id, $user_id);
        if (!$account) {
            throw new Exception("Conto non valido o non appartenente a te.");
        }

        // 3. Update the correct column
        $column_to_update = ($type === 'from') ? 'from_account_id' : 'to_account_id';

        $sql_update = "UPDATE settlement_payments SET $column_to_update = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $account_id, $payment_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Scelta del conto salvata.';
        } else {
            throw new Exception("Nessuna modifica effettuata. Il conto potrebbe essere già stato selezionato.");
        }

        $stmt_update->close();
        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    } finally {
        if(isset($conn)) $conn->close();
    }
} else {
    $response['message'] = 'Utente non autenticato.';
}

echo json_encode($response);
?>