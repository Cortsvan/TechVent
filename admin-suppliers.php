<?php
/**
 * Admin Suppliers Management - TechVent
 * Manage suppliers and their product catalogs
 */

// Start session
session_start();

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'admin_protection.php';

// Get current user info
$currentUser = getCurrentUser();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_supplier':
                $name = trim($_POST['name']);
                $contact_person = trim($_POST['contact_person']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $website = trim($_POST['website']);
                
                $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address, website, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $params = [$name, $contact_person, $email, $phone, $address, $website];
                
                if (executeQuery($sql, $params)) {
                    echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add supplier']);
                }
                break;
                
            case 'update_supplier':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $contact_person = trim($_POST['contact_person']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $website = trim($_POST['website']);
                
                $sql = "UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, website = ? WHERE id = ?";
                $params = [$name, $contact_person, $email, $phone, $address, $website, $id];
                
                if (executeQuery($sql, $params)) {
                    echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update supplier']);
                }
                break;
                
            case 'delete_supplier':
                $id = $_POST['id'];
                
                // Check if supplier has products first
                $productCount = fetchOne("SELECT COUNT(*) as count FROM products WHERE supplier_id = ?", [$id])['count'];
                
                if ($productCount > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete supplier with existing products']);
                } else {
                    if (executeQuery("DELETE FROM suppliers WHERE id = ?", [$id])) {
                        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete supplier']);
                    }
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Get suppliers with product count
try {
    $suppliers = fetchAll("
        SELECT s.*, 
               COUNT(p.id) as product_count,
               SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_products
        FROM suppliers s 
        LEFT JOIN products p ON s.id = p.supplier_id 
        GROUP BY s.id 
        ORDER BY s.name ASC
    ");
} catch (Exception $e) {
    $suppliers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - TechVent</title>
    
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
                        <a class="nav-link" href="admin-dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-suppliers.php">
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
            <!-- Page Header -->
            <div class="dashboard-header text-center fade-in">
                <h1 class="dashboard-title">Supplier Management</h1>
                <p class="dashboard-subtitle">Manage your suppliers and their product catalogs</p>
            </div>

            <!-- Action Bar -->
            <div class="row mb-4 fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center mb-2 mb-md-0">
                                <h5 class="text-light mb-0 me-3">
                                    <i class="fas fa-truck me-2"></i>Suppliers (<?php echo count($suppliers); ?>)
                                </h5>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                    <i class="fas fa-plus me-2"></i>Add New Supplier
                                </button>
                                <button class="btn btn-secondary-custom" onclick="exportSuppliers()">
                                    <i class="fas fa-download me-2"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suppliers Table -->
            <div class="row fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="table-responsive">
                            <table class="table table-custom" id="suppliersTable">
                                <thead>
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Contact Person</th>
                                        <th>Contact Info</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suppliers)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-truck fa-3x mb-3" style="color: var(--text-muted);"></i>
                                                <p style="color: var(--text-muted);">No suppliers found. Add your first supplier to get started.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3">
                                                            <?php echo strtoupper(substr($supplier['name'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-light"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                                            <?php if ($supplier['website']): ?>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-globe me-1"></i>
                                                                    <a href="<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank" class="text-muted">
                                                                        <?php echo htmlspecialchars($supplier['website']); ?>
                                                                    </a>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-light"><?php echo htmlspecialchars($supplier['contact_person']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-light">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($supplier['email']); ?>
                                                    </div>
                                                    <?php if ($supplier['phone']): ?>
                                                        <div class="text-muted">
                                                            <i class="fas fa-phone me-1"></i>
                                                            <?php echo htmlspecialchars($supplier['phone']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="text-light">
                                                        <i class="fas fa-box me-1"></i>
                                                        <?php echo $supplier['product_count']; ?> total
                                                    </div>
                                                    <div class="text-muted">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        <?php echo $supplier['active_products']; ?> active
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">Active</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-action btn-sm" 
                                                                onclick="viewSupplier(<?php echo $supplier['id']; ?>)"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-action btn-sm" 
                                                                onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)"
                                                                title="Edit Supplier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="admin-products.php?supplier_id=<?php echo $supplier['id']; ?>" 
                                                           class="btn btn-action btn-sm" 
                                                           title="Manage Products">
                                                            <i class="fas fa-box-open"></i>
                                                        </a>
                                                        <button class="btn btn-action btn-sm btn-danger" 
                                                                onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['name']); ?>')"
                                                                title="Delete Supplier">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addSupplierForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplierName" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="supplierName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactPerson" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" id="contactPerson" name="contact_person" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="supplierEmail" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="supplierEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="supplierPhone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="supplierPhone" name="phone">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="supplierAddress" class="form-label">Address</label>
                                <textarea class="form-control" id="supplierAddress" name="address" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="supplierWebsite" class="form-label">Website</label>
                                <input type="url" class="form-control" id="supplierWebsite" name="website" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSupplierForm">
                    <input type="hidden" id="editSupplierId" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editSupplierName" class="form-label">Supplier Name *</label>
                                <input type="text" class="form-control" id="editSupplierName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editContactPerson" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" id="editContactPerson" name="contact_person" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editSupplierEmail" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="editSupplierEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editSupplierPhone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="editSupplierPhone" name="phone">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editSupplierAddress" class="form-label">Address</label>
                                <textarea class="form-control" id="editSupplierAddress" name="address" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editSupplierWebsite" class="form-label">Website</label>
                                <input type="url" class="form-control" id="editSupplierWebsite" name="website" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Update Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Suppliers Management JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize forms
            initializeSupplierForms();
            
            // Trigger fade-in animations
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);
        });

        function initializeSupplierForms() {
            // Add supplier form
            const addForm = document.getElementById('addSupplierForm');
            if (addForm) {
                addForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add_supplier');
                    
                    try {
                        const response = await fetch('admin-suppliers.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while adding the supplier');
                    }
                });
            }

            // Edit supplier form
            const editForm = document.getElementById('editSupplierForm');
            if (editForm) {
                editForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'update_supplier');
                    
                    try {
                        const response = await fetch('admin-suppliers.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AlertUtils.showSuccess(result.message);
                            bootstrap.Modal.getInstance(document.getElementById('editSupplierModal')).hide();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            AlertUtils.showError(result.message);
                        }
                    } catch (error) {
                        AlertUtils.showError('An error occurred while updating the supplier');
                    }
                });
            }
        }

        function editSupplier(supplier) {
            document.getElementById('editSupplierId').value = supplier.id;
            document.getElementById('editSupplierName').value = supplier.name;
            document.getElementById('editContactPerson').value = supplier.contact_person;
            document.getElementById('editSupplierEmail').value = supplier.email;
            document.getElementById('editSupplierPhone').value = supplier.phone || '';
            document.getElementById('editSupplierAddress').value = supplier.address || '';
            document.getElementById('editSupplierWebsite').value = supplier.website || '';
            
            new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
        }

        async function deleteSupplier(id, name) {
            if (await AlertUtils.confirm(`Are you sure you want to delete "${name}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_supplier');
                formData.append('id', id);
                
                try {
                    const response = await fetch('admin-suppliers.php', {
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
                    AlertUtils.showError('An error occurred while deleting the supplier');
                }
            }
        }

        function viewSupplier(id) {
            // Navigate to supplier details or products
            window.location.href = `admin-products.php?supplier_id=${id}`;
        }

        function exportSuppliers() {
            AlertUtils.showSuccess('Export functionality coming soon!');
        }
    </script>
</body>
</html>