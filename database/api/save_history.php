<?php
/**
 * API Endpoint: Save Text Processing History
 * 
 * This endpoint handles saving text processing history to the database.
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
if (!$data || !isset($data['type']) || !isset($data['text'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit();
}

// Determine the user ID (use 0 or null for non-authenticated users)
$userId = $_SESSION['user_id'] ?? null;

// Prepare data for insertion
$historyData = [
    'user_id' => $userId,
    'title' => $data['title'] ?? substr($data['text'], 0, 50) . '...',
    'original_text' => $data['text'],
    'processed_text' => $data['result'] ?? null,
    'processing_type' => $data['type'],
    'style' => $data['style'] ?? null,
    'plagiarism_percentage' => $data['plagiarismPercentage'] ?? null,
    'created_at' => date('Y-m-d H:i:s')
];

try {
    // Insert history record
    $historyId = db_insert('text_processing_history', $historyData);
    
    if (!$historyId) {
        throw new Exception("Failed to save history");
    }
    
    // If this is a plagiarism check with matches, save the details
    if ($data['type'] === 'plagiarism' && isset($data['matches'])) {
        $plagiarismData = [
            'history_id' => $historyId,
            'total_percentage' => $data['plagiarismPercentage'],
            'matches_json' => json_encode($data['matches']),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('plagiarism_results', $plagiarismData);
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'history_id' => $historyId
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save history: ' . $e->getMessage()
    ]);
}