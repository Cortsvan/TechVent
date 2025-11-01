<?php
/**
 * Database Configuration for InfinityFree Hosting
 * TechVent Inventory Management System - PRODUCTION VERSION
 */

// InfinityFree Database Configuration
$host = 'sql204.infinityfree.com';
$dbname = 'if0_40163108_techvent';
$username = 'if0_40163108';
$password = 'techVent12313';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Connection successful
    // Uncomment the line below for testing connection
    // echo "Database connection successful!";
    
} catch (PDOException $e) {
    // Connection failed
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Helper function to execute a prepared statement
 * @param string $sql - The SQL query
 * @param array $params - Parameters for the query
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        throw new Exception("Query execution failed: " . $e->getMessage());
    }
}

/**
 * Helper function to get a single row
 * @param string $sql - The SQL query
 * @param array $params - Parameters for the query
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Helper function to get multiple rows
 * @param string $sql - The SQL query
 * @param array $params - Parameters for the query
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Helper function to get the last inserted ID
 * @return string
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}
?>