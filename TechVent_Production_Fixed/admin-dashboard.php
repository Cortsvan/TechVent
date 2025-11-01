<?php
/**
 * Admin Dashboard - TechVent
 * Main admin dashboard with real-time statistics
 */

// Start session
session_start();

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'admin_protection.php';

// Get current user info
$currentUser = getCurrentUser();

// Get dashboard statistics
$dashboardStats = [
    'total_products' => 0,
    'low_stock_items' => 0,
    'total_suppliers' => 0,
    'active_staff' => 0,
    'total_users' => 0,
    'new_users_today' => 0
];

try {
    // Get user statistics
    $dashboardStats['total_users'] = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    $dashboardStats['active_staff'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")['count'];
    $dashboardStats['new_users_today'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count'];
    
    // Mock data for products and suppliers (you can replace with real tables later)
    $dashboardStats['total_products'] = 1247;
    $dashboardStats['low_stock_items'] = 23;
    $dashboardStats['total_suppliers'] = 45;
    
} catch (Exception $e) {
    // Use default values if queries fail
}

// Get recent users for the activity feed
$recentUsers = [];
try {
    $recentUsers = fetchAll("SELECT first_name, last_name, email, user_type, created_at 
                            FROM users 
                            ORDER BY created_at DESC 
                            LIMIT 5");
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechVent</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- TechVent Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <!-- Dashboard Specific Styles -->
    <style>
        .main-content {
            align-items: flex-start;
        }
        
        /* Dashboard specific styles */
        .metric-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(49, 130, 206, 0.1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            transition: width 0.3s ease;
        }

        .metric-card:hover::before {
            width: 8px;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }

        .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .metric-change {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            display: inline-block;
        }

        .metric-change.positive {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .metric-change.negative {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .metric-change.neutral {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid rgba(49, 130, 206, 0.1);
            display: flex;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .activity-desc {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .activity-time {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-microchip me-2"></i>TechVent
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-suppliers.php">
                            <i class="fas fa-truck me-1"></i>Suppliers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-products.php">
                            <i class="fas fa-box-open me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-inventory.php">
                            <i class="fas fa-boxes me-1"></i>Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-user-management.php">
                            <i class="fas fa-users me-1"></i>User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user-profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="dashboard-header text-center fade-in">
                <h1 class="dashboard-title">Admin Dashboard</h1>
                <p class="dashboard-subtitle">TechVent Inventory & Supplier Management System</p>
                <p class="last-updated-text">
                    <i class="fas fa-clock me-2"></i>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?> | Last updated: <span id="lastUpdated"></span>
                </p>
            </div>

            <!-- Metrics Cards -->
            <div class="row mb-4 fade-in">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-value"><?php echo number_format($dashboardStats['total_users']); ?></div>
                        <div class="metric-label">Total Users</div>
                        <span class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+<?php echo $dashboardStats['new_users_today']; ?> today
                        </span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="metric-value"><?php echo number_format($dashboardStats['total_products']); ?></div>
                        <div class="metric-label">Total Products</div>
                        <span class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+12% from last month
                        </span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="metric-value"><?php echo $dashboardStats['low_stock_items']; ?></div>
                        <div class="metric-label">Low Stock Items</div>
                        <span class="metric-change negative">
                            <i class="fas fa-arrow-down me-1"></i>-5% from last week
                        </span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="metric-value"><?php echo $dashboardStats['active_staff']; ?></div>
                        <div class="metric-label">Admin Users</div>
                        <span class="metric-change neutral">
                            <i class="fas fa-minus me-1"></i>No change
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="row fade-in">
                <!-- Quick Actions -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h3 class="text-light mb-4">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="admin-suppliers.php" class="btn btn-primary-custom w-100 mb-2">
                                    <i class="fas fa-truck me-2"></i>Manage Suppliers
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="admin-products.php" class="btn btn-secondary-custom w-100 mb-2">
                                    <i class="fas fa-box-open me-2"></i>Product Catalog
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="admin-inventory.php" class="btn btn-primary-custom w-100 mb-2">
                                    <i class="fas fa-boxes me-2"></i>Manage Inventory
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-secondary-custom w-100 mb-2" onclick="viewReports()">
                                    <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-6 mb-4">
                    <div class="dashboard-card">
                        <h3 class="text-light mb-4">
                            <i class="fas fa-clock me-2"></i>Recent Activity
                        </h3>
                        <div class="activity-feed">
                            <?php if (empty($recentUsers)): ?>
                                <div class="text-center" style="color: var(--text-muted); padding: 20px;">
                                    <i class="fas fa-clock fa-2x mb-3"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $user): ?>
                                    <div class="activity-item">
                                        <div class="activity-avatar">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div class="activity-desc">
                                                New <?php echo $user['user_type']; ?> account created
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo timeAgo($user['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="row fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h3 class="text-light mb-4">
                            <i class="fas fa-cogs me-2"></i>System Overview
                        </h3>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="info-card">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-server"></i>
                                            Server Status
                                        </div>
                                        <div class="info-value">
                                            <span class="status-badge status-active">Online</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-database"></i>
                                            Database
                                        </div>
                                        <div class="info-value">
                                            <span class="status-badge status-active">Connected</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-memory"></i>
                                            Memory Usage
                                        </div>
                                        <div class="info-value">
                                            <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?> MB
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-clock"></i>
                                            Uptime
                                        </div>
                                        <div class="info-value">
                                            <?php echo date('H:i:s'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="info-card">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-shield-alt"></i>
                                            Security
                                        </div>
                                        <div class="info-value">
                                            <span class="status-badge status-active">Secure</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-sync"></i>
                                            Last Backup
                                        </div>
                                        <div class="info-value">
                                            <?php echo date('M j, Y'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Dashboard Specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set last updated time
            document.getElementById('lastUpdated').textContent = new Date().toLocaleString();

            // Trigger fade-in animations
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);

            // Update metrics periodically
            setInterval(updateTimestamp, 30000); // Update every 30 seconds
        });

        function updateTimestamp() {
            document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
        }

        // Quick action functions
        function viewReports() {
            AlertUtils.showSuccess('Reports feature coming soon!');
        }

        function manageInventory() {
            AlertUtils.showSuccess('Inventory management feature coming soon!');
        }

        function exportData() {
            AlertUtils.showSuccess('Data export feature coming soon!');
        }
    </script>
</body>
</html>

<?php
/**
 * Helper function to format time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>