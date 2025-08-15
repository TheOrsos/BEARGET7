<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fund_id'])) {
    $fund_id = $_POST['fund_id'];
    $user_id = $_SESSION['id'];

    $conn->begin_transaction();

    try {
        // --- Security Check ---
        $fund = get_shared_fund_details($conn, $fund_id, $user_id);
        if (!$fund || $fund['creator_id'] != $user_id) {
            throw new Exception("Azione non autorizzata.");
        }
        if ($fund['status'] !== 'settling') {
            throw new Exception("Il fondo deve essere in fase di chiusura per essere archiviato.");
        }

        // --- Check if all payments are confirmed ---
        $sql_check_payments = "SELECT COUNT(*) as pending_count FROM settlement_payments WHERE fund_id = ? AND status = 'pending'";
        $stmt_check = $conn->prepare($sql_check_payments);
        $stmt_check->bind_param("i", $fund_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row = $result->fetch_assoc();
        $stmt_check->close();

        if ($row['pending_count'] > 0) {
            throw new Exception("Non tutti i pagamenti sono stati confermati. Impossibile archiviare il fondo.");
        }

        // --- Update Fund Status ---
        $sql_update_status = "UPDATE shared_funds SET status = 'archived' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_status);
        $stmt_update->bind_param("i", $fund_id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();
        header("location: fund_details.php?id=" . $fund_id . "&message=Fondo archiviato con successo!&type=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("location: fund_details.php?id=" . $fund_id . "&message=Errore: " . $e->getMessage() . "&type=error");
    } finally {
        $conn->close();
    }
} else {
    header("location: shared_funds.php");
    exit;
}
?>