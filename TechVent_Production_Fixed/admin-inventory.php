<?php
/**
 * Inventory Management - TechVent
 * Manage stock levels and inventory transactions (Admin & User Access)
 */

// Start session
session_start();

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'user_protection.php'; // Both admin and users can access

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin();

// Get filter parameters
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$stock_status = isset($_GET['stock_status']) ? trim($_GET['stock_status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_stock':
                $inventory_id = $_POST['inventory_id'];
                $new_quantity = intval($_POST['quantity']);
                $transaction_type = $_POST['transaction_type'];
                $notes = trim($_POST['notes']);
                
                // Get current stock
                $current = fetchOne("SELECT quantity_in_stock, product_id FROM inventory WHERE id = ?", [$inventory_id]);
                if (!$current) {
                    echo json_encode(['success' => false, 'message' => 'Inventory record not found']);
                    break;
                }
                
                $quantity_change = 0;
                
                // Calculate quantity change based on transaction type
                if ($transaction_type === 'stock_in') {
                    $final_quantity = $current['quantity_in_stock'] + $new_quantity;
                    $quantity_change = $new_quantity;
                } elseif ($transaction_type === 'stock_out') {
                    if ($new_quantity > $current['quantity_in_stock']) {
                        echo json_encode(['success' => false, 'message' => 'Cannot remove more stock than available']);
                        break;
                    }
                    $final_quantity = $current['quantity_in_stock'] - $new_quantity;
                    $quantity_change = -$new_quantity;
                } elseif ($transaction_type === 'adjustment') {
                    $final_quantity = $new_quantity;
                    $quantity_change = $new_quantity - $current['quantity_in_stock'];
                }
                
                // Update inventory
                $updateSql = "UPDATE inventory SET quantity_in_stock = ?, last_restocked = NOW() WHERE id = ?";
                executeQuery($updateSql, [$final_quantity, $inventory_id]);
                
                // Log transaction
                $transSql = "INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, notes, user_id, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                executeQuery($transSql, [$inventory_id, $transaction_type, $quantity_change, $notes, $currentUser['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
                break;
                
            case 'add_to_inventory':
                $product_id = $_POST['product_id'];
                $quantity = intval($_POST['quantity']);
                $reorder_level = intval($_POST['reorder_level']);
                $reorder_quantity = intval($_POST['reorder_quantity']);
                $location = trim($_POST['location']);
                $cost_price = floatval($_POST['cost_price']);
                $selling_price = floatval($_POST['selling_price']);
                $notes = trim($_POST['notes']);
                
                // Check if product already has inventory
                $existing = fetchOne("SELECT id FROM inventory WHERE product_id = ?", [$product_id]);
                if ($existing) {
                    echo json_encode(['success' => false, 'message' => 'Product already has inventory record']);
                    break;
                }
                
                $sql = "INSERT INTO inventory (product_id, quantity_in_stock, reorder_level, reorder_quantity, location, cost_price, selling_price, last_restocked, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $params = [$product_id, $quantity, $reorder_level, $reorder_quantity, $location, $cost_price, $selling_price];
                
                executeQuery($sql, $params);
                
                // Get the new inventory ID
                $inventory_id = $pdo->lastInsertId();
                
                // Log initial stock transaction
                $transSql = "INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, notes, user_id, created_at) 
                            VALUES (?, 'stock_in', ?, ?, ?, NOW())";
                executeQuery($transSql, [$inventory_id, $quantity, $notes ?: 'Initial stock', $currentUser['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Product added to inventory successfully']);
                break;
                
            case 'update_inventory_settings':
                $inventory_id = $_POST['inventory_id'];
                $reorder_level = intval($_POST['reorder_level']);
                $reorder_quantity = intval($_POST['reorder_quantity']);
                $location = trim($_POST['location']);
                $cost_price = floatval($_POST['cost_price']);
                $selling_price = floatval($_POST['selling_price']);
                
                $sql = "UPDATE inventory SET reorder_level = ?, reorder_quantity = ?, location = ?, cost_price = ?, selling_price = ? WHERE id = ?";
                $params = [$reorder_level, $reorder_quantity, $location, $cost_price, $selling_price, $inventory_id];
                
                executeQuery($sql, $params);
                echo json_encode(['success' => true, 'message' => 'Inventory settings updated successfully']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Get categories for filter
try {
    $categories = fetchAll("SELECT DISTINCT p.category FROM products p WHERE p.category IS NOT NULL AND p.category != '' ORDER BY p.category ASC");
} catch (Exception $e) {
    $categories = [];
}

// Build inventory query with filters
$whereConditions = ["p.status IN ('active', 'inactive')"];
$params = [];

if ($product_id > 0) {
    $whereConditions[] = "p.id = ?";
    $params[] = $product_id;
}

if (!empty($category)) {
    $whereConditions[] = "p.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR s.name LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $whereConditions);

// Get inventory data with product and supplier info
try {
    $sql = "
        SELECT p.id as product_id, p.name as product_name, p.sku, p.category, p.unit_price,
               s.name as supplier_name,
               i.id as inventory_id, i.quantity_in_stock, i.quantity_reserved, i.quantity_available,
               i.reorder_level, i.reorder_quantity, i.location, i.cost_price, i.selling_price,
               i.last_restocked,
               CASE 
                   WHEN i.id IS NULL THEN 'not_managed'
                   WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                   WHEN i.quantity_available <= i.reorder_level THEN 'low_stock'
                   ELSE 'in_stock'
               END as stock_status,
               CASE 
                   WHEN i.selling_price > 0 AND i.cost_price > 0 THEN 
                       ((i.selling_price - i.cost_price) / i.cost_price * 100)
                   ELSE 0
               END as profit_margin
        FROM products p 
        JOIN suppliers s ON p.supplier_id = s.id 
        LEFT JOIN inventory i ON p.id = i.product_id 
        WHERE {$whereClause}
        ORDER BY 
            CASE 
                WHEN i.quantity_available <= 0 THEN 1
                WHEN i.quantity_available <= i.reorder_level THEN 2
                ELSE 3
            END,
            p.name ASC
    ";
    
    $inventory = fetchAll($sql, $params);
} catch (Exception $e) {
    $inventory = [];
}

// Filter by stock status if specified
if (!empty($stock_status)) {
    $inventory = array_filter($inventory, function($item) use ($stock_status) {
        return $item['stock_status'] === $stock_status;
    });
}

// Get products not in inventory for adding
try {
    $productsNotInInventory = fetchAll("
        SELECT p.id, p.name, p.sku, s.name as supplier_name 
        FROM products p 
        JOIN suppliers s ON p.supplier_id = s.id 
        LEFT JOIN inventory i ON p.id = i.product_id 
        WHERE i.id IS NULL AND p.status = 'active'
        ORDER BY p.name ASC
    ");
} catch (Exception $e) {
    $productsNotInInventory = [];
}

// Calculate summary statistics
$stats = [
    'total_products' => count($inventory),
    'in_stock' => count(array_filter($inventory, fn($item) => $item['stock_status'] === 'in_stock')),
    'low_stock' => count(array_filter($inventory, fn($item) => $item['stock_status'] === 'low_stock')),
    'out_of_stock' => count(array_filter($inventory, fn($item) => $item['stock_status'] === 'out_of_stock')),
    'not_managed' => count(array_filter($inventory, fn($item) => $item['stock_status'] === 'not_managed')),
    'total_value' => array_sum(array_map(fn($item) => ($item['quantity_in_stock'] ?: 0) * ($item['cost_price'] ?: 0), $inventory))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - TechVent</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- TechVent Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/main.css">
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
                        <a class="nav-link" href="<?php echo $isAdmin ? 'admin-dashboard.php' : 'user-dashboard.php'; ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-suppliers.php">
                            <i class="fas fa-truck me-1"></i>Suppliers
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-products.php">
                            <i class="fas fa-box-open me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-inventory.php">
                            <i class="fas fa-boxes me-1"></i>Inventory
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-user-management.php">
                            <i class="fas fa-users me-1"></i>User Management
                        </a>
                    </li>
                    <?php endif; ?>
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
            <!-- Page Header -->
            <div class="dashboard-header text-center fade-in">
                <h1 class="dashboard-title">Inventory Management</h1>
                <p class="dashboard-subtitle">Monitor and manage your stock levels</p>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4 fade-in">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, #22c55e, #16a34a);">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="metric-value"><?php echo $stats['in_stock']; ?></div>
                        <div class="metric-label">In Stock</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, #f59e0b, #d97706);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="metric-value"><?php echo $stats['low_stock']; ?></div>
                        <div class="metric-label">Low Stock</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, #ef4444, #dc2626);">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="metric-value"><?php echo $stats['out_of_stock']; ?></div>
                        <div class="metric-label">Out of Stock</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, #6b7280, #4b5563);">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="metric-value"><?php echo $stats['not_managed']; ?></div>
                        <div class="metric-label">Not Managed</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, #8b5cf6, #7c3aed);">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="metric-value"><?php echo $stats['total_products']; ?></div>
                        <div class="metric-label">Total Items</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="metric-card">
                        <div class="metric-icon" style="background: linear-gradient(45deg, var(--cyan), var(--light-cyan));">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="metric-value">$<?php echo number_format($stats['total_value'], 0); ?></div>
                        <div class="metric-label">Total Value</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="row mb-4 fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="category_filter" class="form-label text-light">
                                    <i class="fas fa-tags me-1"></i>Category
                                </label>
                                <select class="form-select form-select-custom" id="category_filter" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="stock_status_filter" class="form-label text-light">
                                    <i class="fas fa-chart-line me-1"></i>Stock Status
                                </label>
                                <select class="form-select form-select-custom" id="stock_status_filter" name="stock_status">
                                    <option value="">All Status</option>
                                    <option value="in_stock" <?php echo $stock_status === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                    <option value="low_stock" <?php echo $stock_status === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out_of_stock" <?php echo $stock_status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    <option value="not_managed" <?php echo $stock_status === 'not_managed' ? 'selected' : ''; ?>>Not Managed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search_filter" class="form-label text-light">
                                    <i class="fas fa-search me-1"></i>Search
                                </label>
                                <input type="text" class="form-control" id="search_filter" name="search" 
                                       placeholder="Search products or suppliers..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="admin-inventory.php" class="btn btn-secondary-custom">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                        
                        <?php if (!empty($productsNotInInventory)): ?>
                        <div class="mt-3 pt-3 border-top" style="border-color: rgba(49, 130, 206, 0.2) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-light">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    <?php echo count($productsNotInInventory); ?> products not in inventory
                                </span>
                                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addToInventoryModal">
                                    <i class="fas fa-plus me-2"></i>Add Product to Inventory
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="row fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="table-responsive">
                            <table class="table table-custom" id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Supplier</th>
                                        <th>Stock Level</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Pricing</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inventory)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-boxes fa-3x mb-3" style="color: var(--text-muted);"></i>
                                                <p style="color: var(--text-muted);">No inventory records found.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($inventory as $item): ?>
                                            <tr class="inventory-row status-<?php echo $item['stock_status']; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="product-icon me-3">
                                                            <i class="fas fa-box"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-light"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                            <small class="text-muted">
                                                                <?php if ($item['sku']): ?>
                                                                    SKU: <?php echo htmlspecialchars($item['sku']); ?>
                                                                <?php endif; ?>
                                                                <?php if ($item['category']): ?>
                                                                    | <?php echo htmlspecialchars($item['category']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-light"><?php echo htmlspecialchars($item['supplier_name']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($item['inventory_id']): ?>
                                                        <div class="stock-display">
                                                            <div class="stock-main">
                                                                <span class="stock-number"><?php echo $item['quantity_available']; ?></span>
                                                                <span class="stock-label">available</span>
                                                            </div>
                                                            <div class="stock-details">
                                                                <small class="text-muted">
                                                                    Total: <?php echo $item['quantity_in_stock']; ?>
                                                                    <?php if ($item['quantity_reserved'] > 0): ?>
                                                                        | Reserved: <?php echo $item['quantity_reserved']; ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                            <div class="reorder-info">
                                                                <small class="text-muted">
                                                                    Reorder at: <?php echo $item['reorder_level']; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not Managed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusConfig = [
                                                        'in_stock' => ['badge-success', 'fas fa-check-circle', 'In Stock'],
                                                        'low_stock' => ['badge-warning', 'fas fa-exclamation-triangle', 'Low Stock'],
                                                        'out_of_stock' => ['badge-danger', 'fas fa-times-circle', 'Out of Stock'],
                                                        'not_managed' => ['badge-secondary', 'fas fa-question-circle', 'Not Managed']
                                                    ];
                                                    $config = $statusConfig[$item['stock_status']];
                                                    ?>
                                                    <span class="badge <?php echo $config[0]; ?>">
                                                        <i class="<?php echo $config[1]; ?> me-1"></i>
                                                        <?php echo $config[2]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-light">
                                                        <?php echo $item['location'] ? htmlspecialchars($item['location']) : '-'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($item['cost_price'] && $item['selling_price']): ?>
                                                        <div class="pricing-info">
                                                            <div class="text-light">
                                                                Cost: $<?php echo number_format($item['cost_price'], 2); ?>
                                                            </div>
                                                            <div class="text-light">
                                                                Sell: $<?php echo number_format($item['selling_price'], 2); ?>
                                                            </div>
                                                            <small class="text-success">
                                                                Margin: <?php echo number_format($item['profit_margin'], 1); ?>%
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($item['last_restocked']): ?>
                                                        <span class="text-light">
                                                            <?php echo date('M j, Y', strtotime($item['last_restocked'])); ?>
                                                        </span>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('g:i A', strtotime($item['last_restocked'])); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical" role="group">
                                                        <?php if ($item['inventory_id']): ?>
                                                            <button class="btn btn-primary-custom btn-sm mb-1" 
                                                                    onclick="showStockModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                                <i class="fas fa-edit me-1"></i>Update Stock
                                                            </button>
                                                            <button class="btn btn-secondary-custom btn-sm" 
                                                                    onclick="showSettingsModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                                <i class="fas fa-cog me-1"></i>Settings
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-primary-custom btn-sm" 
                                                                    onclick="showAddToInventoryModal(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>')">
                                                                <i class="fas fa-plus me-1"></i>Add to Inventory
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Stock Update Modal -->
    <div class="modal fade modal-custom" id="stockUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="stockUpdateForm">
                    <input type="hidden" id="stockInventoryId" name="inventory_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 id="stockProductName" class="text-light"></h6>
                            <p id="stockCurrentLevel" class="text-muted mb-3"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transactionType" class="form-label">Transaction Type *</label>
                            <select class="form-select" id="transactionType" name="transaction_type" required>
                                <option value="stock_in">Stock In (Add inventory)</option>
                                <option value="stock_out">Stock Out (Remove inventory)</option>
                                <option value="adjustment">Adjustment (Set exact quantity)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stockQuantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="stockQuantity" name="quantity" min="0" required>
                            <div class="form-text" id="quantityHelp"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stockNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="stockNotes" name="notes" rows="3" 
                                      placeholder="Optional notes about this stock change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add to Inventory Modal -->
    <div class="modal fade modal-custom" id="addToInventoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product to Inventory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addToInventoryForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="inventoryProduct" class="form-label">Product *</label>
                                <select class="form-select" id="inventoryProduct" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($productsNotInInventory as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['supplier_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="initialQuantity" class="form-label">Initial Quantity *</label>
                                <input type="number" class="form-control" id="initialQuantity" name="quantity" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reorderLevel" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorderLevel" name="reorder_level" min="0" value="10">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reorderQuantity" class="form-label">Reorder Quantity</label>
                                <input type="number" class="form-control" id="reorderQuantity" name="reorder_quantity" min="1" value="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="inventoryLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="inventoryLocation" name="location" placeholder="e.g., A-001">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="costPrice" class="form-label">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="costPrice" name="cost_price" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sellingPrice" class="form-label">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="sellingPrice" name="selling_price" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="initialNotes" class="form-label">Initial Stock Notes</label>
                                <textarea class="form-control" id="initialNotes" name="notes" rows="2" 
                                          placeholder="Notes about the initial stock..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Add to Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Inventory Settings Modal -->
    <div class="modal fade modal-custom" id="inventorySettingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inventory Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="inventorySettingsForm">
                    <input type="hidden" id="settingsInventoryId" name="inventory_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 id="settingsProductName" class="text-light"></h6>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="settingsReorderLevel" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="settingsReorderLevel" name="reorder_level" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="settingsReorderQuantity" class="form-label">Reorder Quantity</label>
                                <input type="number" class="form-control" id="settingsReorderQuantity" name="reorder_quantity" min="1">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="settingsLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="settingsLocation" name="location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="settingsCostPrice" class="form-label">Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="settingsCostPrice" name="cost_price" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="settingsSellingPrice" class="form-label">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="settingsSellingPrice" name="selling_price" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Update Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Inventory JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeInventoryForms();
            
            // Trigger fade-in animations
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);
            
            // Update quantity help text based on transaction type
            const transactionType = document.getElementById('transactionType');
            const quantityHelp = document.getElementById('quantityHelp');
            
            if (transactionType && quantityHelp) {
                transactionType.addEventListener('change', function() {
                    switch (this.value) {
                        case 'stock_in':
                            quantityHelp.textContent = 'Enter the quantity to add to current stock';
                            break;
                        case 'stock_out':
                            quantityHelp.textContent = 'Enter the quantity to remove from current stock';
                            break;
                        case 'adjustment':
                            quantityHelp.textContent = 'Enter the exact quantity that should be in stock';
                            break;
                    }
                });
            }
        });

        function initializeInventoryForms() {
            // Stock update form
            const stockForm = document.getElementById('stockUpdateForm');
            if (stockForm) {
                stockForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update_stock');
                    
                    try {
                        const response = await fetch('admin-inventory.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('stockUpdateModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while updating stock');
                    }
                });
            }

            // Add to inventory form
            const addForm = document.getElementById('addToInventoryForm');
            if (addForm) {
                addForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add_to_inventory');
                    
                    try {
                        const response = await fetch('admin-inventory.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('addToInventoryModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while adding to inventory');
                    }
                });
            }

            // Settings form
            const settingsForm = document.getElementById('inventorySettingsForm');
            if (settingsForm) {
                settingsForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update_inventory_settings');
                    
                    try {
                        const response = await fetch('admin-inventory.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('inventorySettingsModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while updating settings');
                    }
                });
            }
        }

        function showStockModal(item) {
            document.getElementById('stockInventoryId').value = item.inventory_id;
            document.getElementById('stockProductName').textContent = item.product_name;
            document.getElementById('stockCurrentLevel').textContent = 
                `Current stock: ${item.quantity_available} available (${item.quantity_in_stock} total)`;
            
            // Reset form
            document.getElementById('stockUpdateForm').reset();
            document.getElementById('transactionType').value = 'stock_in';
            document.getElementById('quantityHelp').textContent = 'Enter the quantity to add to current stock';
            
            new bootstrap.Modal(document.getElementById('stockUpdateModal')).show();
        }

        function showSettingsModal(item) {
            document.getElementById('settingsInventoryId').value = item.inventory_id;
            document.getElementById('settingsProductName').textContent = item.product_name;
            document.getElementById('settingsReorderLevel').value = item.reorder_level;
            document.getElementById('settingsReorderQuantity').value = item.reorder_quantity;
            document.getElementById('settingsLocation').value = item.location || '';
            document.getElementById('settingsCostPrice').value = item.cost_price || '';
            document.getElementById('settingsSellingPrice').value = item.selling_price || '';
            
            new bootstrap.Modal(document.getElementById('inventorySettingsModal')).show();
        }

        function showAddToInventoryModal(productId, productName) {
            if (productId && productName) {
                document.getElementById('inventoryProduct').value = productId;
            }
            new bootstrap.Modal(document.getElementById('addToInventoryModal')).show();
        }
    </script>

    <!-- Inventory Specific Styles -->
    <style>
        .inventory-row.status-out_of_stock {
            background: rgba(239, 68, 68, 0.1) !important;
        }

        .inventory-row.status-low_stock {
            background: rgba(245, 158, 11, 0.1) !important;
        }

        .product-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stock-display {
            text-align: center;
        }

        .stock-main {
            margin-bottom: 5px;
        }

        .stock-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-light);
        }

        .stock-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-left: 5px;
        }

        .pricing-info {
            font-size: 0.9rem;
        }

        .metric-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(49, 130, 206, 0.1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 12px;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</body>
</html>