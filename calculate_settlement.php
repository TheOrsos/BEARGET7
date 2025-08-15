<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fund_id'])) {
    $fund_id = $_POST['fund_id'];
    $user_id = $_SESSION['id'];
    $auto_settle = isset($_POST['auto_settle']) && $_POST['auto_settle'] == '1';

    $conn->begin_transaction();

    try {
        // --- 1. Security and Status Check ---
        $fund = get_shared_fund_details($conn, $fund_id, $user_id);
        if (!$fund || $fund['creator_id'] != $user_id) {
            throw new Exception("Azione non autorizzata.");
        }
        if ($fund['status'] !== 'active') {
            throw new Exception("Il fondo non è attivo e non può essere saldato.");
        }

        // --- 2. Calculate Final Balances ---
        $expense_balances = get_group_balances($conn, $fund_id);
        $net_contributions = get_net_contributions_by_user($conn, $fund_id);

        $final_balances = [];
        $all_user_ids = array_unique(array_merge(array_column($expense_balances, 'user_id'), array_keys($net_contributions)));

        foreach ($all_user_ids as $uid) {
            $expense_balance = 0;
            $username = ''; // Initialize username
            foreach ($expense_balances as $b) {
                if ($b['user_id'] == $uid) {
                    $expense_balance = $b['balance'];
                    $username = $b['username'];
                    break;
                }
            }
             // If username is not found in expense balances (e.g., user only contributed), get it from DB
            if (empty($username)) {
                $user_data = get_user_by_id($conn, $uid);
                $username = $user_data['username'] ?? 'Utente Sconosciuto';
            }


            $net_contribution = $net_contributions[$uid] ?? 0;

            $final_balances[] = [
                'user_id' => $uid,
                'username' => $username,
                'balance' => $expense_balance + $net_contribution
            ];
        }

        // --- 3. Simplify Debts to get Peer-to-Peer Payments ---
        $p2p_payments = simplify_debts($final_balances);

        // --- 4. Calculate Final Balances After P2P Payments ---
        $balances_after_p2p = [];
        foreach($final_balances as $b) {
            $balances_after_p2p[$b['user_id']] = $b['balance'];
        }

        foreach ($p2p_payments as $p) {
            $balances_after_p2p[$p['from']] -= $p['amount'];
            $balances_after_p2p[$p['to']] += $p['amount'];
        }

        // --- 5. Identify Cash Withdrawals ---
        $cash_withdrawals = [];
        foreach ($balances_after_p2p as $uid => $balance) {
            if ($balance > 0.01) { // Use a small epsilon for float comparison
                $cash_withdrawals[] = [
                    'from' => $uid, // Convention: from == to for withdrawal
                    'to' => $uid,
                    'amount' => round($balance, 2)
                ];
            }
        }

        // --- 6. Store All Settlement Payments (P2P and Withdrawals) ---
        $sql_insert_payment = "INSERT INTO settlement_payments (fund_id, from_user_id, to_user_id, amount) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_payment);

        if ($stmt_insert === false) {
            throw new Exception("Impossibile preparare la query per l'inserimento dei pagamenti.");
        }

        // Store P2P payments
        foreach ($p2p_payments as $payment) {
            $stmt_insert->bind_param("iiid", $fund_id, $payment['from'], $payment['to'], $payment['amount']);
            $stmt_insert->execute();
        }

        // Store cash withdrawals
        foreach ($cash_withdrawals as $withdrawal) {
            $stmt_insert->bind_param("iiid", $fund_id, $withdrawal['from'], $withdrawal['to'], $withdrawal['amount']);
            $stmt_insert->execute();
        }
        $stmt_insert->close();

        // --- 7. Update Fund Status ---
        $new_status = $auto_settle ? 'settling_auto' : 'settling';
        $sql_update_status = "UPDATE shared_funds SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_status);
        if ($stmt_update === false) {
            throw new Exception("Impossibile preparare la query per l'aggiornamento dello stato del fondo.");
        }
        $stmt_update->bind_param("si", $new_status, $fund_id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();
        header("location: fund_details.php?id=" . $fund_id . "&message=Il processo di chiusura del fondo è iniziato.&type=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("location: fund_details.php?id=" . $fund_id . "&message=Errore: " . urlencode($e->getMessage()) . "&type=error");
    } finally {
        if(isset($conn)) $conn->close();
    }
} else {
    header("location: shared_funds.php");
    exit;
}
?>