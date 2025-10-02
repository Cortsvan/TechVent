<?php
/**
 * Simple Database Migration - TechVent
 * Run this once to add profile fields
 */

require_once 'config/db.php';

echo "<h2>TechVent Database Migration</h2>";

try {
    global $pdo;
    
    // Check if fields already exist
    try {
        $testQuery = $pdo->prepare("SELECT phone, department, location, timezone FROM users LIMIT 1");
        $testQuery->execute();
        echo "<div style='color: green;'>✅ Migration already completed! Extended profile fields are available.</div>";
    } catch (Exception $e) {
        // Fields don't exist, run migration
        echo "<h3>Running migration...</h3>";
        
        $migrations = [
            "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password",
            "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER phone", 
            "ALTER TABLE users ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER department",
            "ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT NULL AFTER location"
        ];
        
        foreach ($migrations as $i => $sql) {
            try {
                $pdo->exec($sql);
                echo "<div style='color: green;'>✅ Step " . ($i + 1) . ": Added field successfully</div>";
            } catch (Exception $e) {
                echo "<div style='color: orange;'>⚠️ Step " . ($i + 1) . ": " . $e->getMessage() . "</div>";
            }
        }
        
        // Update admin user with sample data
        try {
            $pdo->exec("UPDATE users SET phone = '+1 (555) 123-0001', department = 'Administration', location = 'San Francisco, CA', timezone = 'PST (UTC-8)' WHERE email = 'admin@techvent.com'");
            echo "<div style='color: green;'>✅ Admin profile updated with sample data</div>";
        } catch (Exception $e) {
            echo "<div style='color: orange;'>⚠️ Admin update: " . $e->getMessage() . "</div>";
        }
        
        echo "<div style='color: blue; font-weight: bold; margin-top: 20px;'>🎉 Migration completed successfully!</div>";
    }
    
    // Show final table structure
    echo "<h3>Current Table Structure:</h3>";
    $result = $pdo->query("DESCRIBE users");
    $columns = $result->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<br><hr><br>";
echo "<a href='user-profile.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Profile Page</a> ";
echo "<a href='admin-dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Admin Dashboard</a>";
echo "<br><br><em>You can safely delete this file (simple-migrate.php) after migration is complete.</em>";
?>