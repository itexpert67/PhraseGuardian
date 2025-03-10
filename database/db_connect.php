<?php
/**
 * Database Connection and Utility Functions
 *
 * This file handles database connections and provides utility functions
 * for database operations.
 */

// Database configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'text_processor'
];

/**
 * Get database connection
 *
 * @return PDO Database connection
 */
function db_connect() {
    global $db_config;
    
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        } catch (PDOException $e) {
            // Log the error
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Execute a SELECT query
 *
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Query results
 */
function db_select($query, $params = []) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database query failed: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

/**
 * Execute an INSERT query
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value pairs
 * @return int|false Last insert ID or false on failure
 */
function db_insert($table, $data) {
    try {
        $pdo = db_connect();
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $columnString = implode(', ', $columns);
        $placeholderString = implode(', ', $placeholders);
        
        $query = "INSERT INTO {$table} ({$columnString}) VALUES ({$placeholderString})";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_values($data));
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Database insert failed: " . $e->getMessage());
        throw new Exception("Database insert failed");
    }
}

/**
 * Execute an UPDATE query
 *
 * @param string $table Table name
 * @param array $data Associative array of column => value pairs
 * @param string $where Where clause
 * @param array $whereParams Parameters for where clause
 * @return int Number of affected rows
 */
function db_update($table, $data, $where, $whereParams = []) {
    try {
        $pdo = db_connect();
        
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        
        $setString = implode(', ', $set);
        $query = "UPDATE {$table} SET {$setString} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database update failed: " . $e->getMessage());
        throw new Exception("Database update failed");
    }
}

/**
 * Execute a DELETE query
 *
 * @param string $table Table name
 * @param string $where Where clause
 * @param array $params Parameters for where clause
 * @return int Number of affected rows
 */
function db_delete($table, $where, $params = []) {
    try {
        $pdo = db_connect();
        
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database delete failed: " . $e->getMessage());
        throw new Exception("Database delete failed");
    }
}