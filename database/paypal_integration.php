<?php
/**
 * PayPal Integration for Text Paraphrasing and Plagiarism Checking App
 * 
 * This file contains functions for integrating with PayPal for subscription payments
 */

// Include database connection
require_once 'db_connect.php';

// PayPal API configuration
$paypal_config = [
    // Set to true for development/testing, false for production
    'sandbox' => true,
    
    // PayPal API credentials
    'client_id' => 'YOUR_PAYPAL_CLIENT_ID',  // Replace with your PayPal client ID
    'secret' => 'YOUR_PAYPAL_SECRET',        // Replace with your PayPal secret
    
    // API URLs
    'api_url' => 'https://api-m.sandbox.paypal.com', // Use https://api-m.paypal.com for production
];

/**
 * Get PayPal access token
 * 
 * @return string Access token for PayPal API
 */
function getPayPalAccessToken() {
    global $paypal_config;
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $paypal_config['api_url'] . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, $paypal_config['client_id'] . ':' . $paypal_config['secret']);
    
    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Accept-Language: en_US';
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
    
    curl_close($ch);
    $response = json_decode($result, true);
    
    return $response['access_token'] ?? null;
}

/**
 * Create a subscription plan in PayPal
 * 
 * @param array $plan_data Plan data
 * @return array|null Response from PayPal or null on error
 */
function createPayPalPlan($plan_data) {
    global $paypal_config;
    
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return null;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $paypal_config['api_url'] . '/v1/billing/plans');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($plan_data));
    
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer ' . $access_token;
    $headers[] = 'Prefer: return=representation';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($result, true);
}

/**
 * Create a subscription for a user
 * 
 * @param array $subscription_data Subscription data
 * @return array|null Response from PayPal or null on error
 */
function createPayPalSubscription($subscription_data) {
    global $paypal_config;
    
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return null;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $paypal_config['api_url'] . '/v1/billing/subscriptions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscription_data));
    
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer ' . $access_token;
    $headers[] = 'Prefer: return=representation';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($result, true);
}

/**
 * Cancel a subscription
 * 
 * @param string $subscription_id PayPal subscription ID
 * @param string $reason Reason for cancellation
 * @return array|null Response from PayPal or null on error
 */
function cancelPayPalSubscription($subscription_id, $reason = 'User requested cancellation') {
    global $paypal_config;
    
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return null;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $paypal_config['api_url'] . '/v1/billing/subscriptions/' . $subscription_id . '/cancel');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['reason' => $reason]));
    
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer ' . $access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($result, true);
}

/**
 * Get subscription details
 * 
 * @param string $subscription_id PayPal subscription ID
 * @return array|null Response from PayPal or null on error
 */
function getPayPalSubscription($subscription_id) {
    global $paypal_config;
    
    $access_token = getPayPalAccessToken();
    if (!$access_token) {
        return null;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $paypal_config['api_url'] . '/v1/billing/subscriptions/' . $subscription_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer ' . $access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($result, true);
}

/**
 * Update subscription details in database from PayPal
 * 
 * @param int $user_id User ID
 * @param string $paypal_subscription_id PayPal subscription ID
 * @return bool Success status
 */
function updateSubscriptionFromPayPal($user_id, $paypal_subscription_id) {
    $subscription_data = getPayPalSubscription($paypal_subscription_id);
    
    if (!$subscription_data) {
        return false;
    }
    
    // Get plan info from database
    $plan_sql = "SELECT id FROM subscription_plans WHERE paypal_plan_id = ?";
    $plans = db_select($plan_sql, [$subscription_data['plan_id']]);
    
    if (empty($plans)) {
        return false;
    }
    
    $plan_id = $plans[0]['id'];
    $status = $subscription_data['status'];
    $current_period_start = date('Y-m-d H:i:s', strtotime($subscription_data['start_time']));
    $current_period_end = date('Y-m-d H:i:s', strtotime($subscription_data['billing_info']['next_billing_time']));
    
    // Check if subscription exists
    $check_sql = "SELECT id FROM subscriptions WHERE user_id = ? AND paypal_subscription_id = ?";
    $existing_subscriptions = db_select($check_sql, [$user_id, $paypal_subscription_id]);
    
    if (empty($existing_subscriptions)) {
        // Insert new subscription
        $insert_sql = "INSERT INTO subscriptions 
                      (user_id, plan_id, status, paypal_subscription_id, current_period_start, current_period_end) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        
        db_insert($insert_sql, [
            $user_id,
            $plan_id,
            $status,
            $paypal_subscription_id,
            $current_period_start,
            $current_period_end
        ]);
    } else {
        // Update existing subscription
        $subscription_id = $existing_subscriptions[0]['id'];
        
        $update_sql = "UPDATE subscriptions 
                      SET plan_id = ?, status = ?, current_period_start = ?, current_period_end = ? 
                      WHERE id = ?";
        
        db_update($update_sql, [
            $plan_id,
            $status,
            $current_period_start,
            $current_period_end,
            $subscription_id
        ]);
    }
    
    // Update user subscription status
    $subscription_tier = db_select("SELECT name FROM subscription_plans WHERE id = ?", [$plan_id])[0]['name'] ?? 'free';
    
    db_update("UPDATE users SET 
              is_subscribed = ?, 
              subscription_tier = ?,
              subscription_start = ?,
              subscription_end = ? 
              WHERE id = ?", 
              [
                  $status === 'ACTIVE' ? 1 : 0,
                  $subscription_tier,
                  $current_period_start,
                  $current_period_end,
                  $user_id
              ]);
    
    return true;
}

/**
 * Generate a PayPal subscription checkout URL
 * 
 * @param int $plan_id Plan ID from database
 * @param int $user_id User ID
 * @param string $return_url URL to return to after successful payment
 * @param string $cancel_url URL to return to if payment is cancelled
 * @return string|null Checkout URL or null on error
 */
function generateSubscriptionCheckoutUrl($plan_id, $user_id, $return_url, $cancel_url) {
    // Get plan details
    $plan_sql = "SELECT paypal_plan_id FROM subscription_plans WHERE id = ?";
    $plans = db_select($plan_sql, [$plan_id]);
    
    if (empty($plans) || !$plans[0]['paypal_plan_id']) {
        return null;
    }
    
    $paypal_plan_id = $plans[0]['paypal_plan_id'];
    
    // Create subscription data
    $subscription_data = [
        'plan_id' => $paypal_plan_id,
        'application_context' => [
            'brand_name' => 'Text Processor App',
            'locale' => 'en-US',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'SUBSCRIBE_NOW',
            'return_url' => $return_url . '?user_id=' . $user_id,
            'cancel_url' => $cancel_url
        ]
    ];
    
    $response = createPayPalSubscription($subscription_data);
    
    if (!$response || !isset($response['links'])) {
        return null;
    }
    
    // Find the approval URL
    foreach ($response['links'] as $link) {
        if ($link['rel'] === 'approve') {
            return $link['href'];
        }
    }
    
    return null;
}