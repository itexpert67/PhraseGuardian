<?php
/**
 * Process Payment Handler
 * 
 * This script processes PayPal subscription payments and updates the user's subscription status.
 */

// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error = '';
$success = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get subscription data from PayPal
$subscriptionId = $_GET['subscription_id'] ?? '';
$planId = intval($_GET['plan_id'] ?? 0);

if (empty($subscriptionId) || $planId === 0) {
    $error = 'Invalid subscription data.';
} else {
    try {
        // Get user data
        $user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
        
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Get plan data
        $plan = db_select("SELECT * FROM subscription_plans WHERE id = ?", [$planId])[0] ?? null;
        
        if (!$plan) {
            throw new Exception('Subscription plan not found.');
        }
        
        // In a real production environment, you would verify the subscription with PayPal API
        // For this example, we'll assume the subscription is valid
        
        // Calculate subscription dates
        $startDate = date('Y-m-d H:i:s');
        $endDate = null;
        
        if ($plan['interval'] === 'monthly') {
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        } elseif ($plan['interval'] === 'yearly') {
            $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));
        } else {
            $endDate = date('Y-m-d H:i:s', strtotime('+1 month')); // Default to monthly
        }
        
        // Update any existing subscriptions to inactive
        db_update(
            'subscriptions',
            ['status' => 'canceled', 'canceled_at' => date('Y-m-d H:i:s')],
            'user_id = ? AND status = "active"',
            [$userId]
        );
        
        // Create new subscription
        $subscriptionData = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'paypal_subscription_id' => $subscriptionId,
            'status' => 'active',
            'current_period_start' => $startDate,
            'current_period_end' => $endDate,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $newSubscriptionId = db_insert('subscriptions', $subscriptionData);
        
        if (!$newSubscriptionId) {
            throw new Exception('Failed to create subscription record.');
        }
        
        // Create payment record
        $paymentData = [
            'user_id' => $userId,
            'subscription_id' => $newSubscriptionId,
            'amount' => $plan['price'],
            'currency' => 'USD',
            'payment_method' => 'paypal',
            'status' => 'succeeded',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $paymentId = db_insert('payments', $paymentData);
        
        // Update user's subscription tier
        $userData = [
            'is_subscribed' => 1,
            'subscription_tier' => $plan['name'],
            'subscription_start' => $startDate,
            'subscription_end' => $endDate
        ];
        
        db_update('users', $userData, 'id = ?', [$userId]);
        
        // Set success message
        $success = 'Your subscription has been successfully activated!';
        
    } catch (Exception $e) {
        $error = 'Failed to process subscription: ' . $e->getMessage();
        error_log("Subscription processing error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing - Text Processing Platform</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dark-theme">
    <div class="container">
        <header>
            <h1>Text Processing Platform</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="checkout.php">Subscriptions</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="payment-result">
                <?php if (!empty($error)): ?>
                    <div class="error-container">
                        <h2>Payment Error</h2>
                        <div class="error-message"><?php echo $error; ?></div>
                        <p><a href="checkout.php" class="btn btn-primary">Try Again</a></p>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="success-container">
                        <h2>Payment Successful</h2>
                        <div class="success-message"><?php echo $success; ?></div>
                        <div class="subscription-details">
                            <h3>Your Subscription Details</h3>
                            <ul>
                                <li><strong>Plan:</strong> <?php echo htmlspecialchars($plan['name']); ?></li>
                                <li><strong>Price:</strong> $<?php echo number_format($plan['price'], 2); ?>/<?php echo $plan['interval']; ?></li>
                                <li><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($startDate)); ?></li>
                                <li><strong>Renewal Date:</strong> <?php echo date('F j, Y', strtotime($endDate)); ?></li>
                            </ul>
                        </div>
                        <div class="action-buttons">
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="my_subscription.php" class="btn btn-secondary">Manage Subscription</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="processing-container">
                        <h2>Processing Your Payment</h2>
                        <div class="loading-spinner"></div>
                        <p>Please wait while we process your payment...</p>
                    </div>
                    <script>
                        // Redirect to dashboard if no message is displayed (should not happen)
                        setTimeout(function() {
                            if (document.querySelector('.processing-container')) {
                                window.location.href = 'dashboard.php';
                            }
                        }, 5000);
                    </script>
                <?php endif; ?>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>