<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fund_id'])) {
    $fund_id = $_POST['fund_id'];
    $user_id = $_SESSION['id'];

    $conn->begin_transaction();

    try {
        // --- 1. Security and Fund Status Check ---
        $fund = get_shared_fund_details($conn, $fund_id, $user_id);
        if (!$fund || $fund['creator_id'] != $user_id) {
            throw new Exception("Azione non autorizzata.");
        }
        if ($fund['status'] !== 'settling_auto') {
            throw new Exception("Questo fondo non è in modalità di saldaconto automatico.");
        }

        $settlement_payments = get_settlement_payments($conn, $fund_id);

        // --- 2. Validation: Ensure all accounts are selected ---
        foreach ($settlement_payments as $payment) {
            // Skip withdrawals, only check P2P payments
            if ($payment['from_user_id'] != $payment['to_user_id']) {
                if (empty($payment['from_account_id']) || empty($payment['to_account_id'])) {
                    throw new Exception("Non tutti i membri hanno selezionato il proprio conto per il trasferimento.");
                }
            }
        }

        // --- 3. Get or Create Categories for Settlement Transactions ---
        // This part can be refactored to a helper function if used elsewhere
        $category_name = "Regolamento Fondo";

        $expense_category = get_category_by_name_and_type($conn, $category_name, $user_id, 'expense');
        if (!$expense_category) {
            $sql_create_cat_exp = "INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, 'expense', '⚖️')";
            $stmt_create_cat_exp = $conn->prepare($sql_create_cat_exp);
            $stmt_create_cat_exp->bind_param("is", $user_id, $category_name);
            $stmt_create_cat_exp->execute();
            $expense_category_id = $stmt_create_cat_exp->insert_id;
            $stmt_create_cat_exp->close();
        } else {
            $expense_category_id = $expense_category['id'];
        }

        $income_category = get_category_by_name_and_type($conn, $category_name, $user_id, 'income');
        if (!$income_category) {
            $sql_create_cat_inc = "INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, 'income', '⚖️')";
            $stmt_create_cat_inc = $conn->prepare($sql_create_cat_inc);
            $stmt_create_cat_inc->bind_param("is", $user_id, $category_name);
            $stmt_create_cat_inc->execute();
            $income_category_id = $stmt_create_cat_inc->insert_id;
            $stmt_create_cat_inc->close();
        } else {
            $income_category_id = $income_category['id'];
        }

        // --- 4. Process each payment and create transactions ---
        $sql_insert_tx = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_tx = $conn->prepare($sql_insert_tx);

        $today = date('Y-m-d');
        $type_expense = 'expense';
        $type_income = 'income';

        foreach ($settlement_payments as $payment) {
            if ($payment['from_user_id'] == $payment['to_user_id']) continue; // Skip withdrawals

            // Create Expense Transaction for Payer
            $expense_amount = -$payment['amount'];
            $expense_desc = "Pagamento a " . htmlspecialchars($payment['to_username']) . " per fondo '" . htmlspecialchars($fund['name']) . "'";
            $stmt_insert_tx->bind_param("iiidsss", $payment['from_user_id'], $payment['from_account_id'], $expense_category_id, $expense_amount, $type_expense, $expense_desc, $today);
            $stmt_insert_tx->execute();

            // Create Income Transaction for Payee
            $income_desc = "Pagamento da " . htmlspecialchars($payment['from_username']) . " per fondo '" . htmlspecialchars($fund['name']) . "'";
            $stmt_insert_tx->bind_param("iiidsss", $payment['to_user_id'], $payment['to_account_id'], $income_category_id, $payment['amount'], $type_income, $income_desc, $today);
            $stmt_insert_tx->execute();
        }
        $stmt_insert_tx->close();

        // --- 5. Archive the fund and clean up ---
        $sql_archive = "UPDATE shared_funds SET status = 'archived' WHERE id = ?";
        $stmt_archive = $conn->prepare($sql_archive);
        $stmt_archive->bind_param("i", $fund_id);
        $stmt_archive->execute();
        $stmt_archive->close();

        // Clean up settlement_payments table for this fund
        $sql_delete = "DELETE FROM settlement_payments WHERE fund_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $fund_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        $conn->commit();
        header("location: fund_details.php?id=" . $fund_id . "&message=Saldaconto completato e transazioni create con successo!&type=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("location: fund_details.php?id=" . $fund_id . "&message=Errore: " . urlencode($e->getMessage()) . "&type=error");
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    header("location: shared_funds.php");
    exit;
}
?>