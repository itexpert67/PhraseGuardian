<?php
/**
 * Subscription Processing for Text Paraphrasing and Plagiarism Checking App
 * 
 * This file handles subscription processing and callbacks from PayPal
 */

// Include required files
require_once 'db_connect.php';
require_once 'paypal_integration.php';

/**
 * Process a subscription purchase
 * 
 * @param int $user_id User ID
 * @param int $plan_id Plan ID
 * @return array Result with status and redirect_url
 */
function processSubscriptionPurchase($user_id, $plan_id) {
    // Verify user exists
    $user_check = db_select("SELECT id FROM users WHERE id = ?", [$user_id]);
    if (empty($user_check)) {
        return [
            'status' => 'error',
            'message' => 'User not found'
        ];
    }
    
    // Verify plan exists and is active
    $plan_check = db_select("SELECT id, name, price FROM subscription_plans WHERE id = ? AND is_active = 1", [$plan_id]);
    if (empty($plan_check)) {
        return [
            'status' => 'error',
            'message' => 'Subscription plan not found or inactive'
        ];
    }
    
    $plan = $plan_check[0];
    
    // If the plan is free, process it directly
    if ((float)$plan['price'] <= 0) {
        // Calculate subscription dates
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        // Insert subscription record
        $sub_id = db_insert("INSERT INTO subscriptions 
                            (user_id, plan_id, status, current_period_start, current_period_end) 
                            VALUES (?, ?, 'active', ?, ?)", 
                            [$user_id, $plan_id, $start_date, $end_date]);
        
        // Update user record
        db_update("UPDATE users SET 
                  is_subscribed = 1, 
                  subscription_tier = ?, 
                  subscription_start = ?, 
                  subscription_end = ? 
                  WHERE id = ?", 
                  [$plan['name'], $start_date, $end_date, $user_id]);
        
        return [
            'status' => 'success',
            'message' => 'Free subscription activated',
            'redirect_url' => 'dashboard.php'
        ];
    }
    
    // For paid plans, create a PayPal subscription checkout
    $return_url = 'https://yourwebsite.com/subscription_callback.php';
    $cancel_url = 'https://yourwebsite.com/subscription_cancelled.php';
    
    $checkout_url = generateSubscriptionCheckoutUrl($plan_id, $user_id, $return_url, $cancel_url);
    
    if (!$checkout_url) {
        return [
            'status' => 'error',
            'message' => 'Failed to create subscription checkout'
        ];
    }
    
    return [
        'status' => 'redirect',
        'redirect_url' => $checkout_url
    ];
}

/**
 * Process a completed subscription
 * 
 * This function is called after PayPal redirects back to your site
 * 
 * @param int $user_id User ID
 * @param string $subscription_id PayPal subscription ID from the URL
 * @return array Processing result
 */
function processCompletedSubscription($user_id, $subscription_id) {
    // Validate inputs
    if (!$user_id || !$subscription_id) {
        return [
            'status' => 'error',
            'message' => 'Missing required parameters'
        ];
    }
    
    // Update subscription details from PayPal
    $result = updateSubscriptionFromPayPal($user_id, $subscription_id);
    
    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Failed to process subscription'
        ];
    }
    
    // Return success
    return [
        'status' => 'success',
        'message' => 'Subscription activated successfully'
    ];
}

/**
 * Process the PayPal webhook for subscription events
 * 
 * @param string $event_type Event type from PayPal
 * @param array $event_data Event data from PayPal
 * @return bool Success status
 */
function processSubscriptionWebhook($event_type, $event_data) {
    // Extract subscription ID
    $subscription_id = $event_data['resource']['id'] ?? null;
    
    if (!$subscription_id) {
        return false;
    }
    
    // Look up the subscription in our database
    $sub_query = "SELECT id, user_id FROM subscriptions WHERE paypal_subscription_id = ?";
    $subscriptions = db_select($sub_query, [$subscription_id]);
    
    if (empty($subscriptions)) {
        // This could be a new subscription that hasn't been recorded in our database yet
        if ($event_type === 'BILLING.SUBSCRIPTION.CREATED') {
            // This will be handled when the user returns to our site
            return true;
        }
        
        return false;
    }
    
    $subscription = $subscriptions[0];
    $user_id = $subscription['user_id'];
    
    // Process different event types
    switch ($event_type) {
        case 'BILLING.SUBSCRIPTION.CANCELLED':
            // Update subscription status
            db_update("UPDATE subscriptions SET status = 'canceled', canceled_at = NOW() WHERE id = ?", 
                     [$subscription['id']]);
            
            // Update user subscription status
            db_update("UPDATE users SET is_subscribed = 0 WHERE id = ?", [$user_id]);
            break;
            
        case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
            // Update subscription status
            db_update("UPDATE subscriptions SET status = 'past_due' WHERE id = ?", [$subscription['id']]);
            break;
            
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            // Update subscription status
            db_update("UPDATE subscriptions SET status = 'suspended' WHERE id = ?", [$subscription['id']]);
            
            // Update user subscription status
            db_update("UPDATE users SET is_subscribed = 0 WHERE id = ?", [$user_id]);
            break;
            
        case 'BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED':
            // Record the payment
            $amount = $event_data['resource']['amount']['value'] ?? 0;
            $currency = $event_data['resource']['amount']['currency_code'] ?? 'USD';
            $transaction_id = $event_data['resource']['id'] ?? '';
            
            db_insert("INSERT INTO payments 
                      (user_id, amount, currency, status, paypal_transaction_id, payment_method, description) 
                      VALUES (?, ?, ?, 'succeeded', ?, 'paypal', 'Subscription payment')", 
                      [$user_id, $amount, $currency, $transaction_id]);
            
            // Update subscription details from PayPal
            updateSubscriptionFromPayPal($user_id, $subscription_id);
            break;
            
        case 'BILLING.SUBSCRIPTION.UPDATED':
            // Update subscription details from PayPal
            updateSubscriptionFromPayPal($user_id, $subscription_id);
            break;
            
        default:
            // Unknown event type, no action needed
            break;
    }
    
    return true;
}

/**
 * Check if user has an active subscription
 * 
 * @param int $user_id User ID
 * @return bool True if user has an active subscription
 */
function hasActiveSubscription($user_id) {
    $user = db_select("SELECT is_subscribed, subscription_end FROM users WHERE id = ?", [$user_id]);
    
    if (empty($user) || !$user[0]['is_subscribed']) {
        return false;
    }
    
    // Check if subscription is expired
    if ($user[0]['subscription_end'] && strtotime($user[0]['subscription_end']) < time()) {
        // Subscription has expired, update user record
        db_update("UPDATE users SET is_subscribed = 0 WHERE id = ?", [$user_id]);
        return false;
    }
    
    return true;
}

/**
 * Cancel a user's subscription
 * 
 * @param int $user_id User ID
 * @return array Result with status and message
 */
function cancelUserSubscription($user_id) {
    // Get user's active subscription
    $sub_query = "SELECT id, paypal_subscription_id FROM subscriptions 
                 WHERE user_id = ? AND status = 'active'";
    $subscriptions = db_select($sub_query, [$user_id]);
    
    if (empty($subscriptions)) {
        return [
            'status' => 'error',
            'message' => 'No active subscription found'
        ];
    }
    
    $subscription = $subscriptions[0];
    
    // If there's a PayPal subscription ID, cancel it with PayPal
    if (!empty($subscription['paypal_subscription_id'])) {
        $result = cancelPayPalSubscription($subscription['paypal_subscription_id']);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Failed to cancel subscription with PayPal'
            ];
        }
    }
    
    // Update subscription status in database
    db_update("UPDATE subscriptions SET 
              status = 'canceled', 
              cancel_at_period_end = 1, 
              canceled_at = NOW() 
              WHERE id = ?", 
              [$subscription['id']]);
    
    // Don't immediately cancel user's benefits - they'll continue until the end of the billing period
    // This will be handled by the subscription_end date check in hasActiveSubscription()
    
    return [
        'status' => 'success',
        'message' => 'Subscription canceled successfully. You will have access until the end of the current billing period.'
    ];
}