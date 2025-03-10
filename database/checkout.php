<?php
/**
 * PayPal Checkout Integration
 * 
 * This script handles PayPal checkout process for subscription plans
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

// Check if plan ID is provided
if (!isset($_GET['plan']) || !is_numeric($_GET['plan'])) {
    header('Location: my_subscription.php');
    exit();
}

$planId = (int)$_GET['plan'];

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

// PayPal configuration
$paypalConfig = [
    'client_id' => 'YOUR_PAYPAL_CLIENT_ID', // Replace with your PayPal client ID
    'client_secret' => 'YOUR_PAYPAL_CLIENT_SECRET', // Replace with your PayPal client secret
    'environment' => 'sandbox', // 'sandbox' or 'production'
    'currency' => 'USD',
    'return_url' => 'https://yourdomain.com/database/process_payment.php', // Replace with your domain
    'cancel_url' => 'https://yourdomain.com/database/my_subscription.php' // Replace with your domain
];

// Format price for display
function formatPrice($price, $currency = 'USD') {
    return '$' . number_format($price, 2) . ' ' . $currency;
}

// Calculate tax (if applicable)
$taxRate = 0; // Set your tax rate here (e.g., 0.1 for 10%)
$taxAmount = $plan['price'] * $taxRate;
$totalAmount = $plan['price'] + $taxAmount;

// Set success message if coming from successful signup
$successMessage = '';
if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
    $successMessage = 'Your account has been created successfully! Now you can subscribe to a plan.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Text Processing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- PayPal JavaScript SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypalConfig['client_id']; ?>&currency=<?php echo $paypalConfig['currency']; ?>"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-purple-400">Checkout</h1>
            <p class="text-gray-400">Complete your subscription purchase</p>
        </header>

        <?php if ($successMessage): ?>
            <div class="bg-green-800 text-white p-4 rounded mb-6">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Order Summary -->
            <div>
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Order Summary</h2>
                    
                    <div class="mb-6">
                        <div class="flex justify-between items-center py-3 border-b border-gray-700">
                            <span class="font-medium"><?php echo htmlspecialchars($plan['name']); ?> Plan</span>
                            <span><?php echo formatPrice($plan['price']); ?></span>
                        </div>
                        
                        <?php if ($taxRate > 0): ?>
                        <div class="flex justify-between items-center py-3 border-b border-gray-700">
                            <span>Tax</span>
                            <span><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center py-3 font-semibold text-lg">
                            <span>Total</span>
                            <span class="text-purple-300"><?php echo formatPrice($totalAmount); ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-medium text-purple-300 mb-2">Plan Features:</h3>
                        <ul class="space-y-2 pl-5 text-gray-300">
                            <?php 
                            $features = json_decode($plan['features'], true);
                            foreach ($features as $feature): 
                            ?>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-green-400 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="text-sm text-gray-400">
                        <p>You will be charged <?php echo formatPrice($totalAmount); ?> <?php echo strtolower($plan['interval']); ?>.</p>
                        <p class="mt-1">You can cancel your subscription at any time from your account.</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="my_subscription.php" class="text-purple-400 hover:text-purple-300 inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to plans
                    </a>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div>
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Payment Method</h2>
                    
                    <div class="mb-6">
                        <p class="text-gray-300 mb-4">Complete your subscription purchase securely with PayPal.</p>
                        
                        <!-- PayPal Button Container -->
                        <div id="paypal-button-container" class="mt-6"></div>
                        
                        <p class="text-sm text-gray-400 mt-4">
                            By completing this purchase, you agree to our 
                            <a href="#" class="text-purple-400 hover:text-purple-300">Terms of Service</a> and 
                            <a href="#" class="text-purple-400 hover:text-purple-300">Privacy Policy</a>.
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 bg-gray-800 rounded-lg p-4 flex items-center">
                    <svg class="w-6 h-6 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-gray-300 text-sm">Your payment information is securely processed by PayPal. We do not store your payment details.</span>
                </div>
            </div>
        </div>
        
        <footer class="mt-16 text-center text-gray-500 text-sm">
            <p>© <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        // Render the PayPal button
        paypal.Buttons({
            // Set up the transaction
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: '<?php echo htmlspecialchars($plan['name'] . ' Plan - ' . $plan['interval']); ?>',
                        amount: {
                            value: '<?php echo number_format($totalAmount, 2, '.', ''); ?>'
                        }
                    }]
                });
            },
            
            // Finalize the transaction
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(orderData) {
                    // Successful capture! For demo purposes:
                    // Store transaction details in our database
                    const transaction = orderData.purchase_units[0].payments.captures[0];
                    
                    // Send to server to process the subscription
                    window.location.href = '<?php echo $paypalConfig['return_url']; ?>?plan_id=<?php echo $planId; ?>&transaction_id=' + transaction.id + '&status=' + transaction.status;
                });
            },
            
            // Handle errors
            onError: function(err) {
                console.error('PayPal error:', err);
                alert('There was an error processing your payment. Please try again or contact support.');
            }
        }).render('#paypal-button-container');
    </script>
</body>
</html>