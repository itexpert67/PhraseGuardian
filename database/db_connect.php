<?php
/**
 * Database Connection Script
 * 
 * This file establishes a connection to your MySQL database and provides utility functions
 * for database operations.
 * 
 * IMPORTANT: Update the connection details with your actual database credentials.
 */

// Database configuration
define('DB_HOST', 'localhost');     // Change if your database is hosted elsewhere
define('DB_NAME', 'text_processor'); // Change to your database name
define('DB_USER', 'root');           // Change to your database username
define('DB_PASS', '');               // Change to your database password

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Connect to the database
 * 
 * @return PDO Database connection
 */
function db_connect() {
    static $pdo;
    
    if (!$pdo) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the error but don't expose details in production
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

/**
 * Execute a SELECT query
 * 
 * @param string $query SQL query with placeholders
 * @param array $params Parameters for prepared statement
 * @return array Results as associative array
 */
function db_select($query, $params = []) {
    try {
        $stmt = db_connect()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute an INSERT query
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|false Last inserted ID or false on failure
 */
function db_insert($table, $data) {
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = db_connect()->prepare($query);
        $stmt->execute(array_values($data));
        
        return db_connect()->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute an UPDATE query
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return int|false Number of affected rows or false on failure
 */
function db_update($table, $data, $where, $params = []) {
    try {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        
        $query = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        $stmt = db_connect()->prepare($query);
        
        $values = array_merge(array_values($data), $params);
        $stmt->execute($values);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a DELETE query
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return int|false Number of affected rows or false on failure
 */
function db_delete($table, $where, $params = []) {
    try {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = db_connect()->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Delete failed: " . $e->getMessage());
        return false;
    }
}