<?php
/**
 * API Endpoint: Get Text Processing History
 * 
 * This endpoint retrieves text processing history from the database.
 * It accepts GET requests with optional user_id parameter.
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

try {
    // Determine the user ID (use 0 or null for non-authenticated users)
    $userId = $_SESSION['user_id'] ?? null;
    
    // If user is not logged in, return empty array
    if (!$userId) {
        echo json_encode([
            'success' => true,
            'history' => []
        ]);
        exit();
    }
    
    // Get history records for the user
    $history = db_select(
        "SELECT h.*, pr.matches_json 
         FROM text_processing_history h
         LEFT JOIN plagiarism_results pr ON h.id = pr.history_id
         WHERE h.user_id = ?
         ORDER BY h.created_at DESC
         LIMIT 50", 
        [$userId]
    );
    
    // Format the history records
    $formattedHistory = [];
    foreach ($history as $item) {
        $formattedItem = [
            'id' => $item['id'],
            'title' => $item['title'],
            'originalText' => $item['original_text'],
            'processedText' => $item['processed_text'],
            'processingType' => $item['processing_type'],
            'style' => $item['style'],
            'plagiarismPercentage' => $item['plagiarism_percentage'],
            'createdAt' => $item['created_at'],
            'displayTime' => date('M j, Y g:i A', strtotime($item['created_at']))
        ];
        
        // Add matches for plagiarism checks
        if ($item['processing_type'] === 'plagiarism' && !empty($item['matches_json'])) {
            $formattedItem['matches'] = json_decode($item['matches_json'], true);
        }
        
        $formattedHistory[] = $formattedItem;
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'history' => $formattedHistory
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve history: ' . $e->getMessage()
    ]);
}