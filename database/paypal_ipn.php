<?php
/**
 * PayPal IPN Handler
 * 
 * This script handles PayPal Instant Payment Notifications (IPNs) for subscription events.
 * It should be set as the IPN listener URL in your PayPal account.
 */

// Include database connection
require_once 'db_connect.php';

// Define the log file
$log_file = __DIR__ . '/paypal_ipn.log';

/**
 * Log a message to the IPN log file
 *
 * @param string $message The message to log
 */
function ipn_log($message) {
    global $log_file;
    
    // Format the timestamp
    $timestamp = date('[Y-m-d H:i:s] ');
    
    // Append message to log file
    file_put_contents($log_file, $timestamp . $message . "\n", FILE_APPEND);
}

/**
 * Verify the PayPal IPN
 *
 * @param array $post_data The IPN data received from PayPal
 * @return bool True if the IPN is verified, false otherwise
 */
function verify_ipn($post_data) {
    // Set up the verification data
    $req = 'cmd=_notify-validate';
    
    // Add all received POST variables to the verification data
    foreach ($post_data as $key => $value) {
        $value = urlencode($value);
        $req .= "&$key=$value";
    }
    
    // Set up the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://ipnpb.paypal.com/cgi-bin/webscr'); // Use sandbox URL for testing
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Set user agent
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Connection: Close',
        'User-Agent: PHP-IPN-Verification-Script'
    ));
    
    // Execute request and get response
    $response = curl_exec($ch);
    
    // Check for errors
    if (!$response) {
        ipn_log('Curl error: ' . curl_error($ch) . ' (' . curl_errno($ch) . ')');
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Check the response
    if (strcmp($response, "VERIFIED") == 0) {
        ipn_log('IPN Verified: ' . print_r($post_data, true));
        return true;
    } else {
        ipn_log('IPN Invalid: ' . print_r($post_data, true));
        return false;
    }
}

/**
 * Process a payment IPN
 *
 * @param array $data The IPN data
 */
function process_payment_ipn($data) {
    try {
        // Get subscription ID from IPN
        $paypal_subscription_id = $data['recurring_payment_id'] ?? $data['subscr_id'] ?? null;
        
        if (!$paypal_subscription_id) {
            ipn_log('No subscription ID found in IPN.');
            return;
        }
        
        // Get the subscription from the database
        $subscription = db_select(
            "SELECT s.*, u.id as user_id, p.name as plan_name, p.price, p.interval 
             FROM subscriptions s 
             JOIN users u ON s.user_id = u.id 
             JOIN subscription_plans p ON s.plan_id = p.id 
             WHERE s.paypal_subscription_id = ?", 
            [$paypal_subscription_id]
        );
        
        if (empty($subscription)) {
            ipn_log('Subscription not found in database: ' . $paypal_subscription_id);
            return;
        }
        
        $subscription = $subscription[0];
        $userId = $subscription['user_id'];
        
        // Process the IPN based on the transaction type
        $txn_type = $data['txn_type'] ?? '';
        
        switch ($txn_type) {
            case 'recurring_payment':
                // Recurring payment received
                if ($data['payment_status'] === 'Completed') {
                    // Update subscription period
                    $startDate = date('Y-m-d H:i:s');
                    $endDate = null;
                    
                    if ($subscription['interval'] === 'monthly') {
                        $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
                    } elseif ($subscription['interval'] === 'yearly') {
                        $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                    } else {
                        $endDate = date('Y-m-d H:i:s', strtotime('+1 month')); // Default to monthly
                    }
                    
                    // Update subscription
                    db_update(
                        'subscriptions',
                        [
                            'current_period_start' => $startDate,
                            'current_period_end' => $endDate,
                            'status' => 'active',
                            'cancel_at_period_end' => 0,
                            'canceled_at' => null
                        ],
                        'id = ?',
                        [$subscription['id']]
                    );
                    
                    // Update user subscription status
                    db_update(
                        'users',
                        [
                            'is_subscribed' => 1,
                            'subscription_tier' => $subscription['plan_name'],
                            'subscription_start' => $startDate,
                            'subscription_end' => $endDate
                        ],
                        'id = ?',
                        [$userId]
                    );
                    
                    // Create payment record
                    $paymentData = [
                        'user_id' => $userId,
                        'subscription_id' => $subscription['id'],
                        'amount' => $data['amount'] ?? $subscription['price'],
                        'currency' => $data['mc_currency'] ?? 'USD',
                        'payment_method' => 'paypal',
                        'status' => 'succeeded',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    db_insert('payments', $paymentData);
                    
                    ipn_log('Processed recurring payment for subscription: ' . $paypal_subscription_id);
                }
                break;
                
            case 'recurring_payment_profile_cancel':
                // Subscription canceled
                db_update(
                    'subscriptions',
                    [
                        'status' => 'canceled',
                        'cancel_at_period_end' => 1,
                        'canceled_at' => date('Y-m-d H:i:s')
                    ],
                    'id = ?',
                    [$subscription['id']]
                );
                
                ipn_log('Processed subscription cancellation for: ' . $paypal_subscription_id);
                break;
                
            case 'recurring_payment_failed':
                // Payment failed
                ipn_log('Payment failed for subscription: ' . $paypal_subscription_id);
                break;
                
            case 'recurring_payment_expired':
                // Subscription expired
                db_update(
                    'subscriptions',
                    [
                        'status' => 'expired',
                        'canceled_at' => date('Y-m-d H:i:s')
                    ],
                    'id = ?',
                    [$subscription['id']]
                );
                
                // Update user to free tier
                db_update(
                    'users',
                    [
                        'is_subscribed' => 0,
                        'subscription_tier' => 'Basic'
                    ],
                    'id = ?',
                    [$userId]
                );
                
                ipn_log('Processed subscription expiration for: ' . $paypal_subscription_id);
                break;
                
            default:
                ipn_log('Unhandled transaction type: ' . $txn_type);
                break;
        }
    } catch (Exception $e) {
        ipn_log('Error processing IPN: ' . $e->getMessage());
    }
}

// Main execution
try {
    // Get POST data from PayPal
    $post_data = $_POST;
    
    if (empty($post_data)) {
        ipn_log('No POST data received.');
        exit;
    }
    
    // Log the received IPN
    ipn_log('IPN Received: ' . print_r($post_data, true));
    
    // Verify the IPN with PayPal
    if (verify_ipn($post_data)) {
        // Process the verified IPN
        process_payment_ipn($post_data);
    }
} catch (Exception $e) {
    ipn_log('Exception: ' . $e->getMessage());
}

// Return a 200 status to PayPal
http_response_code(200);