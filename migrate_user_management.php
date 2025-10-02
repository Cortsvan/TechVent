<?php
/**
 * Database Migration Script for User Management Updates
 * Adds phone, department, and is_active fields to users table
 */

// Include database configuration
require_once 'config/db.php';

echo "=== TechVent Database Migration ===\n";
echo "Adding new fields to users table...\n\n";

try {
    echo "1. Checking current table structure...\n";
    
    // Check if columns already exist
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    $columnsToAdd = [];
    
    if (!in_array('phone', $existingColumns)) {
        $columnsToAdd[] = 'phone';
    }
    
    if (!in_array('department', $existingColumns)) {
        $columnsToAdd[] = 'department';
    }
    
    if (!in_array('is_active', $existingColumns)) {
        $columnsToAdd[] = 'is_active';
    }
    
    if (empty($columnsToAdd)) {
        echo "✅ All columns already exist. No migration needed.\n";
        exit(0);
    }
    
    echo "📋 Columns to add: " . implode(', ', $columnsToAdd) . "\n\n";
    
    // Add phone column
    if (in_array('phone', $columnsToAdd)) {
        echo "2. Adding 'phone' column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
        echo "✅ Phone column added successfully.\n";
    } else {
        echo "2. Phone column already exists, skipping...\n";
    }
    
    // Add department column
    if (in_array('department', $columnsToAdd)) {
        echo "3. Adding 'department' column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER " . (in_array('phone', $existingColumns) || in_array('phone', $columnsToAdd) ? 'phone' : 'email'));
        echo "✅ Department column added successfully.\n";
    } else {
        echo "3. Department column already exists, skipping...\n";
    }
    
    // Add is_active column
    if (in_array('is_active', $columnsToAdd)) {
        echo "4. Adding 'is_active' column...\n";
        $afterColumn = 'email';
        if (in_array('department', $existingColumns) || in_array('department', $columnsToAdd)) {
            $afterColumn = 'department';
        } elseif (in_array('phone', $existingColumns) || in_array('phone', $columnsToAdd)) {
            $afterColumn = 'phone';
        }
        
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER {$afterColumn}");
        echo "✅ Is_active column added successfully.\n";
        
        // Set all existing users as active
        echo "5. Setting all existing users as active...\n";
        $updateResult = $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
        echo "✅ Updated {$updateResult} users to active status.\n";
    } else {
        echo "4. Is_active column already exists, skipping...\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "📊 Final table structure:\n";
    
    // Show final table structure
    $finalColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($finalColumns as $column) {
        $nullable = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] !== null ? "DEFAULT '{$column['Default']}'" : '';
        echo "   - {$column['Field']}: {$column['Type']} {$nullable} {$default}\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "You can now refresh your user management page.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>