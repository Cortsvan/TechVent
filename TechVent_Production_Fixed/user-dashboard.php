<?php
/**
 * User Dashboard - TechVent
 * Main user dashboard with inventory access
 */

// Start session
session_start();

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'user_protection.php';

// Get current user info
$currentUser = getCurrentUser();

// Get dashboard statistics for users
$dashboardStats = [
    'total_products' => 0,
    'low_stock_items' => 0,
    'out_of_stock_items' => 0,
    'categories' => 0
];

try {
    // Get product and inventory statistics
    $dashboardStats['total_products'] = fetchOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'];
    $dashboardStats['low_stock_items'] = fetchOne("
        SELECT COUNT(*) as count 
        FROM inventory i 
        JOIN products p ON i.product_id = p.id 
        WHERE i.quantity_available <= i.reorder_level AND i.quantity_available > 0
    ")['count'];
    $dashboardStats['out_of_stock_items'] = fetchOne("
        SELECT COUNT(*) as count 
        FROM inventory i 
        JOIN products p ON i.product_id = p.id 
        WHERE i.quantity_available <= 0
    ")['count'];
    $dashboardStats['categories'] = fetchOne("SELECT COUNT(DISTINCT category) as count FROM products WHERE category IS NOT NULL AND category != ''")['count'];
    
} catch (Exception $e) {
    // Use default values if queries fail
}

// Get recent inventory activities
$recentActivities = [];
try {
    $recentActivities = fetchAll("
        SELECT p.name as product_name, s.name as supplier_name, 
               it.transaction_type, it.quantity, it.created_at,
               u.first_name, u.last_name
        FROM inventory_transactions it
        JOIN inventory i ON it.inventory_id = i.id
        JOIN products p ON i.product_id = p.id
        JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN users u ON it.user_id = u.id
        ORDER BY it.created_at DESC 
        LIMIT 8
    ");
} catch (Exception $e) {
    // Handle error silently
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - TechVent</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-cube me-2"></i>TechVent
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="user-dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
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
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">User Dashboard</h1>
                            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="admin-products.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Products
                            </a>
                            <a href="admin-inventory.php" class="btn btn-outline-primary">
                                <i class="fas fa-boxes me-2"></i>Manage Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Total Products</h6>
                                    <h3 class="mb-0"><?php echo $dashboardStats['total_products']; ?></h3>
                                </div>
                                <div class="stats-icon bg-primary">
                                    <i class="fas fa-boxes"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Categories</h6>
                                    <h3 class="mb-0"><?php echo $dashboardStats['categories']; ?></h3>
                                </div>
                                <div class="stats-icon bg-success">
                                    <i class="fas fa-tags"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Low Stock</h6>
                                    <h3 class="mb-0"><?php echo $dashboardStats['low_stock_items']; ?></h3>
                                </div>
                                <div class="stats-icon bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Out of Stock</h6>
                                    <h3 class="mb-0"><?php echo $dashboardStats['out_of_stock_items']; ?></h3>
                                </div>
                                <div class="stats-icon bg-danger">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Inventory Activity</h5>
                            <a href="admin-inventory.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-history fa-3x mb-3 d-block"></i>
                                    No recent inventory activity found.
                                </div>
                            <?php else: ?>
                                <div class="activity-timeline">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="activity-item d-flex align-items-center mb-3">
                                            <div class="activity-icon me-3">
                                                <?php
                                                switch ($activity['transaction_type']) {
                                                    case 'stock_in':
                                                        echo '<i class="fas fa-plus text-success"></i>';
                                                        break;
                                                    case 'stock_out':
                                                        echo '<i class="fas fa-minus text-danger"></i>';
                                                        break;
                                                    case 'adjustment':
                                                        echo '<i class="fas fa-edit text-primary"></i>';
                                                        break;
                                                    default:
                                                        echo '<i class="fas fa-exchange-alt text-info"></i>';
                                                }
                                                ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($activity['product_name']); ?></div>
                                                <small class="text-muted">
                                                    <?php
                                                    $action = '';
                                                    switch ($activity['transaction_type']) {
                                                        case 'stock_in':
                                                            $action = 'Added ' . abs($activity['quantity']) . ' units';
                                                            break;
                                                        case 'stock_out':
                                                            $action = 'Removed ' . abs($activity['quantity']) . ' units';
                                                            break;
                                                        case 'adjustment':
                                                            $action = 'Adjusted by ' . $activity['quantity'] . ' units';
                                                            break;
                                                    }
                                                    echo $action;
                                                    
                                                    if ($activity['first_name']) {
                                                        echo ' by ' . htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']);
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="admin-products.php" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-2"></i>Browse Products
                                </a>
                                <a href="admin-inventory.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-boxes me-2"></i>View Inventory
                                </a>
                                <a href="admin-inventory.php?stock_status=low_stock" class="btn btn-outline-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Items
                                </a>
                                <a href="admin-inventory.php?stock_status=out_of_stock" class="btn btn-outline-danger">
                                    <i class="fas fa-times-circle me-2"></i>Out of Stock
                                </a>
                                <hr>
                                <a href="user-profile.php" class="btn btn-outline-info">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- External JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh dashboard data every 5 minutes
            setInterval(function() {
                // You can implement auto-refresh logic here if needed
                console.log('Dashboard data refresh...');
            }, 300000); // 5 minutes
            
            // Add smooth animations
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>