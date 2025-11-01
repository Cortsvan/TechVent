<?php
/**
 * Database Configuration for InfinityFree Hosting
 * TechVent Inventory Management System
 * 
 * INSTRUCTIONS FOR SETUP:
 * 1. Replace 'YOUR_MYSQL_HOST' with your actual MySQL hostname from InfinityFree control panel
 * 2. Replace 'YOUR_MYSQL_USERNAME' with your MySQL username (usually starts with 'if0_' or 'epiz_')
 * 3. Replace 'YOUR_MYSQL_PASSWORD' with your MySQL password
 * 4. Keep 'techvent' as the database name (or change if you used a different name)
 */

// InfinityFree Database Configuration
// Get these details from your InfinityFree Control Panel > MySQL Databases
$host = 'sql204.infinityfree.com';               // e.g., 'sql200.infinityfree.com' or similar
$dbname = 'if0_40163108_techvent';       // Your FULL database name with prefix (e.g., if0_40163108_techvent)
$username = 'if0_40163108';              // Your MySQL username from InfinityFree (matches your account)
$password = 'techVent12313';       // Your MySQL password from InfinityFree

// Production settings for InfinityFree
try {
    // Create PDO connection with production-ready settings
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // For InfinityFree compatibility
            PDO::ATTR_PERSISTENT => false, // Disable persistent connections for shared hosting
        ]
    );
    
    // Set timezone for consistency
    $pdo->exec("SET time_zone = '+00:00'");
    
    // Connection successful - Do not echo in production
    
} catch (PDOException $e) {
    // Enhanced error handling for production
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly error message without exposing sensitive details
    die("Database connection error. Please contact the administrator.");
}

/**
 * Helper function to execute a prepared statement
 * Enhanced with better error handling for production
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
        // Log the actual error for debugging
        error_log("Query execution failed: " . $e->getMessage() . " | SQL: " . $sql);
        
        // Throw a generic error message to the user
        throw new Exception("Database query failed. Please try again later.");
    }
}

/**
 * Helper function to check if database connection is working
 * @return boolean
 */
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Security headers for production
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Apply security headers
setSecurityHeaders();

/**
 * Production Environment Settings
 */
// Disable error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Set timezone (adjust as needed for your location)
date_default_timezone_set('UTC');
?>