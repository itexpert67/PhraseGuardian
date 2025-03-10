<?php
/**
 * API Endpoint: Check Plagiarism
 * 
 * This endpoint handles plagiarism checking requests.
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
if (!$data || !isset($data['text'])) {
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
             WHERE user_id = ? AND processing_type = 'plagiarism'",
            [$userId]
        )[0]['count'] ?? 0;
        
        // Check limits based on tier
        $tierLimits = [
            'Basic' => 5,
            'Premium' => 999999, // Unlimited
            'Professional' => 999999 // Unlimited
        ];
        
        $limit = $tierLimits[$tier] ?? $tierLimits['Basic'];
        
        if ($usageCount >= $limit && $tier === 'Basic') {
            http_response_code(403);
            echo json_encode([
                'error' => 'You have reached your plagiarism check limit. Please upgrade your subscription for unlimited checks.',
                'upgradeRequired' => true
            ]);
            exit();
        }
    }
}

// Get the text to check
$text = $data['text'];

// Get all plagiarism sources from the database
$sources = db_select("SELECT * FROM plagiarism_sources", []);

// Function to check for plagiarism
function checkPlagiarism($text, $sources) {
    $matches = [];
    $totalMatchPercentage = 0;
    
    // Preprocess text
    $text = strtolower($text);
    $text = preg_replace('/[^\w\s]/', '', $text);
    
    // Split into sentences (simple implementation)
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    
    foreach ($sources as $source) {
        $sourceText = strtolower($source['source_text']);
        $sourceText = preg_replace('/[^\w\s]/', '', $sourceText);
        
        // Check for exact matches
        $similarity = similar_text($text, $sourceText, $percent);
        
        if ($percent > 30) {
            // Find matching sections
            foreach ($sentences as $sentence) {
                if (strlen($sentence) > 20 && strpos($sourceText, $sentence) !== false) {
                    $matchPercent = min(100, strlen($sentence) / strlen($text) * 100 * 3);
                    
                    $matches[] = [
                        'id' => uniqid(),
                        'text' => $sentence,
                        'matchPercentage' => round($matchPercent, 2),
                        'source' => $source['source_name'],
                        'url' => $source['source_url']
                    ];
                    
                    $totalMatchPercentage += $matchPercent;
                }
            }
        }
    }
    
    // Cap total percentage at 100
    $totalMatchPercentage = min(100, $totalMatchPercentage);
    
    // Remove duplicate matches
    $uniqueMatches = [];
    foreach ($matches as $match) {
        $found = false;
        foreach ($uniqueMatches as $uniqueMatch) {
            if ($uniqueMatch['text'] === $match['text']) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $uniqueMatches[] = $match;
        }
    }
    
    return [
        'text' => $text,
        'totalMatchPercentage' => round($totalMatchPercentage, 2),
        'matches' => $uniqueMatches
    ];
}

try {
    // Check for plagiarism
    $result = checkPlagiarism($text, $sources);
    
    // Save history if user is logged in
    if ($userId) {
        $historyData = [
            'user_id' => $userId,
            'title' => substr($text, 0, 50) . '...',
            'original_text' => $text,
            'processing_type' => 'plagiarism',
            'plagiarism_percentage' => $result['totalMatchPercentage'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $historyId = db_insert('text_processing_history', $historyData);
        
        // Save plagiarism results
        if ($historyId) {
            $plagiarismData = [
                'history_id' => $historyId,
                'total_percentage' => $result['totalMatchPercentage'],
                'matches_json' => json_encode($result['matches']),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            db_insert('plagiarism_results', $plagiarismData);
        }
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
        'error' => 'Failed to check plagiarism: ' . $e->getMessage()
    ]);
}