<?php
/**
 * Subscription Management Page
 * 
 * Allows users to view and manage their subscription.
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
    header('Location: login.php?redirect=my_subscription.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get user data
$user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;

if (!$user) {
    // Invalid user ID
    session_destroy();
    header('Location: login.php');
    exit();
}

// Get active subscription
$subscription = db_select(
    "SELECT s.*, p.name as plan_name, p.price, p.interval, p.features 
     FROM subscriptions s
     JOIN subscription_plans p ON s.plan_id = p.id
     WHERE s.user_id = ? AND s.status = 'active'
     ORDER BY s.created_at DESC LIMIT 1", 
    [$userId]
);

// Get payment history
$payments = db_select(
    "SELECT * FROM payments 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 10", 
    [$userId]
);

// Process subscription cancellation
if (isset($_POST['cancel_subscription']) && !empty($subscription)) {
    try {
        $subscriptionId = $subscription[0]['id'];
        $paypalSubscriptionId = $subscription[0]['paypal_subscription_id'];
        
        // In a real application, you would call PayPal API to cancel the subscription
        // For this example, we'll just update our database
        
        // Update subscription status
        db_update(
            'subscriptions',
            [
                'cancel_at_period_end' => 1,
                'canceled_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$subscriptionId]
        );
        
        $success = 'Your subscription has been canceled. You will continue to have access until the end of your current billing period.';
        
        // Refresh subscription data
        $subscription = db_select(
            "SELECT s.*, p.name as plan_name, p.price, p.interval, p.features 
             FROM subscriptions s
             JOIN subscription_plans p ON s.plan_id = p.id
             WHERE s.user_id = ? AND s.status = 'active'
             ORDER BY s.created_at DESC LIMIT 1", 
            [$userId]
        );
    } catch (Exception $e) {
        $error = 'Failed to cancel subscription: ' . $e->getMessage();
        error_log("Subscription cancellation error: " . $e->getMessage());
    }
}

// Set page title
$pageTitle = 'My Subscription';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Text Processing Platform</title>
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
                    <li><a href="my_subscription.php" class="active">My Subscription</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="subscription-page">
                <h2>My Subscription</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (empty($subscription)): ?>
                    <div class="no-subscription">
                        <p>You don't have an active subscription.</p>
                        <p>Your current tier: <strong><?php echo htmlspecialchars($user['subscription_tier']); ?></strong></p>
                        <p><a href="checkout.php" class="btn btn-primary">View Subscription Plans</a></p>
                    </div>
                <?php else: ?>
                    <?php 
                    $sub = $subscription[0];
                    $isCanceled = $sub['cancel_at_period_end'] == 1;
                    ?>
                    <div class="subscription-details">
                        <h3>Current Subscription</h3>
                        
                        <div class="subscription-card">
                            <div class="subscription-header">
                                <h4><?php echo htmlspecialchars($sub['plan_name']); ?> Plan</h4>
                                <div class="subscription-status <?php echo $isCanceled ? 'canceled' : 'active'; ?>">
                                    <?php echo $isCanceled ? 'Canceled' : 'Active'; ?>
                                </div>
                            </div>
                            
                            <div class="subscription-info">
                                <p><strong>Price:</strong> $<?php echo number_format($sub['price'], 2); ?>/<?php echo $sub['interval']; ?></p>
                                <p><strong>Started:</strong> <?php echo date('F j, Y', strtotime($sub['current_period_start'])); ?></p>
                                <p>
                                    <strong><?php echo $isCanceled ? 'Access until' : 'Next billing date'; ?>:</strong> 
                                    <?php echo date('F j, Y', strtotime($sub['current_period_end'])); ?>
                                </p>
                                
                                <?php if ($isCanceled): ?>
                                    <p class="canceled-info">Your subscription has been canceled and will not renew.</p>
                                    <p><a href="checkout.php" class="btn btn-secondary">Resubscribe</a></p>
                                <?php else: ?>
                                    <form method="post" action="my_subscription.php" onsubmit="return confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your current billing period.');">
                                        <button type="submit" name="cancel_subscription" class="btn btn-danger">Cancel Subscription</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="subscription-features">
                                <h5>Features:</h5>
                                <ul>
                                    <?php 
                                    $features = json_decode($sub['features'], true);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="payment-history">
                    <h3>Payment History</h3>
                    
                    <?php if (empty($payments)): ?>
                        <p>No payment history found.</p>
                    <?php else: ?>
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                        <td><?php echo $payment['currency']; ?> <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <span class="payment-status <?php echo strtolower($payment['status']); ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="subscription-faq">
                    <h3>Frequently Asked Questions</h3>
                    
                    <div class="faq-item">
                        <h4>How do I upgrade my subscription?</h4>
                        <p>You can upgrade your subscription by visiting the <a href="checkout.php">Subscription Plans</a> page and selecting a new plan. Your current plan will be replaced with the new one immediately.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4>What happens when I cancel my subscription?</h4>
                        <p>When you cancel your subscription, you will continue to have access to your plan's features until the end of your current billing period. After that, your account will revert to the Basic (free) tier.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4>How can I update my payment information?</h4>
                        <p>To update your payment information, please visit your PayPal account and update your payment settings for this subscription.</p>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="assets/js/scripts.js"></script>
</body>
</html>