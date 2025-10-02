<?php
/**
 * System Test Page
 * Use this to verify your TechVent authentication system is working properly
 * Access this at: http://localhost/TechVent/test.php
 */

// Include database connection
require_once 'config/db.php';
require_once 'includes/session.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - TechVent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .test-card { margin-bottom: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">TechVent System Test</h1>
        
        <!-- Database Connection Test -->
        <div class="card test-card">
            <div class="card-header">
                <h5>Database Connection Test</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->query("SELECT VERSION() as version");
                    $version = $stmt->fetch();
                    echo "<p class='success'><strong>✓ Database Connected Successfully!</strong></p>";
                    echo "<p class='info'>MySQL Version: " . htmlspecialchars($version['version']) . "</p>";
                } catch (Exception $e) {
                    echo "<p class='error'><strong>✗ Database Connection Failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>

        <!-- Database Tables Test -->
        <div class="card test-card">
            <div class="card-header">
                <h5>Database Tables Test</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->query("SHOW TABLES FROM techvent");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('users', $tables)) {
                        echo "<p class='success'><strong>✓ Users table exists!</strong></p>";
                        
                        // Check table structure
                        $stmt = $pdo->query("DESCRIBE users");
                        $columns = $stmt->fetchAll();
                        echo "<p class='info'>Table columns: ";
                        foreach ($columns as $column) {
                            echo htmlspecialchars($column['Field']) . " ";
                        }
                        echo "</p>";
                        
                        // Count users
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                        $count = $stmt->fetch();
                        echo "<p class='info'>Total users in database: " . $count['count'] . "</p>";
                        
                    } else {
                        echo "<p class='error'><strong>✗ Users table not found!</strong></p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'><strong>✗ Error checking tables:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>

        <!-- Admin User Test -->
        <div class="card test-card">
            <div class="card-header">
                <h5>Default Admin User Test</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'admin'");
                    $stmt->execute(['admin@techvent.com']);
                    $admin = $stmt->fetch();
                    
                    if ($admin) {
                        echo "<p class='success'><strong>✓ Default admin user exists!</strong></p>";
                        echo "<p class='info'>Admin Email: " . htmlspecialchars($admin['email']) . "</p>";
                        echo "<p class='info'>Admin Name: " . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</p>";
                        echo "<p class='info'>User Type: " . htmlspecialchars($admin['user_type']) . "</p>";
                    } else {
                        echo "<p class='error'><strong>✗ Default admin user not found!</strong></p>";
                        echo "<p class='info'>Run the database_setup.sql script to create the admin user.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'><strong>✗ Error checking admin user:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>

        <!-- Session Test -->
        <div class="card test-card">
            <div class="card-header">
                <h5>Session Status</h5>
            </div>
            <div class="card-body">
                <?php
                if (isLoggedIn()) {
                    $user = getCurrentUser();
                    echo "<p class='success'><strong>✓ User is logged in!</strong></p>";
                    echo "<p class='info'>User: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</p>";
                    echo "<p class='info'>User Type: " . htmlspecialchars($user['type']) . "</p>";
                    echo "<p><a href='logout.php' class='btn btn-warning'>Logout</a></p>";
                } else {
                    echo "<p class='info'><strong>ℹ No user logged in</strong></p>";
                    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login</a></p>";
                }
                ?>
            </div>
        </div>

        <!-- File Structure Test -->
        <div class="card test-card">
            <div class="card-header">
                <h5>File Structure Test</h5>
            </div>
            <div class="card-body">
                <?php
                $requiredFiles = [
                    'config/db.php',
                    'includes/session.php', 
                    'login.php',
                    'register.php',
                    'logout.php',
                    'admin-dashboard.php',
                    'user-dashboard.php'
                ];

                $allFilesExist = true;
                foreach ($requiredFiles as $file) {
                    if (file_exists($file)) {
                        echo "<p class='success'>✓ " . htmlspecialchars($file) . "</p>";
                    } else {
                        echo "<p class='error'>✗ " . htmlspecialchars($file) . " (missing)</p>";
                        $allFilesExist = false;
                    }
                }

                if ($allFilesExist) {
                    echo "<p class='success'><strong>✓ All required files are present!</strong></p>";
                } else {
                    echo "<p class='error'><strong>✗ Some required files are missing!</strong></p>";
                }
                ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card test-card">
            <div class="card-header">
                <h5>Quick Navigation</h5>
            </div>
            <div class="card-body">
                <a href="login.php" class="btn btn-primary me-2">Login Page</a>
                <a href="register.php" class="btn btn-success me-2">Register Page</a>
                <a href="admin-dashboard.php" class="btn btn-warning me-2">Admin Dashboard</a>
                <a href="user-dashboard.php" class="btn btn-info me-2">User Dashboard</a>
                <a href="index.html" class="btn btn-secondary">Home Page</a>
            </div>
        </div>

        <div class="alert alert-info">
            <h6>Default Login Credentials:</h6>
            <p><strong>Admin:</strong> admin@techvent.com / admin123</p>
            <p><strong>Regular User:</strong> Create an account through the registration form</p>
        </div>
    </div>
</body>
</html>