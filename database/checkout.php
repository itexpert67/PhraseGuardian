<?php
/**
 * Checkout Page
 * 
 * Handles subscription checkout process with PayPal integration.
 */

// Start session
session_start();

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$error = '';
$success = '';
$planId = isset($_GET['plan']) ? intval($_GET['plan']) : 0;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php?redirect=checkout.php' . ($planId ? "?plan=$planId" : ''));
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

// Get available plans
$plans = db_select("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");

// Get selected plan
$selectedPlan = null;
if ($planId) {
    foreach ($plans as $plan) {
        if ($plan['id'] == $planId) {
            $selectedPlan = $plan;
            break;
        }
    }
}

// Check if user already has an active subscription
$activeSubscription = db_select(
    "SELECT s.*, p.name as plan_name, p.price, p.interval 
     FROM subscriptions s
     JOIN subscription_plans p ON s.plan_id = p.id
     WHERE s.user_id = ? AND s.status = 'active' AND s.current_period_end > NOW()",
    [$userId]
);

$hasActiveSubscription = !empty($activeSubscription);

// Set page title
$pageTitle = 'Subscription Checkout';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Text Processing Platform</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD&vault=true"></script>
</head>
<body class="dark-theme">
    <div class="container">
        <header>
            <h1>Text Processing Platform</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="checkout.php" class="active">Subscriptions</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="checkout-page">
                <h2>Choose Your Subscription Plan</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($hasActiveSubscription): ?>
                    <div class="subscription-info">
                        <h3>Your Current Subscription</h3>
                        <div class="subscription-details">
                            <p><strong>Plan:</strong> <?php echo htmlspecialchars($activeSubscription[0]['plan_name']); ?></p>
                            <p><strong>Price:</strong> $<?php echo number_format($activeSubscription[0]['price'], 2); ?>/<?php echo $activeSubscription[0]['interval']; ?></p>
                            <p><strong>Status:</strong> Active</p>
                            <p><strong>Renewal Date:</strong> <?php echo date('F j, Y', strtotime($activeSubscription[0]['current_period_end'])); ?></p>
                        </div>
                        <p>You can upgrade or change your plan at any time. Your current plan will remain active until the end of the billing period.</p>
                        <p><a href="my_subscription.php" class="btn btn-secondary">Manage Subscription</a></p>
                    </div>
                <?php endif; ?>
                
                <div class="plans-container">
                    <?php foreach ($plans as $plan): ?>
                        <div class="plan-card <?php echo $selectedPlan && $selectedPlan['id'] == $plan['id'] ? 'selected' : ''; ?> <?php echo $plan['name'] === 'Premium' ? 'recommended' : ''; ?>">
                            <?php if ($plan['name'] === 'Premium'): ?>
                                <div class="plan-badge">Most Popular</div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                            
                            <div class="plan-price">
                                <span class="amount">$<?php echo number_format($plan['price'], 2); ?></span>
                                <span class="interval">/<?php echo $plan['interval']; ?></span>
                            </div>
                            
                            <div class="plan-features">
                                <ul>
                                    <?php 
                                    $features = json_decode($plan['features'], true);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <?php if ($plan['price'] > 0): ?>
                                <?php if ($selectedPlan && $selectedPlan['id'] == $plan['id']): ?>
                                    <div class="checkout-section">
                                        <div id="paypal-button-container-<?php echo $plan['id']; ?>"></div>
                                        <script>
                                            // Render the PayPal button for this plan
                                            paypal.Buttons({
                                                style: {
                                                    layout: 'vertical',
                                                    color: 'blue',
                                                    shape: 'rect',
                                                    label: 'subscribe'
                                                },
                                                createSubscription: function(data, actions) {
                                                    return actions.subscription.create({
                                                        'plan_id': '<?php echo $plan['paypal_plan_id']; ?>' // This should be your PayPal plan ID
                                                    });
                                                },
                                                onApprove: function(data, actions) {
                                                    // Capture the subscription ID for server-side processing
                                                    console.log('Subscription approved: ' + data.subscriptionID);
                                                    
                                                    // Submit the subscription ID to your server
                                                    window.location.href = 'process_payment.php?subscription_id=' + data.subscriptionID + '&plan_id=<?php echo $plan['id']; ?>';
                                                }
                                            }).render('#paypal-button-container-<?php echo $plan['id']; ?>');
                                        </script>
                                    </div>
                                <?php else: ?>
                                    <a href="checkout.php?plan=<?php echo $plan['id']; ?>" class="btn btn-primary">Select Plan</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Free Plan</p>
                                <?php if ($user['subscription_tier'] !== 'Basic'): ?>
                                    <a href="downgrade_to_free.php" class="btn btn-secondary">Downgrade to Free</a>
                                <?php else: ?>
                                    <span class="btn btn-disabled">Current Plan</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="checkout-info">
                    <h3>Subscription Benefits</h3>
                    <ul>
                        <li>Unlimited text paraphrasing</li>
                        <li>Advanced plagiarism detection</li>
                        <li>Multiple paraphrasing styles</li>
                        <li>Priority customer support</li>
                        <li>Cancel anytime</li>
                    </ul>
                    
                    <div class="security-info">
                        <p>All payments are securely processed through PayPal. We do not store your payment information.</p>
                        <p>By subscribing, you agree to our <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>.</p>
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