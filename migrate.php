<?php
/**
 * Database Migration Runner - TechVent
 * Simple tool to run database migrations
 * 
 * SECURITY WARNING: Remove this file after running migrations in production!
 */

session_start();
require_once 'config/db.php';
require_once 'includes/session.php';

// Check if user is admin (basic security)
if (!isAdmin()) {
    die('<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; font-family: Arial;">
        <h3>Access Denied</h3>
        <p>Admin privileges required to run database migrations.</p>
        <p><strong>Current session:</strong></p>
        <ul>
            <li>Logged in: ' . (isLoggedIn() ? 'Yes' : 'No') . '</li>
            <li>User type: ' . ($_SESSION['user_type'] ?? 'Not set') . '</li>
            <li>User ID: ' . ($_SESSION['user_id'] ?? 'Not set') . '</li>
        </ul>
        <p><a href="login.php">Login as admin</a> | <a href="admin-dashboard.php">Admin Dashboard</a></p>
    </div>');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        global $pdo;
        
        // Check if fields already exist
        try {
            $testQuery = $pdo->prepare("SELECT phone, department, location, timezone FROM users LIMIT 1");
            $testQuery->execute();
            $message = "Migration already completed! Extended profile fields are available.";
        } catch (Exception $e) {
            // Fields don't exist, run migration
            $migrations = [
                "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password",
                "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER phone", 
                "ALTER TABLE users ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER department",
                "ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT NULL AFTER location",
                "UPDATE users SET phone = '+1 (555) 123-0001', department = 'Administration', location = 'San Francisco, CA', timezone = 'PST (UTC-8)' WHERE email = 'admin@techvent.com'"
            ];
            
            foreach ($migrations as $sql) {
                $pdo->exec($sql);
            }
            
            $message = "Migration completed successfully! Extended profile fields are now available.";
        }
        
    } catch (Exception $e) {
        $error = "Migration failed: " . $e->getMessage();
    }
}

// Check current table structure
$tableInfo = [];
try {
    global $pdo;
    $result = $pdo->query("DESCRIBE users");
    $tableInfo = $result->fetchAll();
} catch (Exception $e) {
    $error = "Could not retrieve table information: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - TechVent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-database me-2"></i>Database Migration</h2>
                        <p class="text-muted">Run database migrations to add extended profile fields</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>Current Users Table Structure:</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Key</th>
                                        <th>Default</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableInfo as $column): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($column['Field']); ?></code></td>
                                        <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                        <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="text-center">
                            <p class="mb-3">
                                <strong>Migration will add these fields:</strong><br>
                                <code>phone</code>, <code>department</code>, <code>location</code>, <code>timezone</code>
                            </p>
                            <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                <i class="fas fa-play me-2"></i>Run Migration
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="admin-dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <a href="user-profile.php" class="btn btn-info">
                            <i class="fas fa-user me-2"></i>Test Profile Page
                        </a>
                    </div>

                    <div class="mt-4">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Security Notice</h6>
                            <p class="mb-0">Remember to delete this migration tool (<code>migrate.php</code>) after running migrations in production environments!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>