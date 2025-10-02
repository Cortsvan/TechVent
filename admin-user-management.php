<?php
/**
 * Admin User Management Page - TechVent
 * Manages user accounts with database integration
 */

// Start session
session_start();

// Include required files
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'admin_protection.php';

// Get current user info
$currentUser = getCurrentUser();

// Initialize variables
$users = [];
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $success = handleAddUser();
            break;
        case 'edit_user':
            $success = handleEditUser();
            break;
        case 'delete_user':
            $success = handleDeleteUser();
            break;
        case 'toggle_status':
            $success = handleToggleStatus();
            break;
    }
}

// Get all users with filtering
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$departmentFilter = $_GET['department'] ?? '';

try {
    $sql = "SELECT id, first_name, middle_name, last_name, suffix, email, phone, department, user_type, is_active, created_at
            FROM users WHERE 1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR department LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($typeFilter)) {
        $sql .= " AND user_type = ?";
        $params[] = $typeFilter;
    }
    
    if (!empty($departmentFilter)) {
        $sql .= " AND department = ?";
        $params[] = $departmentFilter;
    }
    
    if (!empty($statusFilter)) {
        if ($statusFilter === 'active') {
            $sql .= " AND is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $sql .= " AND is_active = 0";
        }
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $users = fetchAll($sql, $params);
} catch (Exception $e) {
    $errors[] = "Error loading users: " . $e->getMessage();
}

// Handle user operations
function handleAddUser() {
    global $errors;
    
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'];
    $userType = $_POST['user_type'];
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $errors[] = "All required fields must be filled.";
        return false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
        return false;
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
        return false;
    }
    
    // Check if email already exists
    try {
        $existingUser = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            $errors[] = "A user with this email already exists.";
            return false;
        }
        
        // Insert new user
        $sql = "INSERT INTO users (first_name, middle_name, last_name, suffix, email, phone, department, password, user_type, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $params = [$firstName, $middleName, $lastName, $suffix, $email, $phone, $department, $password, $userType];
        
        executeQuery($sql, $params);
        return "User added successfully!";
        
    } catch (Exception $e) {
        $errors[] = "Error adding user: " . $e->getMessage();
        return false;
    }
}

function handleEditUser() {
    global $errors;
    
    $userId = $_POST['user_id'];
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $userType = $_POST['user_type'];
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errors[] = "All required fields must be filled.";
        return false;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
        return false;
    }
    
    try {
        // Check if email is used by another user
        $existingUser = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existingUser) {
            $errors[] = "This email is already used by another user.";
            return false;
        }
        
        // Update user
        $sql = "UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                email = ?, phone = ?, department = ?, user_type = ? WHERE id = ?";
        $params = [$firstName, $middleName, $lastName, $suffix, $email, $phone, $department, $userType, $userId];
        
        executeQuery($sql, $params);
        return "User updated successfully!";
        
    } catch (Exception $e) {
        $errors[] = "Error updating user: " . $e->getMessage();
        return false;
    }
}

function handleDeleteUser() {
    global $errors, $currentUser;
    
    $userId = $_POST['user_id'];
    
    // Prevent admin from deleting themselves
    if ($userId == $currentUser['id']) {
        $errors[] = "You cannot delete your own account.";
        return false;
    }
    
    try {
        executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
        return "User deleted successfully!";
        
    } catch (Exception $e) {
        $errors[] = "Error deleting user: " . $e->getMessage();
        return false;
    }
}

function handleToggleStatus() {
    global $errors, $currentUser;
    
    $userId = $_POST['user_id'];
    
    // Prevent admin from changing their own status
    if ($userId == $currentUser['id']) {
        $errors[] = "You cannot change your own status.";
        return false;
    }
    
    try {
        // Get current status
        $user = fetchOne("SELECT is_active, first_name, last_name FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            $errors[] = "User not found.";
            return false;
        }
        
        // Toggle status
        $newStatus = $user['is_active'] ? 0 : 1;
        $statusText = $newStatus ? 'activated' : 'deactivated';
        
        // Update user status
        executeQuery("UPDATE users SET is_active = ? WHERE id = ?", [$newStatus, $userId]);
        
        return "User {$user['first_name']} {$user['last_name']} has been {$statusText} successfully!";
        
    } catch (Exception $e) {
        $errors[] = "Error updating user status: " . $e->getMessage();
        return false;
    }
}

// Get user statistics
$userStats = [
    'total_users' => 0,
    'admin_users' => 0,
    'regular_users' => 0,
    'new_this_month' => 0
];

try {
    $userStats['total_users'] = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    $userStats['admin_users'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")['count'];
    $userStats['regular_users'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'user'")['count'];
    $userStats['new_this_month'] = fetchOne("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")['count'];
} catch (Exception $e) {
    // Use default values if query fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - TechVent Admin</title>
    
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
                        <a class="nav-link active" href="admin-user-management.php">
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
            <div class="dashboard-header text-center fade-in mb-4">
                <h1 class="dashboard-title">User Management</h1>
                <p class="dashboard-subtitle">Manage user accounts and permissions</p>
                <p class="last-updated-text">
                    <i class="fas fa-clock me-2"></i>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>
                </p>
            </div>

            <!-- Display messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert-custom-error fade-in mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-custom-success fade-in mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="row mb-4 fade-in">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $userStats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-value"><?php echo $userStats['admin_users']; ?></div>
                        <div class="stat-label">Admin Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-value"><?php echo $userStats['regular_users']; ?></div>
                        <div class="stat-label">Regular Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-value"><?php echo $userStats['new_this_month']; ?></div>
                        <div class="stat-label">New This Month</div>
                    </div>
                </div>
            </div>

            <!-- User Management Section -->
            <div class="row fade-in">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-light mb-0">
                                <i class="fas fa-users me-2"></i>User Management
                            </h3>
                            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-user-plus me-2"></i>Add New User
                            </button>
                        </div>
                        
                        <!-- Search and Filter -->
                        <div class="info-card mb-4">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <input type="text" class="form-control-custom" name="search" 
                                               placeholder="Search users..." 
                                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                                    </div>
                                    <div class="col-md-2 mb-3 mb-md-0">
                                        <select class="form-control-custom" name="type">
                                            <option value="">All Types</option>
                                            <option value="admin" <?php echo $typeFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $typeFilter === 'user' ? 'selected' : ''; ?>>User</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <select class="form-control-custom" name="department">
                                            <option value="">All Departments</option>
                                            <option value="Engineering" <?php echo $departmentFilter === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                            <option value="Product Management" <?php echo $departmentFilter === 'Product Management' ? 'selected' : ''; ?>>Product Management</option>
                                            <option value="Marketing" <?php echo $departmentFilter === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Sales" <?php echo $departmentFilter === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                            <option value="Support" <?php echo $departmentFilter === 'Support' ? 'selected' : ''; ?>>Support</option>
                                            <option value="HR" <?php echo $departmentFilter === 'HR' ? 'selected' : ''; ?>>HR</option>
                                            <option value="Finance" <?php echo $departmentFilter === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                            <option value="Operations" <?php echo $departmentFilter === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3 mb-md-0">
                                        <select class="form-control-custom" name="status">
                                            <option value="">All Status</option>
                                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary-custom w-100">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table user-table" id="userTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th style="min-width:180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="9" class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <h3>No users found</h3>
                                                <p>Try adjusting your search criteria</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-avatar-table">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="user-name">
                                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        <?php if ($user['id'] == $currentUser['id']): ?>
                                                            <span class="user-badge">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($user['middle_name'])): ?>
                                                        <div class="user-meta" style="font-size: 0.85rem; color: var(--text-muted);">
                                                            <?php echo htmlspecialchars($user['middle_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="user-phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="user-department"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $user['user_type'] === 'admin' ? 'status-admin' : 'status-inactive'; ?>">
                                                        <?php echo ucfirst($user['user_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="user-join-date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="user-actions-table">
                                                        <button class="btn-action btn-view" 
                                                                onclick="viewUser('<?php echo $user['id']; ?>')"
                                                                title="View User">
                                                            <i class="fas fa-eye"></i>View
                                                        </button>
                                                        <button class="btn-action btn-edit" 
                                                                onclick="editUser(<?php echo $user['id']; ?>)"
                                                                data-user='<?php echo json_encode($user); ?>'
                                                                title="Edit User">
                                                            <i class="fas fa-edit"></i>Edit
                                                        </button>
                                                        <?php if ($user['id'] != $currentUser['id']): ?>
                                                            <button class="btn-action btn-toggle" 
                                                                    onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', <?php echo $user['is_active'] ? 'true' : 'false'; ?>)"
                                                                    title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                                                                <i class="fas fa-<?php echo $user['is_active'] ? 'user-slash' : 'user-check'; ?>"></i>
                                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                            <button class="btn-action btn-delete" 
                                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')"
                                                                    title="Delete User">
                                                                <i class="fas fa-trash"></i>Delete
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

    <!-- Add User Modal -->
    <div class="modal fade modal-custom" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">First Name *</label>
                                <input type="text" class="form-control-custom" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Last Name *</label>
                                <input type="text" class="form-control-custom" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Middle Name</label>
                                <input type="text" class="form-control-custom" name="middle_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Suffix</label>
                                <input type="text" class="form-control-custom" name="suffix" placeholder="Jr., Sr., III, etc.">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Email Address *</label>
                            <input type="email" class="form-control-custom" name="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Phone Number</label>
                                <input type="tel" class="form-control-custom" name="phone" placeholder="+1-555-0123">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Department</label>
                                <select class="form-control-custom" name="department">
                                    <option value="">Select Department</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Product Management">Product Management</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Support">Support</option>
                                    <option value="HR">Human Resources</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Operations">Operations</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Password *</label>
                            <input type="password" class="form-control-custom" name="password" required minlength="8">
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">User Type *</label>
                            <select class="form-control-custom" name="user_type" required>
                                <option value="">Select user type</option>
                                <option value="user">Regular User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade modal-custom" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">First Name *</label>
                                <input type="text" class="form-control-custom" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Last Name *</label>
                                <input type="text" class="form-control-custom" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Middle Name</label>
                                <input type="text" class="form-control-custom" name="middle_name" id="edit_middle_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Suffix</label>
                                <input type="text" class="form-control-custom" name="suffix" id="edit_suffix">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Email Address *</label>
                            <input type="email" class="form-control-custom" name="email" id="edit_email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Phone Number</label>
                                <input type="tel" class="form-control-custom" name="phone" id="edit_phone" placeholder="+1-555-0123">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-custom">Department</label>
                                <select class="form-control-custom" name="department" id="edit_department">
                                    <option value="">Select Department</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Product Management">Product Management</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Support">Support</option>
                                    <option value="HR">Human Resources</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Operations">Operations</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">User Type *</label>
                            <select class="form-control-custom" name="user_type" id="edit_user_type" required>
                                <option value="user">Regular User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade modal-custom" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="deleteUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <p style="color: var(--text-light);">Are you sure you want to delete the user <strong id="delete_user_name"></strong>?</p>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-outline-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div class="modal fade modal-custom" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggle_modal_title">Confirm Status Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="toggleStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" id="toggle_user_id">
                        <p style="color: var(--text-light);" id="toggle_message"></p>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">This will change the user's access to the system.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom" id="toggle_confirm_btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TechVent Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- User Management Specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize UserManager
            UserManager.init();
            
            // Trigger fade-in animations
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);
        });

        function viewUser(userId) {
            // Find user data
            const userRow = document.querySelector(`button[onclick*="viewUser('${userId}')"]`).closest('tr');
            const userName = userRow.querySelector('.user-name').textContent.trim();
            const userEmail = userRow.querySelector('.user-email').textContent.trim();
            const userPhone = userRow.querySelector('.user-phone').textContent.trim();
            const userDepartment = userRow.querySelector('.user-department').textContent.trim();
            const userType = userRow.querySelector('.status-badge').textContent.trim();
            const joinDate = userRow.querySelector('.user-join-date').textContent.trim();
            
            const userInfo = `
                User Information:
                
                Name: ${userName}
                Email: ${userEmail}
                Phone: ${userPhone}
                Department: ${userDepartment}
                Type: ${userType}
                Join Date: ${joinDate}
            `;
            
            alert(userInfo);
        }

        function editUser(userId) {
            // Get user data from the button's data attribute
            const button = event.target.closest('button');
            const userData = JSON.parse(button.getAttribute('data-user'));
            
            // Populate the edit modal with all fields
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_first_name').value = userData.first_name;
            document.getElementById('edit_middle_name').value = userData.middle_name || '';
            document.getElementById('edit_last_name').value = userData.last_name;
            document.getElementById('edit_suffix').value = userData.suffix || '';
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_phone').value = userData.phone || '';
            document.getElementById('edit_department').value = userData.department || '';
            document.getElementById('edit_user_type').value = userData.user_type;
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        function toggleUserStatus(userId, userName, isActive) {
            const action = isActive ? 'deactivate' : 'activate';
            const actionText = isActive ? 'Deactivate' : 'Activate';
            
            document.getElementById('toggle_user_id').value = userId;
            document.getElementById('toggle_modal_title').textContent = `${actionText} User`;
            document.getElementById('toggle_message').innerHTML = `Are you sure you want to <strong>${action}</strong> the user <strong>${userName}</strong>?`;
            document.getElementById('toggle_confirm_btn').textContent = actionText;
            
            const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
            modal.show();
        }

        function deleteUser(userId, userName) {
            UserManager.showDeleteModal(userId, userName);
        }
    </script>
</body>
</html>