<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    exit("Accesso non autorizzato.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $fund_id = trim($_POST['fund_id']);
    $amount = trim($_POST['amount']);
    $account_id = trim($_POST['account_id']);
    $category_id = trim($_POST['category_id']); // Categoria "Fondi Comuni"

    if (empty($fund_id) || empty($account_id) || !is_numeric($amount) || $amount <= 0) {
        header("location: fund_details.php?id={$fund_id}&message=Dati non validi.&type=error");
        exit();
    }

    $conn->begin_transaction();
    try {
        // 1. Aggiungi il contributo al fondo
        $sql_contrib = "INSERT INTO shared_fund_contributions (fund_id, user_id, amount, contribution_date) VALUES (?, ?, ?, ?)";
        $stmt_contrib = $conn->prepare($sql_contrib);
        $today = date('Y-m-d');
        $stmt_contrib->bind_param("iids", $fund_id, $user_id, $amount, $today);
        $stmt_contrib->execute();
        $contribution_id = $stmt_contrib->insert_id;
        $stmt_contrib->close();

        // 2. Crea una transazione di spesa personale per tracciare il movimento
        if (!empty($category_id)) {
            $sql_tx = "INSERT INTO transactions (user_id, account_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, 'expense', ?, ?)";
            $stmt_tx = $conn->prepare($sql_tx);
            
            $fund_name_sql = "SELECT name FROM shared_funds WHERE id = ?";
            $stmt_fund_name = $conn->prepare($fund_name_sql);
            $stmt_fund_name->bind_param("i", $fund_id);
            $stmt_fund_name->execute();
            $fund_name = $stmt_fund_name->get_result()->fetch_assoc()['name'];
            $stmt_fund_name->close();
            
            $description = "Contributo a fondo: " . $fund_name;
            $negative_amount = -abs($amount);
            $stmt_tx->bind_param("iiidss", $user_id, $account_id, $category_id, $negative_amount, $description, $today);
            $stmt_tx->execute();
            $transaction_id = $stmt_tx->insert_id;
            $stmt_tx->close();

            // 3. Collega la transazione al contributo
            $sql_update_contrib = "UPDATE shared_fund_contributions SET transaction_id = ? WHERE id = ?";
            $stmt_update_contrib = $conn->prepare($sql_update_contrib);
            $stmt_update_contrib->bind_param("ii", $transaction_id, $contribution_id);
            $stmt_update_contrib->execute();
            $stmt_update_contrib->close();
        }

        $conn->commit();
        header("location: fund_details.php?id={$fund_id}&message=Contributo aggiunto!&type=success");
    } catch (Exception $e) {
        $conn->rollback();
        header("location: fund_details.php?id={$fund_id}&message=Errore durante l'operazione.&type=error");
    }
    $conn->close();
    exit();
}
?>