<?php
/**
 * Products Management - TechVent
 * View and manage supplier product catalogs (Admin & User Access)
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
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle AJAX requests (Admin only for modifications)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $isAdmin) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_product':
                $supplier_id = $_POST['supplier_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $sku = trim($_POST['sku']);
                $category = trim($_POST['category']);
                $brand = trim($_POST['brand']);
                $unit_price = floatval($_POST['unit_price']);
                $min_order_quantity = intval($_POST['min_order_quantity']);
                
                $sql = "INSERT INTO products (supplier_id, name, description, sku, category, brand, unit_price, min_order_quantity, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [$supplier_id, $name, $description, $sku, $category, $brand, $unit_price, $min_order_quantity];
                
                if (executeQuery($sql, $params)) {
                    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add product']);
                }
                break;
                
            case 'update_product':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $category = trim($_POST['category']);
                $brand = trim($_POST['brand']);
                $unit_price = floatval($_POST['unit_price']);
                $min_order_quantity = intval($_POST['min_order_quantity']);
                $status = $_POST['status'];
                
                $sql = "UPDATE products SET name = ?, description = ?, category = ?, brand = ?, unit_price = ?, min_order_quantity = ?, status = ? WHERE id = ?";
                $params = [$name, $description, $category, $brand, $unit_price, $min_order_quantity, $status, $id];
                
                if (executeQuery($sql, $params)) {
                    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update product']);
                }
                break;
                
            case 'delete_product':
                $id = $_POST['id'];
                
                // Check if product has inventory
                $inventoryCount = fetchOne("SELECT COUNT(*) as count FROM inventory WHERE product_id = ?", [$id])['count'];
                
                if ($inventoryCount > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete product with existing inventory']);
                } else {
                    if (executeQuery("DELETE FROM products WHERE id = ?", [$id])) {
                        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
                    }
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Get suppliers for filter dropdown
try {
    $suppliers = fetchAll("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name ASC");
} catch (Exception $e) {
    $suppliers = [];
}

// Get categories for filter
try {
    $categories = fetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
} catch (Exception $e) {
    $categories = [];
}

// Build products query with filters
$whereConditions = ["p.status IN ('active', 'inactive')"];
$params = [];

if ($supplier_id > 0) {
    $whereConditions[] = "p.supplier_id = ?";
    $params[] = $supplier_id;
}

if (!empty($category)) {
    $whereConditions[] = "p.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $whereConditions);

// Get products with supplier and inventory info
try {
    $sql = "
        SELECT p.*, s.name as supplier_name,
               COALESCE(i.quantity_in_stock, 0) as stock_quantity,
               COALESCE(i.quantity_available, 0) as available_quantity,
               COALESCE(i.reorder_level, 0) as reorder_level,
               CASE 
                   WHEN i.quantity_available <= i.reorder_level THEN 'low'
                   WHEN i.quantity_available = 0 THEN 'out'
                   ELSE 'good'
               END as stock_status
        FROM products p 
        JOIN suppliers s ON p.supplier_id = s.id 
        LEFT JOIN inventory i ON p.id = i.product_id 
        WHERE {$whereClause}
        ORDER BY p.name ASC
    ";
    
    $products = fetchAll($sql, $params);
} catch (Exception $e) {
    $products = [];
}

// Get current supplier name if filtered
$currentSupplier = null;
if ($supplier_id > 0) {
    try {
        $currentSupplier = fetchOne("SELECT name FROM suppliers WHERE id = ?", [$supplier_id]);
    } catch (Exception $e) {
        // Handle error silently
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog - TechVent</title>
    
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
                        <a class="nav-link active" href="admin-products.php">
                            <i class="fas fa-box-open me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-inventory.php">
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
                <h1 class="dashboard-title">Product Catalog</h1>
                <p class="dashboard-subtitle">
                    <?php if ($currentSupplier): ?>
                        Products from <?php echo htmlspecialchars($currentSupplier['name']); ?>
                    <?php else: ?>
                        Browse and manage all supplier products
                    <?php endif; ?>
                </p>
            </div>

            <!-- Filters and Actions -->
            <div class="row mb-4 fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="supplier_filter" class="form-label text-light">
                                    <i class="fas fa-truck me-1"></i>Supplier
                                </label>
                                <select class="form-select form-select-custom" id="supplier_filter" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                                <?php echo $supplier_id == $supplier['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                            <div class="col-md-4">
                                <label for="search_filter" class="form-label text-light">
                                    <i class="fas fa-search me-1"></i>Search
                                </label>
                                <input type="text" class="form-control" id="search_filter" name="search" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="admin-products.php" class="btn btn-secondary-custom">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($isAdmin): ?>
                        <div class="mt-3 pt-3 border-top" style="border-color: rgba(49, 130, 206, 0.2) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-light">
                                    <i class="fas fa-box-open me-2"></i>
                                    Total Products: <?php echo count($products); ?>
                                </span>
                                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="fas fa-plus me-2"></i>Add New Product
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row fade-in">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="dashboard-card text-center">
                            <i class="fas fa-box-open fa-4x mb-3" style="color: var(--text-muted);"></i>
                            <h4 style="color: var(--text-muted);">No Products Found</h4>
                            <p style="color: var(--text-muted);">
                                <?php if ($isAdmin): ?>
                                    Add your first product to get started with the inventory system.
                                <?php else: ?>
                                    No products match your current filters.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <i class="fas fa-box-open"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Stock Status Badge -->
                                    <div class="stock-badge stock-<?php echo $product['stock_status']; ?>">
                                        <?php
                                        switch ($product['stock_status']) {
                                            case 'good':
                                                echo '<i class="fas fa-check-circle me-1"></i>In Stock';
                                                break;
                                            case 'low':
                                                echo '<i class="fas fa-exclamation-triangle me-1"></i>Low Stock';
                                                break;
                                            case 'out':
                                                echo '<i class="fas fa-times-circle me-1"></i>Out of Stock';
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="product-content">
                                    <div class="product-header">
                                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <div class="product-sku">SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></div>
                                    </div>
                                    
                                    <div class="product-meta">
                                        <div class="product-supplier">
                                            <i class="fas fa-truck me-1"></i>
                                            <?php echo htmlspecialchars($product['supplier_name']); ?>
                                        </div>
                                        <div class="product-category">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($product['category'] ?: 'Uncategorized'); ?>
                                        </div>
                                        <?php if ($product['brand']): ?>
                                        <div class="product-brand">
                                            <i class="fas fa-trademark me-1"></i>
                                            <?php echo htmlspecialchars($product['brand']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($product['description']): ?>
                                    <div class="product-description">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-pricing">
                                        <div class="product-price">$<?php echo number_format($product['unit_price'], 2); ?></div>
                                        <div class="product-moq">Min Order: <?php echo $product['min_order_quantity']; ?></div>
                                    </div>
                                    
                                    <div class="product-stock">
                                        <div class="stock-info">
                                            <span class="stock-label">Available:</span>
                                            <span class="stock-value"><?php echo $product['available_quantity']; ?></span>
                                        </div>
                                        <div class="stock-info">
                                            <span class="stock-label">Total Stock:</span>
                                            <span class="stock-value"><?php echo $product['stock_quantity']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="admin-inventory.php?product_id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary-custom btn-sm">
                                            <i class="fas fa-boxes me-1"></i>Manage Stock
                                        </a>
                                        
                                        <?php if ($isAdmin): ?>
                                        <button class="btn btn-secondary-custom btn-sm" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if ($isAdmin): ?>
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProductForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="productSupplier" class="form-label">Supplier *</label>
                                <select class="form-select" id="productSupplier" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>" 
                                                <?php echo $supplier_id == $supplier['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productName" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="productName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productSku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="productSku" name="sku">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="productCategory" name="category">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productBrand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="productBrand" name="brand">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productPrice" class="form-label">Unit Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="productPrice" name="unit_price" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productMoq" class="form-label">Min Order Quantity</label>
                                <input type="number" class="form-control" id="productMoq" name="min_order_quantity" 
                                       min="1" value="1">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="productDescription" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProductForm">
                    <input type="hidden" id="editProductId" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProductName" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="editProductName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProductCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="editProductCategory" name="category">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProductBrand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="editProductBrand" name="brand">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProductPrice" class="form-label">Unit Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="editProductPrice" name="unit_price" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProductMoq" class="form-label">Min Order Quantity</label>
                                <input type="number" class="form-control" id="editProductMoq" name="min_order_quantity" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProductStatus" class="form-label">Status</label>
                                <select class="form-select" id="editProductStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="discontinued">Discontinued</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editProductDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editProductDescription" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Products JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($isAdmin): ?>
            initializeProductForms();
            <?php endif; ?>
            
            // Trigger fade-in animations
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);
        });

        <?php if ($isAdmin): ?>
        function initializeProductForms() {
            // Add product form
            const addForm = document.getElementById('addProductForm');
            if (addForm) {
                addForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add_product');
                    
                    try {
                        const response = await fetch('admin-products.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while adding the product');
                    }
                });
            }

            // Edit product form
            const editForm = document.getElementById('editProductForm');
            if (editForm) {
                editForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update_product');
                    
                    try {
                        const response = await fetch('admin-products.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while updating the product');
                    }
                });
            }
        }

        function editProduct(product) {
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editProductName').value = product.name;
            document.getElementById('editProductCategory').value = product.category || '';
            document.getElementById('editProductBrand').value = product.brand || '';
            document.getElementById('editProductPrice').value = product.unit_price;
            document.getElementById('editProductMoq').value = product.min_order_quantity;
            document.getElementById('editProductStatus').value = product.status;
            document.getElementById('editProductDescription').value = product.description || '';
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }

        async function deleteProduct(id, name) {
            if (await AlertUtils.confirm(`Are you sure you want to delete "${name}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('id', id);
                
                try {
                    const response = await fetch('admin-products.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        AlertUtils.showSuccess(result.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        AlertUtils.showError(result.message);
                    }
                } catch (error) {
                    AlertUtils.showError('An error occurred while deleting the product');
                }
            }
        }
        <?php endif; ?>
    </script>

    <!-- Product Card Styles -->
    <style>
        .product-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(49, 130, 206, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        .product-image {
            position: relative;
            height: 200px;
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-placeholder {
            font-size: 3rem;
            color: var(--text-muted);
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-badge.stock-good {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .stock-badge.stock-low {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .stock-badge.stock-out {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .product-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .product-sku {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .product-meta {
            margin-bottom: 15px;
        }

        .product-meta > div {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .product-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 15px;
            flex: 1;
        }

        .product-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(49, 130, 206, 0.1);
            border-radius: 8px;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--cyan);
        }

        .product-moq {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .product-stock {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
        }

        .stock-info {
            text-align: center;
        }

        .stock-label {
            display: block;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 2px;
        }

        .stock-value {
            display: block;
            color: var(--text-light);
            font-weight: 600;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .product-actions .btn {
            flex: 1;
            min-width: auto;
        }
    </style>
</body>
</html>