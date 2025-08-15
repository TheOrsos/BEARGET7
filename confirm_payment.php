<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_id'])) {
    $payment_id = $_POST['payment_id'];
    $user_id = $_SESSION['id'];

    $conn->begin_transaction();

    try {
        // --- Get Payment Details ---
        $sql_get_payment = "SELECT * FROM settlement_payments WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get_payment);
        $stmt_get->bind_param("i", $payment_id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $payment = $result->fetch_assoc();
        $stmt_get->close();

        if (!$payment) {
            throw new Exception("Pagamento non trovato.");
        }

        $fund_id = $payment['fund_id'];

        // --- Security Check ---
        // Only the payer or the payee can confirm the payment.
        if ($user_id != $payment['from_user_id'] && $user_id != $payment['to_user_id']) {
            throw new Exception("Solo il pagatore o il ricevente possono confermare questo pagamento.");
        }

        if ($payment['status'] !== 'pending') {
            throw new Exception("Questo pagamento è già stato confermato.");
        }

        // --- Update Payment Status ---
        $sql_update = "UPDATE settlement_payments SET status = 'paid', paid_at = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $payment_id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();
        header("location: fund_details.php?id=" . $fund_id . "&message=Pagamento confermato con successo!&type=success");

    } catch (Exception $e) {
        $conn->rollback();
        // Try to get fund_id from payment if it exists, otherwise redirect to main page
        $redirect_url = isset($fund_id) ? "fund_details.php?id=" . $fund_id : "shared_funds.php";
        header("location: " . $redirect_url . "&message=Errore: " . $e->getMessage() . "&type=error");
    } finally {
        $conn->close();
    }
} else {
    header("location: shared_funds.php");
    exit;
}
?>