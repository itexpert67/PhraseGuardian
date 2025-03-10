<?php
/**
 * PayPal Webhook Handler
 * 
 * This file processes webhook events from PayPal for subscription updates
 * Configure your PayPal webhook to point to this file
 */

// Include required files
require_once 'db_connect.php';
require_once 'subscription_process.php';

// Verify webhook is from PayPal (simplified version)
// In production, you should validate the webhook signature: https://developer.paypal.com/api/rest/webhooks/

// Get JSON payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400); // Bad Request
    exit('Invalid payload');
}

// Log the webhook for debugging
$log_file = __DIR__ . '/paypal_webhook_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $payload . PHP_EOL, FILE_APPEND);

// Process webhook
$event_type = $data['event_type'] ?? '';

if (empty($event_type)) {
    http_response_code(400);
    exit('Missing event type');
}

// Process subscription-related events
if (strpos($event_type, 'BILLING.SUBSCRIPTION') === 0) {
    $result = processSubscriptionWebhook($event_type, $data);
    
    if ($result) {
        http_response_code(200);
        exit('Webhook processed successfully');
    } else {
        http_response_code(422); // Unprocessable Entity
        exit('Failed to process webhook');
    }
}

// Return 200 for events we don't handle to acknowledge receipt
http_response_code(200);
exit('Event type not processed');