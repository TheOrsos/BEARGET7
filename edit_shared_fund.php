<?php
// File: edit_shared_fund.php (NUOVO FILE)
session_start();
require_once 'db_connect.php';
require_once 'functions.php';

$user_id = $_SESSION["id"];
$fund_id = $_GET['id'] ?? 0;

// Recupera i dettagli del fondo, ma solo se l'utente attuale è il creatore
$fund = get_shared_fund_details_for_creator($conn, $fund_id, $user_id);
if (!$fund) {
    header("location: shared_funds.php?message=Fondo non trovato o non sei il creatore.&type=error");
    exit;
}

// Logica per l'aggiornamento
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $target_amount = trim($_POST['target_amount']);

    if (empty($name) || !is_numeric($target_amount) || $target_amount <= 0) {
        header("location: edit_shared_fund.php?id={$fund_id}&message=Dati non validi.&type=error");
        exit();
    }

    $sql = "UPDATE shared_funds SET name = ?, target_amount = ? WHERE id = ? AND creator_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdii", $name, $target_amount, $fund_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
    header("location: fund_details.php?id={$fund_id}");
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Modifica Fondo Comune</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="theme.php">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #111827; }</style>
</head>
<body class="text-gray-200 p-10">
    <div class="max-w-lg mx-auto bg-gray-800 rounded-2xl p-8">
        <h1 class="text-2xl font-bold text-white mb-6">Modifica Fondo: <?php echo htmlspecialchars($fund['name']); ?></h1>
        <form action="edit_shared_fund.php?id=<?php echo $fund_id; ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Nome del Fondo</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($fund['name']); ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Obiettivo di Raccolta (€)</label>
                <input type="number" step="0.01" name="target_amount" value="<?php echo $fund['target_amount']; ?>" required class="w-full bg-gray-700 text-white rounded-lg px-3 py-2">
            </div>
            <div class="pt-4 flex justify-end space-x-4">
                <a href="fund_details.php?id=<?php echo $fund_id; ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-5 rounded-lg">Annulla</a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-5 rounded-lg">Salva Modifiche</button>
            </div>
        </form>
    </div>
</body>
</html>