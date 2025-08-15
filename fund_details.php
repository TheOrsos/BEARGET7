<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$fund_id = $_GET['id'] ?? 0;
$current_page = 'shared_funds';

// Recupera i dettagli del fondo, ma solo se l'utente attuale ne è membro
$fund = get_shared_fund_details($conn, $fund_id, $user_id);
if (!$fund) {
    header("location: shared_funds.php?message=Fondo non trovato o accesso non autorizzato.&type=error");
    exit;
}

$is_creator = ($fund['creator_id'] == $user_id);

$members = get_fund_members($conn, $fund_id);
$accounts = get_user_accounts($conn, $user_id);
$expense_categories = get_user_categories($conn, $user_id, 'expense');

if ($fund['status'] === 'settling' || $fund['status'] === 'settling_auto') {
    $settlement_payments = get_settlement_payments($conn, $fund_id);
    $all_payments_confirmed = true;
    if (empty($settlement_payments)) {
        $all_payments_confirmed = true;
    } else {
        foreach ($settlement_payments as $payment) {
            if ($payment['status'] === 'pending') {
                $all_payments_confirmed = false;
                break;
            }
        }
    }
} else { // active or archived
    $group_expenses = get_group_expenses($conn, $fund_id);
    $balances = get_group_balances($conn, $fund_id);
    $contributions = get_fund_contributions($conn, $fund_id);
    $fundCategory = get_category_by_name_and_type($conn, 'Fondi Comuni', $user_id, 'expense');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettagli Fondo - Bearget</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="theme.php">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: { 500: 'var(--color-primary-500)', 600: 'var(--color-primary-600)', 700: 'var(--color-primary-700)' },
              gray: { 100: 'var(--color-gray-100)', 200: 'var(--color-gray-200)', 300: 'var(--color-gray-300)', 400: 'var(--color-gray-400)', 700: 'var(--color-gray-700)', 800: 'var(--color-gray-800)', 900: 'var(--color-gray-900)' },
              success: 'var(--color-success)', danger: 'var(--color-danger)', warning: 'var(--color-warning)'
            }
          }
        }
      }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: var(--color-gray-900); }
        .modal-backdrop { transition: opacity 0.3s ease-in-out; }
        .modal-content { transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out; }
    </style>
</head>
<body class="text-gray-300">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
            <header class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($fund['name']); ?></h1>
                    <p class="text-gray-400 mt-1">
                        <?php if($fund['status'] === 'active'): echo 'Gestisci le spese, i contributi e i membri del fondo.'; ?>
                        <?php elseif($fund['status'] === 'settling'): echo 'Questo fondo è in fase di chiusura. Conferma i pagamenti per finalizzare.'; ?>
                        <?php elseif($fund['status'] === 'settling_auto'): echo 'Saldaconto automatico in corso. Seleziona i conti per procedere.'; ?>
                        <?php elseif($fund['status'] === 'archived'): echo 'Questo fondo è archiviato e può essere solo consultato.'; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="fund_stats.php?id=<?php echo $fund_id; ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Statistiche
                    </a>
                    <?php if($fund['status'] === 'active' && $is_creator): ?>
                        <button onclick="openModal('settle-up-modal')" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Chiudi Conto
                        </button>
                    <?php elseif($fund['status'] === 'settling' && $is_creator && $all_payments_confirmed): ?>
                        <button onclick="openModal('archive-fund-modal')" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                             <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4H5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 8v11a2 2 0 01-2 2H7a2 2 0 01-2-2V8m5 4v4m4-4v4"></path></svg>
                            Archivia Fondo
                        </button>
                    <?php endif; ?>

                    <?php if($fund['status'] === 'active'): ?>
                        <button onclick="openModal('add-expense-modal')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Spesa
                        </button>
                        <button onclick="openModal('add-contribution-modal')" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Contributo
                        </button>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($fund['status'] === 'settling'): ?>
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Azioni per il Saldaconto</h2>
                    <div class="space-y-3">
                        <?php if (empty($settlement_payments)): ?>
                            <p class="text-gray-500 text-center py-4">Tutti i conti sono saldati o non ci sono azioni da effettuare.</p>
                        <?php else: foreach($settlement_payments as $payment): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-700/50">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">
                                    <?php
                                        if ($payment['from_user_id'] == $payment['to_user_id']) {
                                            echo $payment['status'] === 'paid' ? '💰' : '📥';
                                        } else {
                                            echo $payment['status'] === 'paid' ? '✅' : '⏳';
                                        }
                                    ?>
                                </span>
                                <div>
                                    <p class="text-white">
                                        <?php if ($payment['from_user_id'] == $payment['to_user_id']): ?>
                                            <span class="font-bold"><?php echo htmlspecialchars($payment['to_username']); ?></span> deve prelevare
                                            <span class="font-bold text-green-400">€<?php echo number_format($payment['amount'], 2, ',', '.'); ?></span> dal fondo.
                                        <?php else: ?>
                                            <span class="font-bold"><?php echo htmlspecialchars($payment['from_username']); ?></span> deve pagare
                                            <span class="font-bold text-primary-400">€<?php echo number_format($payment['amount'], 2, ',', '.'); ?></span> a
                                            <span class="font-bold"><?php echo htmlspecialchars($payment['to_username']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php
                                $is_withdrawal = $payment['from_user_id'] == $payment['to_user_id'];
                                $can_confirm = false;
                                if ($is_withdrawal && $payment['to_user_id'] == $user_id) {
                                    $can_confirm = true;
                                } elseif (!$is_withdrawal && ($payment['from_user_id'] == $user_id || $payment['to_user_id'] == $user_id)) {
                                    $can_confirm = true;
                                }
                            ?>
                            <?php if($payment['status'] === 'pending' && $can_confirm): ?>
                            <form action="confirm_payment.php" method="POST" class="confirm-payment-form">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-1 px-3 rounded-lg text-sm">
                                    <?php echo $is_withdrawal ? 'Conferma Prelievo' : 'Conferma Pagamento'; ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            <?php elseif ($fund['status'] === 'settling_auto'):
                $p2p_payments = array_filter($settlement_payments, function($p) {
                    return $p['from_user_id'] != $p['to_user_id'];
                });
                $all_accounts_selected = true;
                foreach($p2p_payments as $p) {
                    if (empty($p['from_account_id']) || empty($p['to_account_id'])) {
                        $all_accounts_selected = false;
                        break;
                    }
                }
            ?>
                <div class="bg-gray-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-white mb-2">Saldaconto Automatico</h2>
                    <p class="text-gray-400 mb-6">Ogni utente deve selezionare il proprio conto per il trasferimento. Una volta che tutti avranno scelto, il creatore potrà finalizzare.</p>

                    <div id="settlement-container" class="space-y-4">
                        <?php if(empty($p2p_payments)): ?>
                            <p class="text-center text-gray-500 py-4">Nessun debito da saldare tra i membri.</p>
                        <?php else:
                            $user_accounts_map = [];
                            foreach ($members as $member) {
                                $user_accounts_map[$member['id']] = get_user_accounts($conn, $member['id']);
                            }
                        ?>
                            <?php foreach($p2p_payments as $payment): ?>
                                <div class="bg-gray-700/50 p-4 rounded-lg">
                                    <p class="text-white text-center mb-3">
                                        <span class="font-bold"><?php echo htmlspecialchars($payment['from_username']); ?></span> deve pagare
                                        <span class="font-bold text-primary-400">€<?php echo number_format($payment['amount'], 2, ',', '.'); ?></span> a
                                        <span class="font-bold"><?php echo htmlspecialchars($payment['to_username']); ?></span>
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Conto di <?php echo htmlspecialchars($payment['from_username']); ?> (Uscita)</label>
                                            <?php if ($user_id == $payment['from_user_id'] && empty($payment['from_account_id'])): ?>
                                                <select data-payment-id="<?php echo $payment['id']; ?>" data-type="from" class="account-select w-full bg-gray-900 text-white rounded-lg px-3 py-2">
                                                    <option value="">Scegli un conto...</option>
                                                    <?php foreach($user_accounts_map[$payment['from_user_id']] as $account): ?>
                                                        <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <p class="text-gray-400 text-sm italic mt-2">
                                                    <?php echo empty($payment['from_account_id']) ? 'In attesa di scelta...' : 'Conto selezionato.'; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Conto di <?php echo htmlspecialchars($payment['to_username']); ?> (Entrata)</label>
                                            <?php if ($user_id == $payment['to_user_id'] && empty($payment['to_account_id'])): ?>
                                                <select data-payment-id="<?php echo $payment['id']; ?>" data-type="to" class="account-select w-full bg-gray-900 text-white rounded-lg px-3 py-2">
                                                    <option value="">Scegli un conto...</option>
                                                    <?php foreach($user_accounts_map[$payment['to_user_id']] as $account): ?>
                                                        <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <p class="text-gray-400 text-sm italic mt-2">
                                                    <?php echo empty($payment['to_account_id']) ? 'In attesa di scelta...' : 'Conto selezionato.'; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_creator && $all_accounts_selected && !empty($p2p_payments)): ?>
                    <div class="mt-6 text-right">
                        <form action="process_automatic_settlement.php" method="POST">
                            <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Processa e Archivia Fondo</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: // active or archived ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Colonna Principale -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Riepilogo</h2>
                            <?php
                                $percentage = ($fund['target_amount'] > 0) ? ($fund['total_contributed'] / $fund['target_amount']) * 100 : 0;
                            ?>
                            <div class="w-full bg-gray-700 rounded-full h-4 mb-2">
                                <div class="bg-green-500 h-4 rounded-full text-center text-white text-xs font-bold" style="width: <?php echo min($percentage, 100); ?>%"><?php echo round($percentage); ?>%</div>
                            </div>
                            <div class="flex justify-between text-lg text-gray-300">
                                <span class="font-bold text-white">€<?php echo number_format($fund['total_contributed'], 2, ',', '.'); ?></span>
                                <span class="text-gray-400">di €<?php echo number_format($fund['target_amount'], 2, ',', '.'); ?></span>
                            </div>
                        </div>

                        <!-- Expense List -->
                         <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Spese del Gruppo</h2>
                            <div class="space-y-2">
                                <?php if(empty($group_expenses)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <p>Nessuna spesa registrata in questo gruppo.</p>
                                    </div>
                                <?php else: foreach($group_expenses as $expense): ?>
                                <div class="flex items-center justify-between p-2 rounded-lg transition-colors hover:bg-gray-700/50">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl"><?php echo htmlspecialchars($expense['category_icon'] ?? '💰'); ?></span>
                                        <div>
                                            <p class="font-semibold text-white"><?php echo htmlspecialchars($expense['description']); ?></p>
                                            <p class="text-sm text-gray-400">
                                                Pagato da <?php echo htmlspecialchars($expense['paid_by_username']); ?> il <?php echo date("d/m/Y", strtotime($expense['expense_date'])); ?>
                                                <?php if($expense['category_name']): ?>
                                                <span class="font-bold"> · </span> <?php echo htmlspecialchars($expense['category_name']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <?php if($expense['note_id']): ?>
                                        <a href="note_details.php?id=<?php echo $expense['note_id']; ?>" class="text-gray-400 hover:text-white" title="Visualizza nota">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </a>
                                        <?php endif; ?>
                                        <p class="font-bold text-danger text-lg">-€<?php echo number_format($expense['amount'], 2, ',', '.'); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>

                        <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Storico Contributi</h2>
                            <div class="space-y-2">
                                <?php if(empty($contributions)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <p>Nessun contributo ancora versato.</p>
                                    </div>
                                <?php else: foreach($contributions as $c): ?>
                                <div class="flex items-center justify-between p-2 rounded-lg transition-colors hover:bg-gray-700/50">
                                    <div>
                                        <p class="font-semibold text-white"><?php echo htmlspecialchars($c['username']); ?></p>
                                        <p class="text-sm text-gray-400"><?php echo date("d/m/Y", strtotime($c['contribution_date'])); ?></p>
                                    </div>
                                    <p class="font-bold text-success">+€<?php echo number_format($c['amount'], 2, ',', '.'); ?></p>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Colonna Laterale -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Membri</h2>
                            <div class="space-y-3">
                                <?php foreach($members as $member): ?>
                                <div class="flex items-center p-2 rounded-lg transition-colors hover:bg-gray-700/50">
                                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-sm mr-3 flex-shrink-0"><?php echo strtoupper(substr($member['username'], 0, 1)); ?></div>
                                    <span><?php echo htmlspecialchars($member['username']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Balances -->
                        <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Bilanci</h2>
                            <?php
                                $users_who_owe = array_filter($balances, function($b) { return $b['balance'] < 0; });
                                $users_who_are_owed = array_filter($balances, function($b) { return $b['balance'] > 0; });
                            ?>
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-md font-semibold text-gray-400 mb-2 border-b border-gray-700 pb-1">Chi deve dare</h3>
                                    <div class="space-y-2 pt-2">
                                    <?php if(empty($users_who_owe)): ?>
                                        <p class="text-sm text-gray-500">Nessuno deve soldi al gruppo.</p>
                                    <?php else: foreach($users_who_owe as $balance): ?>
                                    <div class="flex items-center justify-between p-1">
                                        <span class="text-white"><?php echo htmlspecialchars($balance['username']); ?></span>
                                        <span class="font-bold text-danger">€<?php echo number_format(abs($balance['balance']), 2, ',', '.'); ?></span>
                                    </div>
                                    <?php endforeach; endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-md font-semibold text-gray-400 mb-2 border-b border-gray-700 pb-1">Chi deve ricevere</h3>
                                    <div class="space-y-2 pt-2">
                                    <?php if(empty($users_who_are_owed)): ?>
                                        <p class="text-sm text-gray-500">Nessuno deve ricevere soldi dal gruppo.</p>
                                    <?php else: foreach($users_who_are_owed as $balance): ?>
                                    <div class="flex items-center justify-between p-1">
                                        <span class="text-white"><?php echo htmlspecialchars($balance['username']); ?></span>
                                        <span class="font-bold text-success">+€<?php echo number_format($balance['balance'], 2, ',', '.'); ?></span>
                                    </div>
                                    <?php endforeach; endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-800 rounded-2xl p-6">
                            <h2 class="text-xl font-bold text-white mb-4">Invita Membro</h2>
                            <form action="invite_member.php" method="POST" class="space-y-3">
                                <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">
                                <div>
                                    <label for="friend_code" class="block text-sm font-medium text-gray-400 mb-1">Codice Amico</label>
                                    <input type="text" name="friend_code" id="friend_code" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="ABC123DE">
                                </div>
                                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Invita
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modale Aggiungi Spesa -->
    <div id="add-expense-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-expense-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-lg p-6 transform scale-95 opacity-0 modal-content overflow-y-auto max-h-full">
            <h2 class="text-2xl font-bold text-white mb-6">Aggiungi Nuova Spesa</h2>
            <form id="add-expense-form" action="add_expense.php" method="POST" class="space-y-4">
                <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Descrizione</label>
                    <input type="text" name="description" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" placeholder="Es. Cena fuori">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Importo Totale (€)</label>
                        <input type="number" step="0.01" name="amount" id="expense-amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data Spesa</label>
                        <input type="date" name="expense_date" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Pagato da</label>
                    <select name="paid_by_user_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <?php foreach($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>" <?php echo ($member['id'] == $user_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Conto Personale</label>
                    <select name="account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <?php foreach($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-white mb-2 mt-4">Divisione Spesa</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Metodo di divisione</label>
                        <select name="split_method" id="split-method-select" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                            <option value="equal">Parti Uguali</option>
                            <option value="fixed">Importo Fisso</option>
                            <option value="percentage">Percentuale</option>
                        </select>
                    </div>
                </div>

                <div id="split-container">
                    <!-- Container for dynamic split inputs -->
                </div>
                <div id="split-feedback" class="text-sm text-red-400 h-4"></div>


                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-expense-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" id="add-expense-submit-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg">Aggiungi Spesa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Aggiungi Contributo -->
    <div id="add-contribution-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 opacity-0 modal-backdrop" onclick="closeModal('add-contribution-modal')"></div>
        <div class="bg-gray-800 rounded-2xl w-full max-w-md p-6 transform scale-95 opacity-0 modal-content">
            <h2 class="text-2xl font-bold text-white mb-6">Versa nel Fondo</h2>
            <form action="add_fund_contribution.php" method="POST" class="space-y-4">
                <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">
                <input type="hidden" name="category_id" value="<?php echo $fundCategory['id'] ?? ''; ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Importo (€)</label>
                    <input type="number" step="0.01" name="amount" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Dal tuo conto</label>
                    <select name="account_id" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
                        <?php foreach($accounts as $account): ?><option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php if (!isset($fundCategory['id'])): ?>
                    <p class="text-sm text-yellow-400">Attenzione: Categoria 'Fondi Comuni' non trovata. Il contributo non creerà una transazione di spesa personale.</p>
                <?php endif; ?>
                <div class="pt-4 flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('add-contribution-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Conferma</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modals -->
    <?php if($fund['status'] === 'active' && $is_creator): ?>
        <!-- Settle Up Modal -->
        <div id="settle-up-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
             <div class="fixed inset-0 bg-black bg-opacity-60" onclick="closeModal('settle-up-modal')"></div>
             <div class="bg-gray-800 rounded-lg p-6 z-10 max-w-md text-center shadow-lg">
                <h2 class="text-xl font-bold mb-4 text-white">Chiudere il Conto?</h2>
                <p class="text-gray-400">Questa azione calcolerà i pagamenti finali e metterà il fondo in modalità "chiusura". Non potrai più aggiungere nuove spese o contributi. Sei sicuro?</p>
                <form action="calculate_settlement.php" method="POST">
                    <div class="mt-4 text-left">
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="auto_settle" value="1" class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2">Salda automaticamente i debiti creando le transazioni</span>
                        </label>
                    </div>
                    <div class="mt-6 flex justify-center gap-4">
                        <button type="button" onclick="closeModal('settle-up-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                        <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-5 rounded-lg">Sì, chiudi conto</button>
                    </div>
                </form>
             </div>
        </div>
    <?php endif; ?>
    <?php if($fund['status'] === 'settling' && $is_creator && $all_payments_confirmed): ?>
        <!-- Archive Fund Modal -->
        <div id="archive-fund-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
            <div class="fixed inset-0 bg-black bg-opacity-60" onclick="closeModal('archive-fund-modal')"></div>
             <div class="bg-gray-800 rounded-lg p-6 z-10 max-w-md text-center shadow-lg">
                <h2 class="text-xl font-bold mb-4 text-white">Archiviare il Fondo?</h2>
                <p class="text-gray-400">Tutti i pagamenti sono stati confermati. Archiviando il fondo lo renderai non modificabile e di sola lettura. Sei sicuro?</p>
                <div class="mt-6 flex justify-center gap-4">
                    <button type="button" onclick="closeModal('archive-fund-modal')" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</button>
                    <form action="archive_fund.php" method="POST">
                        <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-lg">Sì, archivia</button>
                    </form>
                </div>
             </div>
        </div>
    <?php endif; ?>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                if(backdrop) backdrop.classList.remove('opacity-0');
                if(content) content.classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            if(backdrop) backdrop.classList.add('opacity-0');
            if(content) content.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const members = <?php echo json_encode($members); ?>;
            const splitMethodSelect = document.getElementById('split-method-select');
            const splitContainer = document.getElementById('split-container');
            const expenseAmountInput = document.getElementById('expense-amount');
            const feedbackDiv = document.getElementById('split-feedback');
            const submitBtn = document.getElementById('add-expense-submit-btn');

            function renderSplitInputs() {
                const method = splitMethodSelect.value;
                let html = '';

                switch(method) {
                    case 'equal':
                        html = `<div class="grid grid-cols-2 md:grid-cols-3 gap-2">`;
                        members.forEach(member => {
                            html += `<label class="flex items-center bg-gray-700 p-2 rounded-lg">
                                <input type="checkbox" name="split_with_users[]" value="${member.id}" checked class="form-checkbox h-5 w-5 bg-gray-900 border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-white">${escapeHTML(member.username)}</span>
                            </label>`;
                        });
                        html += `</div>`;
                        break;

                    case 'fixed':
                    case 'percentage':
                        const unit = method === 'fixed' ? '€' : '%';
                        html = `<div class="space-y-2">`;
                        members.forEach(member => {
                            html += `<div class="flex items-center justify-between">
                                <label class="text-white">${escapeHTML(member.username)}</label>
                                <div class="flex items-center w-1/2">
                                    <input type="number" step="0.01" name="${method}[${member.id}]" class="split-input w-full bg-gray-900 text-white rounded-lg px-3 py-1" placeholder="0.00" data-method="${method}">
                                    <span class="ml-2 text-gray-400">${unit}</span>
                                </div>
                            </div>`;
                        });
                        html += `</div>`;
                        break;
                }
                splitContainer.innerHTML = html;
            }

            function validateSplits() {
                const method = splitMethodSelect.value;
                const totalAmount = parseFloat(expenseAmountInput.value) || 0;
                let currentTotal = 0;

                document.querySelectorAll('.split-input').forEach(input => {
                    currentTotal += parseFloat(input.value) || 0;
                });

                feedbackDiv.textContent = '';
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

                if (totalAmount <= 0) return;

                if (method === 'fixed') {
                    feedbackDiv.textContent = `Totale: €${currentTotal.toFixed(2)} / €${totalAmount.toFixed(2)}`;
                    if (Math.abs(currentTotal - totalAmount) > 0.01) {
                        feedbackDiv.classList.add('text-red-400');
                        feedbackDiv.classList.remove('text-green-400');
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        feedbackDiv.classList.remove('text-red-400');
                        feedbackDiv.classList.add('text-green-400');
                    }
                } else if (method === 'percentage') {
                    feedbackDiv.textContent = `Totale: ${currentTotal.toFixed(2)}% / 100%`;
                    if (Math.abs(currentTotal - 100) > 0.1) {
                        feedbackDiv.classList.add('text-red-400');
                        feedbackDiv.classList.remove('text-green-400');
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        feedbackDiv.classList.remove('text-red-400');
                        feedbackDiv.classList.add('text-green-400');
                    }
                }
            }

            splitMethodSelect.addEventListener('change', renderSplitInputs);
            expenseAmountInput.addEventListener('input', validateSplits);
            splitContainer.addEventListener('input', validateSplits);

            // Initial render
            renderSplitInputs();
        });

        function escapeHTML(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // AJAX for saving account choice
        document.getElementById('settlement-container')?.addEventListener('change', function(e) {
            if (e.target.classList.contains('account-select')) {
                const select = e.target;
                const paymentId = select.dataset.paymentId;
                const accountId = select.value;
                const type = select.dataset.type;

                const formData = new FormData();
                formData.append('payment_id', paymentId);
                formData.append('account_id', accountId);
                formData.append('type', type);

                fetch('save_account_choice.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Visually confirm selection
                        select.disabled = true;
                        const parent = select.parentElement;
                        parent.innerHTML = `<p class="text-green-400 text-sm italic mt-2">Conto selezionato.</p>`;
                        // Optionally, check if all accounts are now selected and show the final button
                    } else {
                        alert('Errore: ' + data.message);
                    }
                })
                .catch(err => alert('Errore di rete.'));
            }
        });
    </script>
</body>
</html>