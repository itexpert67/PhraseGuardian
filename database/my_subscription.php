<?php
/**
 * Subscription Management Page
 * 
 * This page allows users to view and manage their subscription details
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

// Get user details
$user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
if (!$user) {
    die("User not found");
}

// Get current subscription if any
$subscription = db_select(
    "SELECT s.*, p.name as plan_name, p.price, p.interval 
     FROM subscriptions s
     JOIN subscription_plans p ON s.plan_id = p.id
     WHERE s.user_id = ? AND s.status = 'active'
     ORDER BY s.id DESC LIMIT 1", 
    [$userId]
)[0] ?? null;

// Get available plans
$availablePlans = db_select("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");

// Handle subscription cancellation
$cancelMessage = '';
if (isset($_POST['cancel_subscription']) && $subscription) {
    $result = db_update('subscriptions', 
        ['cancel_at_period_end' => 1, 'canceled_at' => date('Y-m-d H:i:s')],
        'id = ?', 
        [$subscription['id']]
    );
    
    if ($result) {
        $cancelMessage = 'Your subscription has been set to cancel at the end of the billing period.';
        // Refresh subscription data
        $subscription = db_select(
            "SELECT s.*, p.name as plan_name, p.price, p.interval 
             FROM subscriptions s
             JOIN subscription_plans p ON s.plan_id = p.id
             WHERE s.id = ?", 
            [$subscription['id']]
        )[0] ?? null;
    } else {
        $cancelMessage = 'There was an error canceling your subscription. Please try again.';
    }
}

// Get payment history
$paymentHistory = db_select(
    "SELECT p.*, s.plan_id, sp.name as plan_name 
     FROM payments p
     LEFT JOIN subscriptions s ON p.subscription_id = s.id
     LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC",
    [$userId]
);

// Function to format dates
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to format price
function formatPrice($price, $currency = 'USD') {
    return '$' . number_format($price, 2) . ' ' . $currency;
}

// Get usage statistics
$usageStats = [
    'paraphrasing' => db_select(
        "SELECT COUNT(*) as count FROM text_processing_history 
         WHERE user_id = ? AND processing_type = 'paraphrased'",
        [$userId]
    )[0]['count'] ?? 0,
    
    'plagiarism' => db_select(
        "SELECT COUNT(*) as count FROM text_processing_history 
         WHERE user_id = ? AND processing_type = 'plagiarism'",
        [$userId]
    )[0]['count'] ?? 0
];

// Get subscription tier limits
$tierLimits = [
    'Basic' => [
        'paraphrasing' => 20,
        'plagiarism' => 5
    ],
    'Premium' => [
        'paraphrasing' => 999999, // Unlimited
        'plagiarism' => 999999    // Unlimited
    ],
    'Professional' => [
        'paraphrasing' => 999999, // Unlimited
        'plagiarism' => 999999    // Unlimited
    ]
];

// Get current tier
$currentTier = $user['subscription_tier'] ?? 'Basic';
$limits = $tierLimits[$currentTier] ?? $tierLimits['Basic'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscription | Text Processing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-purple-400">My Subscription</h1>
            <p class="text-gray-400">Manage your subscription details and payment history</p>
        </header>

        <?php if ($cancelMessage): ?>
            <div class="bg-green-800 text-white p-4 rounded mb-6">
                <?php echo $cancelMessage; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Current Subscription -->
            <div class="col-span-2">
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Current Plan</h2>
                    
                    <?php if ($subscription): ?>
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400">Plan:</span>
                                <span class="font-medium text-white"><?php echo htmlspecialchars($subscription['plan_name']); ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400">Status:</span>
                                <span class="font-medium <?php echo $subscription['cancel_at_period_end'] ? 'text-yellow-400' : 'text-green-400'; ?>">
                                    <?php echo $subscription['cancel_at_period_end'] ? 'Cancels on ' . formatDate($subscription['current_period_end']) : 'Active'; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400">Price:</span>
                                <span class="font-medium text-white"><?php echo formatPrice($subscription['price']); ?>/<?php echo $subscription['interval']; ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-400">Current period:</span>
                                <span class="font-medium text-white">
                                    <?php echo formatDate($subscription['current_period_start']); ?> to 
                                    <?php echo formatDate($subscription['current_period_end']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!$subscription['cancel_at_period_end']): ?>
                            <form method="post" onsubmit="return confirm('Are you sure you want to cancel your subscription? You will still have access until the end of the current billing period.');">
                                <button type="submit" name="cancel_subscription" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded transition duration-200">
                                    Cancel Subscription
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-400 mb-4">You don't have an active subscription plan.</p>
                            <a href="#plans" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-6 rounded-lg transition duration-200">
                                View Available Plans
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div>
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                    <h2 class="text-xl font-semibold mb-4 text-purple-300">Usage Statistics</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-400">Paraphrasing</span>
                                <span class="text-sm text-gray-300">
                                    <?php echo $usageStats['paraphrasing']; ?>/<?php echo $limits['paraphrasing'] == 999999 ? '∞' : $limits['paraphrasing']; ?>
                                </span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2.5">
                                <?php 
                                $percentage = $limits['paraphrasing'] == 999999 ? 0 : min(100, ($usageStats['paraphrasing'] / $limits['paraphrasing']) * 100);
                                ?>
                                <div class="bg-purple-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-400">Plagiarism Checks</span>
                                <span class="text-sm text-gray-300">
                                    <?php echo $usageStats['plagiarism']; ?>/<?php echo $limits['plagiarism'] == 999999 ? '∞' : $limits['plagiarism']; ?>
                                </span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2.5">
                                <?php 
                                $percentage = $limits['plagiarism'] == 999999 ? 0 : min(100, ($usageStats['plagiarism'] / $limits['plagiarism']) * 100);
                                ?>
                                <div class="bg-purple-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Plans -->
        <div id="plans" class="mt-12">
            <h2 class="text-2xl font-bold text-purple-400 mb-6">Available Plans</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($availablePlans as $plan): ?>
                    <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg border-2 <?php echo $currentTier == $plan['name'] ? 'border-purple-500' : 'border-gray-700'; ?>">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 text-purple-300"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="text-3xl font-bold mb-4">
                                <?php echo formatPrice($plan['price']); ?>
                                <span class="text-sm text-gray-400">/<?php echo $plan['interval']; ?></span>
                            </div>
                            <p class="text-gray-400 mb-6"><?php echo htmlspecialchars($plan['description']); ?></p>
                            
                            <ul class="space-y-3 mb-6">
                                <?php 
                                $features = json_decode($plan['features'], true);
                                foreach ($features as $feature): 
                                ?>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-300"><?php echo htmlspecialchars($feature); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($currentTier == $plan['name']): ?>
                                <button disabled class="w-full bg-purple-700 text-white py-2 rounded-lg opacity-70 cursor-not-allowed">
                                    Current Plan
                                </button>
                            <?php else: ?>
                                <a href="checkout.php?plan=<?php echo $plan['id']; ?>" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-2 rounded-lg transition duration-200">
                                    <?php echo $plan['price'] > 0 ? 'Subscribe' : 'Select Free Plan'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($paymentHistory)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-purple-400 mb-6">Payment History</h2>
                
                <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-300">Date</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-300">Description</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-300">Amount</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-300">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="py-3 px-4 text-sm text-gray-300"><?php echo formatDate($payment['created_at']); ?></td>
                                    <td class="py-3 px-4 text-sm text-gray-300">
                                        <?php 
                                        echo $payment['description'] ?? 'Subscription payment - ' . ($payment['plan_name'] ?? 'Unknown plan');
                                        ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-300"><?php echo formatPrice($payment['amount'], $payment['currency']); ?></td>
                                    <td class="py-3 px-4 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs <?php echo $payment['status'] === 'succeeded' ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-yellow-300'; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <footer class="mt-16 text-center text-gray-500 text-sm">
            <p>© <?php echo date('Y'); ?> Text Processing Platform. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>