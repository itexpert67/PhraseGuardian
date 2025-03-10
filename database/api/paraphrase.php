<?php
/**
 * API Endpoint: Paraphrase Text
 * 
 * This endpoint handles text paraphrasing requests.
 * It accepts POST requests with JSON data.
 */

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Replace with specific domain in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Check if data is valid
if (!$data || !isset($data['text']) || !isset($data['style'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit();
}

// Get user ID if logged in
$userId = $_SESSION['user_id'] ?? null;

// If user is logged in, check subscription status and limits
if ($userId) {
    $user = db_select("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
    
    if ($user) {
        $tier = $user['subscription_tier'] ?? 'Basic';
        
        // Get usage count
        $usageCount = db_select(
            "SELECT COUNT(*) as count FROM text_processing_history 
             WHERE user_id = ? AND processing_type = 'paraphrased'",
            [$userId]
        )[0]['count'] ?? 0;
        
        // Check limits based on tier
        $tierLimits = [
            'Basic' => 20,
            'Premium' => 999999, // Unlimited
            'Professional' => 999999 // Unlimited
        ];
        
        $limit = $tierLimits[$tier] ?? $tierLimits['Basic'];
        
        if ($usageCount >= $limit && $tier === 'Basic') {
            http_response_code(403);
            echo json_encode([
                'error' => 'You have reached your paraphrasing limit. Please upgrade your subscription for unlimited paraphrasing.',
                'upgradeRequired' => true
            ]);
            exit();
        }
    }
}

// Process the text
$text = $data['text'];
$style = $data['style'];

// In a real production environment, you would use an actual AI service here
// This is a simplified example for demonstration
function paraphraseText($text, $style) {
    // Simple paraphrasing logic based on style
    switch($style) {
        case 'fluent':
            $result = "Fluently paraphrased: " . $text;
            break;
        case 'academic':
            $result = "Academically paraphrased: " . $text;
            break;
        case 'simple':
            $result = "Simplified version: " . $text;
            break;
        case 'creative':
            $result = "Creative rewrite: " . $text;
            break;
        case 'business':
            $result = "Business style: " . $text;
            break;
        default:
            $result = "Standard paraphrase: " . $text;
    }
    
    // Calculate metrics
    $uniquenessScore = rand(75, 95);
    $readabilityScore = rand(70, 90);
    $wordsChanged = str_word_count($text) * rand(30, 70) / 100;
    $wordsChangedPercent = round($wordsChanged / str_word_count($text) * 100);
    
    return [
        'text' => $result,
        'uniquenessScore' => $uniquenessScore,
        'readabilityScore' => $readabilityScore,
        'wordsChanged' => $wordsChanged,
        'wordsChangedPercent' => $wordsChangedPercent
    ];
}

try {
    // Paraphrase the text
    $result = paraphraseText($text, $style);
    
    // Save history if user is logged in
    if ($userId) {
        $historyData = [
            'user_id' => $userId,
            'title' => substr($text, 0, 50) . '...',
            'original_text' => $text,
            'processed_text' => $result['text'],
            'processing_type' => 'paraphrased',
            'style' => $style,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('text_processing_history', $historyData);
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to paraphrase text: ' . $e->getMessage()
    ]);
}