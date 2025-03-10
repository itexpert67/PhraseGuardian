<?php
/**
 * Process PayPal Payment
 * 
 * This script handles the PayPal payment process callback
 * and creates subscription records in the database
 */

// Include database connection
require_once 'db_connect.php';

// Start session for user authentication
session_start();

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Check if required parameters are provided
if (!isset($_GET['plan_id']) || !isset($_GET['transaction_id']) || !isset($_GET['status'])) {
    header('Location: my_subscription.php?error=missing_parameters');
    exit();
}

$planId = (int)$_GET['plan_id'];
$transactionId = $_GET['transaction_id'];
$status = $_GET['status'];

// Verify the transaction with PayPal (in a real implementation, you would make a call to PayPal's API)
// This is a simplified version for demo purposes
$isVerified = ($status === 'COMPLETED');

if (!$isVerified) {
    header('Location: my_subscription.php?error=payment_failed');
    exit();
}

// Get plan details
$plan = db_select("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1", [$planId])[0] ?? null;
if (!$plan) {
    header('Location: my_subscription.php?error=invalid_plan');
    exit();
}

// Get user details
$user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
if (!$user) {
    header('Location: login.php');
    exit();
}

// Begin transaction
$pdo = db_connect();
$pdo->beginTransaction();

try {
    // Calculate subscription period
    $startDate = date('Y-m-d H:i:s');
    $endDate = null;
    
    switch ($plan['interval']) {
        case 'monthly':
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
            break;
        case 'quarterly':
            $endDate = date('Y-m-d H:i:s', strtotime('+3 months'));
            break;
        case 'yearly':
            $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));
            break;
        default:
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
    }
    
    // Update existing active subscription to be canceled
    $existingSubscription = db_select(
        "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'", 
        [$userId]
    )[0] ?? null;
    
    if ($existingSubscription) {
        db_update('subscriptions', 
            [
                'status' => 'canceled',
                'canceled_at' => date('Y-m-d H:i:s')
            ],
            'id = ?', 
            [$existingSubscription['id']]
        );
    }
    
    // Create new subscription
    $subscriptionId = db_insert('subscriptions', [
        'user_id' => $userId,
        'plan_id' => $planId,
        'status' => 'active',
        'current_period_start' => $startDate,
        'current_period_end' => $endDate,
        'cancel_at_period_end' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$subscriptionId) {
        throw new Exception("Failed to create subscription record");
    }
    
    // Create payment record
    $paymentId = db_insert('payments', [
        'user_id' => $userId,
        'subscription_id' => $subscriptionId,
        'amount' => $plan['price'],
        'currency' => 'usd',
        'status' => 'succeeded',
        'payment_method' => 'paypal',
        'stripe_payment_id' => $transactionId, // Using this field for PayPal transaction ID
        'description' => $plan['name'] . ' Plan - ' . ucfirst($plan['interval']) . ' Subscription',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$paymentId) {
        throw new Exception("Failed to create payment record");
    }
    
    // Update user subscription status
    $result = db_update('users', 
        [
            'is_subscribed' => 1,
            'subscription_tier' => $plan['name'],
            'subscription_start' => $startDate,
            'subscription_end' => $endDate
        ],
        'id = ?', 
        [$userId]
    );
    
    if (!$result) {
        throw new Exception("Failed to update user subscription status");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect to subscription page with success message
    header('Location: my_subscription.php?payment=success');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log the error
    error_log("Payment processing failed: " . $e->getMessage());
    
    // Redirect with error message
    header('Location: my_subscription.php?error=processing_failed');
    exit();
}