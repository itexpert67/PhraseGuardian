<?php
/**
 * PayPal Subscription Callback Handler
 * 
 * This file processes the callback from PayPal after a user completes a subscription purchase
 */

// Include required files
require_once 'db_connect.php';
require_once 'subscription_process.php';

// Start or resume session
session_start();

// Get user ID from query parameter or session
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    // Redirect to login page if user ID is not available
    header('Location: login.php?error=session_expired');
    exit;
}

// Get subscription ID from query parameter
$subscription_id = $_GET['subscription_id'] ?? '';

if (empty($subscription_id)) {
    // Redirect with error if subscription ID is missing
    header('Location: dashboard.php?error=missing_subscription');
    exit;
}

// Process the completed subscription
$result = processCompletedSubscription($user_id, $subscription_id);

// Redirect based on result
if ($result['status'] === 'success') {
    header('Location: dashboard.php?subscription=success');
} else {
    header('Location: dashboard.php?subscription=error&message=' . urlencode($result['message']));
}
exit;