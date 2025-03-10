<?php
/**
 * My Subscription Page
 * 
 * This page allows users to view and manage their subscription
 */

// Include required files
require_once 'db_connect.php';
require_once 'subscription_process.php';

// Start or resume session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Process subscription cancellation
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $result = cancelUserSubscription($user_id);
    $message = '<div class="alert alert-' . ($result['status'] === 'success' ? 'success' : 'danger') . '">' . 
               $result['message'] . '</div>';
}

// Get user information
$user = db_select("SELECT username, email, is_subscribed, subscription_tier, subscription_start, subscription_end 
                 FROM users WHERE id = ?", [$user_id])[0] ?? null;

if (!$user) {
    // Redirect to login page if user not found
    header('Location: login.php');
    exit;
}

// Get subscription information
$subscription = null;
if ($user['is_subscribed']) {
    $subscription = db_select("SELECT s.*, p.name, p.price, p.interval, p.features 
                             FROM subscriptions s 
                             JOIN subscription_plans p ON s.plan_id = p.id 
                             WHERE s.user_id = ? AND s.status = 'active' 
                             ORDER BY s.created_at DESC LIMIT 1", 
                             [$user_id])[0] ?? null;
}

// Get payment history
$payments = db_select("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscription - Text Processing App</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --primary-dark: #5541d7;
            --dark-bg: #1e1e2e;
            --card-bg: #2a2a3a;
            --text-light: #f8f9fa;
            --text-muted: #adb5bd;
            --success-color: #00b894;
            --danger-color: #ff6b6b;
            --warning-color: #feca57;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 10px;
        }
        
        .card-header {
            background-color: rgba(108, 92, 231, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-active {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-inactive {
            background-color: var(--text-muted);
            color: var(--dark-bg);
        }
        
        .status-canceled {
            background-color: var(--danger-color);
            color: white;
        }
        
        .status-past_due {
            background-color: var(--warning-color);
            color: var(--dark-bg);
        }
        
        .table {
            color: var(--text-light);
        }
        
        .table th {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .table td {
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        .feature-list {
            list-style-type: none;
            padding-left: 0;
        }
        
        .feature-list li {
            padding: 5px 0;
        }
        
        .feature-list li::before {
            content: "✓";
            color: var(--primary-color);
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">My Subscription</h1>
        
        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <?php if ($user['is_subscribed'] && $subscription): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Current Subscription</h3>
                            <span class="status-badge status-<?php echo strtolower($subscription['status']); ?>">
                                <?php echo ucfirst($subscription['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($subscription['name']); ?> Plan</h5>
                                    <p class="text-muted">
                                        $<?php echo number_format((float)$subscription['price'], 2); ?> / 
                                        <?php echo htmlspecialchars($subscription['interval']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p>
                                        <strong>Started:</strong> 
                                        <?php echo date('F j, Y', strtotime($subscription['current_period_start'])); ?>
                                    </p>
                                    <p>
                                        <strong>Next billing:</strong> 
                                        <?php echo date('F j, Y', strtotime($subscription['current_period_end'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">Features:</h5>
                            <ul class="feature-list mb-4">
                                <?php foreach (json_decode($subscription['features'], true) ?? [] as $feature): ?>
                                    <li><?php echo htmlspecialchars($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($subscription['status'] === 'active'): ?>
                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel your subscription? You will still have access until the end of the billing period.');">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-danger">Cancel Subscription</button>
                                </form>
                            <?php elseif ($subscription['status'] === 'canceled' && !$subscription['cancel_at_period_end']): ?>
                                <div class="alert alert-info">
                                    Your subscription has been canceled. You will have access until 
                                    <?php echo date('F j, Y', strtotime($subscription['current_period_end'])); ?>.
                                </div>
                                <a href="subscribe.php" class="btn btn-primary">Subscribe Again</a>
                            <?php else: ?>
                                <a href="subscribe.php" class="btn btn-primary">Change Plan</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">No Active Subscription</h3>
                        </div>
                        <div class="card-body">
                            <p>You currently don't have an active subscription.</p>
                            <p>Subscribe to a plan to unlock premium features like unlimited paraphrasing, all styles, and advanced plagiarism detection.</p>
                            <a href="subscribe.php" class="btn btn-primary">View Plans</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($payments)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0">Payment History</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <?php echo $payment['currency'] === 'usd' ? '$' : ''; ?>
                                                    <?php echo number_format((float)$payment['amount'], 2); ?>
                                                    <?php echo $payment['currency'] !== 'usd' ? ' ' . strtoupper($payment['currency']) : ''; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['description'] ?? 'Subscription payment'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>