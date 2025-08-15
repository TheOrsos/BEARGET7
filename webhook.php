<?php
/*
================================================================================
File: webhook.php
Descrizione: Gestisce gli eventi di Stripe per attivare/disattivare gli
             abbonamenti e salvare le date di inizio e rinnovo.
================================================================================
*/

require_once 'db_connect.php';
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'stripe_webhook_errors.log');

$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch(\UnexpectedValueException $e) {
    http_response_code(400); exit();
} catch(SignatureVerificationException $e) {
    http_response_code(400); exit();
}

// ===================================================================
// === FUNZIONE HELPER CORRETTA ======================================
// ===================================================================
// Questa funzione converte il formato data di Stripe (timestamp)
// in un formato che il nostro database MySQL può capire (DATETIME).
function convertStripeTimestamp($timestamp) {
    return date("Y-m-d H:i:s", $timestamp);
}
// ===================================================================

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $user_id = $session->client_reference_id;
        $stripe_customer_id = $session->customer;
        $stripe_subscription_id = $session->subscription;

        // Recupera l'oggetto completo dell'abbonamento per ottenere le date
        $subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);
        $start_date = convertStripeTimestamp($subscription->start_date);
        $end_date = convertStripeTimestamp($subscription->current_period_end);

        // Aggiorna il database con tutti i dati
        $sql = "UPDATE users SET 
                    subscription_status = 'active', 
                    stripe_customer_id = ?, 
                    stripe_subscription_id = ?,
                    subscription_start_date = ?,
                    subscription_end_date = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $stripe_customer_id, $stripe_subscription_id, $start_date, $end_date, $user_id);
        $stmt->execute();
        $stmt->close();
        break;

    case 'invoice.paid':
        // Questo evento scatta ad ogni rinnovo mensile/annuale
        $invoice = $event->data->object;
        if ($invoice->billing_reason == 'subscription_cycle') {
            $stripe_subscription_id = $invoice->subscription;
            
            // Recupera l'abbonamento per ottenere la nuova data di fine periodo
            $subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);
            $new_end_date = convertStripeTimestamp($subscription->current_period_end);

            // Aggiorna lo stato (se era 'past_due') e la data di rinnovo
            $sql = "UPDATE users SET subscription_status = 'active', subscription_end_date = ? WHERE stripe_subscription_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $new_end_date, $stripe_subscription_id);
            $stmt->execute();
            $stmt->close();
        }
        break;

    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        $stripe_subscription_id = $subscription->id;

        $sql = "UPDATE users SET subscription_status = 'canceled' WHERE stripe_subscription_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $stripe_subscription_id);
        $stmt->execute();
        $stmt->close();
        break;

    // Evento: un pagamento ricorrente è fallito
    case 'invoice.payment_failed':
        $invoice = $event->data->object;
        $stripe_subscription_id = $invoice->subscription;

        $sql = "UPDATE users SET subscription_status = 'past_due' WHERE stripe_subscription_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $stripe_subscription_id);
        $stmt->execute();
        $stmt->close();
        break;
        
    default:
        // Evento non gestito
        error_log('Received unhandled event type ' . $event->type);
}

// Rispondi a Stripe con un codice 200 per confermare che hai ricevuto la notifica
http_response_code(200);
?>
