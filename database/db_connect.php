<?php
/**
 * Database Connection for Text Paraphrasing and Plagiarism Checking App
 * 
 * Use this file to connect to your MySQL database through phpMyAdmin
 */

// Database configuration
$db_host = 'localhost';     // Change if your database is on a different server
$db_name = 'text_processor'; // Your database name
$db_user = 'root';          // Your database username
$db_pass = '';              // Your database password

// Create database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements for real prepared statements
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // echo "Connected successfully";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

/**
 * Helper function to execute a query and fetch all results
 */
function db_select($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Helper function to execute an insert query and return the last insert ID
 */
function db_insert($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

/**
 * Helper function to execute an update query and return the number of affected rows
 */
function db_update($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Helper function to execute a delete query and return the number of affected rows
 */
function db_delete($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}