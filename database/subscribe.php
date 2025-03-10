<?php
/**
 * Subscription page for Text Paraphrasing and Plagiarism Checking App
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

// Process subscription purchase if plan_id is provided
$message = '';
if (isset($_POST['plan_id'])) {
    $plan_id = (int)$_POST['plan_id'];
    $result = processSubscriptionPurchase($user_id, $plan_id);
    
    if ($result['status'] === 'redirect') {
        // Redirect to PayPal checkout
        header('Location: ' . $result['redirect_url']);
        exit;
    } elseif ($result['status'] === 'success') {
        // Redirect to dashboard or display success message
        $message = '<div class="alert alert-success">' . $result['message'] . '</div>';
        header('Location: dashboard.php');
        exit;
    } else {
        // Display error message
        $message = '<div class="alert alert-danger">' . $result['message'] . '</div>';
    }
}

// Get all active subscription plans
$plans = db_select("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");

// Get user's current subscription
$user_subscription = db_select("SELECT subscription_tier, subscription_end FROM users WHERE id = ?", [$user_id])[0] ?? null;
$current_plan = $user_subscription ? $user_subscription['subscription_tier'] : 'none';
$has_active_subscription = hasActiveSubscription($user_id);

// Format features for display
foreach ($plans as &$plan) {
    $plan['features_array'] = json_decode($plan['features'], true) ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe - Text Processing App</title>
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
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .card-highlight {
            border: 2px solid var(--primary-color);
        }
        
        .card-header {
            background-color: rgba(108, 92, 231, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .period {
            font-size: 1rem;
            color: var(--text-muted);
        }
        
        .feature-list {
            list-style-type: none;
            padding-left: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list li::before {
            content: "✓";
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .current-plan {
            background-color: rgba(108, 92, 231, 0.1);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Choose Your Subscription Plan</h1>
        
        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <?php if ($has_active_subscription): ?>
            <div class="alert alert-info mb-4">
                You currently have an active subscription (<?php echo htmlspecialchars($current_plan); ?>)
                valid until <?php echo date('F j, Y', strtotime($user_subscription['subscription_end'])); ?>.
                <a href="cancel_subscription.php" class="alert-link">Cancel subscription</a>
            </div>
        <?php endif; ?>
        
        <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center">
            <?php foreach ($plans as $plan): ?>
                <div class="col">
                    <div class="card h-100 <?php echo $current_plan === $plan['name'] ? 'card-highlight' : ''; ?>">
                        <div class="card-header text-center py-3">
                            <h3 class="card-title mb-0">
                                <?php echo htmlspecialchars($plan['name']); ?>
                                <?php if ($current_plan === $plan['name']): ?>
                                    <span class="current-plan">Current Plan</span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <span class="price">$<?php echo number_format((float)$plan['price'], 2); ?></span>
                                <span class="period">/ <?php echo htmlspecialchars($plan['interval']); ?></span>
                            </div>
                            
                            <p class="text-center mb-4"><?php echo htmlspecialchars($plan['description']); ?></p>
                            
                            <ul class="feature-list mb-4">
                                <?php foreach ($plan['features_array'] as $feature): ?>
                                    <li><?php echo htmlspecialchars($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <form method="post" class="text-center">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                
                                <?php if ($current_plan === $plan['name']): ?>
                                    <button type="button" class="btn btn-secondary w-100" disabled>Current Plan</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <?php echo (float)$plan['price'] > 0 ? 'Subscribe with PayPal' : 'Get Started'; ?>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>