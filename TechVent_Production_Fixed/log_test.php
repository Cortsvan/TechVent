<?php
/**
 * Simple Logging Test for TechVent
 * DELETE AFTER DEBUGGING
 */

// Test if we can write logs
$logFile = 'debug.log';
$logMessage = "[" . date('Y-m-d H:i:s') . "] Test log entry - TechVent debugging\n";

if (file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX)) {
    echo "✅ Log file created successfully: $logFile<br>";
    echo "✅ Log message: " . htmlspecialchars(trim($logMessage)) . "<br>";
    
    // Test reading the log
    if (file_exists($logFile)) {
        echo "<h3>Current log contents:</h3>";
        echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
    }
} else {
    echo "❌ Could not write to log file<br>";
    echo "❌ Current directory: " . getcwd() . "<br>";
    echo "❌ Directory permissions: " . (is_writable('.') ? 'Writable' : 'Not writable') . "<br>";
}

// Test PHP error logging
echo "<h3>PHP Error Log Settings:</h3>";
echo "Log errors: " . (ini_get('log_errors') ? 'Yes' : 'No') . "<br>";
echo "Error log file: " . ini_get('error_log') . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? 'Yes' : 'No') . "<br>";

// Try to trigger a test error log
error_log("TechVent debug test: " . date('Y-m-d H:i:s'));
echo "✅ Test error logged<br>";
?>