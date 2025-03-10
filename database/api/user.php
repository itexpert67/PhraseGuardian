<?php
/**
 * API Endpoint: Get User Details
 * 
 * This endpoint retrieves user information for the authenticated user.
 * It accepts GET requests and returns user data.
 */

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Replace with specific domain in production
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once '../db_connect.php';

// Start session for user authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Get user data
    $user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Get subscription data
    $subscription = db_select(
        "SELECT s.*, p.name as plan_name, p.price, p.interval, p.features
         FROM subscriptions s
         JOIN subscription_plans p ON s.plan_id = p.id
         WHERE s.user_id = ? AND s.status = 'active'
         ORDER BY s.id DESC LIMIT 1", 
        [$userId]
    )[0] ?? null;
    
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
    
    // Get tier limits
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
    
    // Prepare user data for response (excluding sensitive information)
    $userData = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'tier' => $currentTier,
        'isSubscribed' => (bool)$user['is_subscribed'],
        'usageStats' => $usageStats,
        'limits' => $limits,
        'subscription' => null
    ];
    
    if ($subscription) {
        $userData['subscription'] = [
            'id' => $subscription['id'],
            'planId' => $subscription['plan_id'],
            'planName' => $subscription['plan_name'],
            'price' => $subscription['price'],
            'interval' => $subscription['interval'],
            'features' => json_decode($subscription['features'], true),
            'currentPeriodStart' => $subscription['current_period_start'],
            'currentPeriodEnd' => $subscription['current_period_end'],
            'cancelAtPeriodEnd' => (bool)$subscription['cancel_at_period_end'],
            'canceledAt' => $subscription['canceled_at']
        ];
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve user data: ' . $e->getMessage()
    ]);
}