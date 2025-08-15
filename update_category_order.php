<?php
/*
================================================================================
File: update_category_order.php
Descrizione: Riceve il nuovo ordine delle categorie e lo salva nel database.
================================================================================
*/
session_start();
require_once 'db_connect.php';

// Sicurezza e recupero dati
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(["error" => "Accesso non autorizzato."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ordered_ids = $data['order'] ?? [];
$user_id = $_SESSION['id'];

if (empty($ordered_ids) || !is_array($ordered_ids)) {
    http_response_code(400);
    echo json_encode(["error" => "Dati non validi."]);
    exit;
}

// Aggiorna l'ordine nel database
$sql = "UPDATE categories SET category_order = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

foreach ($ordered_ids as $index => $category_id) {
    $order = $index + 1; // L'ordine parte da 1
    $stmt->bind_param("iii", $order, $category_id, $user_id);
    $stmt->execute();
}

$stmt->close();
$conn->close();

// Invia una risposta di successo
header('Content-Type: application/json');
echo json_encode(["success" => true]);
?>
