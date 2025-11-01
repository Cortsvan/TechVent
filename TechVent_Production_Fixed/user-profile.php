<?php
/**
 * User Profile Page - TechVent
 * Handles user profile display and updates
 */

// Start session
session_start();

// Include database connection and session helpers
require_once 'config/db.php';
require_once 'includes/session.php';

// Check if user is logged in
requireLogin();

// Get current user data
$currentUser = getCurrentUser();
$errors = [];
$success = '';

// Get user data from database
try {
    $sql = "SELECT * FROM users WHERE id = ?";
    $user = fetchOne($sql, [$currentUser['id']]);
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    $errors[] = "Error loading profile data.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName']);
    $suffix = trim($_POST['suffix'] ?? '');
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    
    // Validation
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    
    // Check if email is taken by another user
    if (empty($errors) && $email !== $user['email']) {
        try {
            $emailCheck = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $currentUser['id']]);
            if ($emailCheck) {
                $errors[] = "This email address is already in use by another account.";
            }
        } catch (Exception $e) {
            $errors[] = "Error validating email address.";
        }
    }
    
    // If no errors, update the user profile
    if (empty($errors)) {
        try {
            // Build the UPDATE query dynamically based on available fields
            $updateFields = [
                'first_name = ?',
                'last_name = ?', 
                'email = ?',
                'updated_at = NOW()'
            ];
            $params = [$firstName, $lastName, $email];
            
            // Add optional fields only if they exist in the database
            if (!empty($middleName)) {
                $updateFields[] = 'middle_name = ?';
                $params[] = $middleName;
            } else {
                $updateFields[] = 'middle_name = NULL';
            }
            
            if (!empty($suffix)) {
                $updateFields[] = 'suffix = ?';
                $params[] = $suffix;
            } else {
                $updateFields[] = 'suffix = NULL';
            }
            
            // Try to update extended profile fields (these may not exist in database yet)
            try {
                // Check if extended fields exist by attempting a simple query
                global $pdo;
                $testQuery = $pdo->prepare("SELECT phone, department FROM users LIMIT 1");
                $testQuery->execute();
                
                // If we reach here, the fields exist, so include them in update
                if (!empty($phone)) {
                    $updateFields[] = 'phone = ?';
                    $params[] = $phone;
                } else {
                    $updateFields[] = 'phone = NULL';
                }
                
                if (!empty($department)) {
                    $updateFields[] = 'department = ?';
                    $params[] = $department;
                } else {
                    $updateFields[] = 'department = NULL';
                }
                
            } catch (Exception $e) {
                // Extended fields don't exist in database yet, skip them
                $errors[] = "Note: Some profile fields are not available. Please run the database migration to enable full profile editing.";
            }
            
            // Add user ID parameter
            $params[] = $currentUser['id'];
            
            // Build and execute the final query
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            executeQuery($sql, $params);
            
            // Update session data
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            
            // Refresh user data
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$currentUser['id']]);
            
            $success = "Profile updated successfully!";
            
        } catch (Exception $e) {
            $errors[] = "Failed to update profile. Please try again. Error: " . $e->getMessage();
        }
    }
}

// Calculate account statistics
$accountStats = [
    'products_managed' => 0,
    'days_active' => 0,
    'recent_updates' => 0,
    'profile_complete' => 0
];

try {
    // Calculate days since registration
    $joinDate = new DateTime($user['created_at']);
    $now = new DateTime();
    $accountStats['days_active'] = $now->diff($joinDate)->days;
    
    // Calculate profile completeness
    $fields = ['first_name', 'last_name', 'email', 'phone', 'department'];
    $completed = 0;
    foreach ($fields as $field) {
        if (!empty($user[$field] ?? '')) {
            $completed++;
        }
    }
    $accountStats['profile_complete'] = round(($completed / count($fields)) * 100);
    
    // You can add more statistics here based on your database structure
    // For now, using some example values
    $accountStats['products_managed'] = 24;
    $accountStats['recent_updates'] = 47;
    
} catch (Exception $e) {
    // Use default values if calculation fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="shortcut icon" href="favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - TechVent</title>
    
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
                    <?php if ($currentUser['type'] === 'admin'): ?>
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
                            <a class="nav-link" href="admin-user-management.php">
                                <i class="fas fa-users me-1"></i>User Management
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user-dashboard.php">
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
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="user-profile.php">
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
            <!-- Profile Header -->
            <div class="profile-header text-center fade-in">
                <h1 class="profile-title">User Profile</h1>
                <p class="profile-subtitle">Manage your account information and settings</p>
                <p class="last-updated-text">
                    <i class="fas fa-clock me-2"></i>Last updated: 
                    <span><?php echo $user['updated_at'] ? date('M j, Y g:i A', strtotime($user['updated_at'])) : date('M j, Y g:i A', strtotime($user['created_at'])); ?></span>
                </p>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8 mb-4">
                    <?php 
                    // Check if extended profile fields are available
                    $hasExtendedFields = false;
                    try {
                        global $pdo;
                        $testQuery = $pdo->prepare("SELECT phone, department FROM users LIMIT 1");
                        $testQuery->execute();
                        $hasExtendedFields = true;
                    } catch (Exception $e) {
                        // Extended fields don't exist
                    }
                    ?>
                    
                    <?php if (!$hasExtendedFields): ?>
                    <!-- Migration Notice -->
                    <div class="alert alert-info mb-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Enhanced Profile Features Available</h6>
                        <p class="mb-2">To unlock full profile editing capabilities (phone, department), please run the database migration:</p>
                        <code>database_migration_profile_fields.sql</code>
                        <p class="mt-2 mb-0"><small>Basic profile editing (name, email) is still available.</small></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="dashboard-card fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-light mb-0">
                                <i class="fas fa-user me-2"></i>Profile Information
                            </h3>
                        </div>

                        <!-- Profile Avatar Section -->
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name'])); ?>
                                <?php if (!empty($user['suffix'] ?? '')): ?>
                                    <?php echo htmlspecialchars($user['suffix']); ?>
                                <?php endif; ?>
                            </h2>
                            <p class="profile-role"><?php echo htmlspecialchars(($user['department'] ?? '') ?: 'Team Member'); ?></p>
                            <span class="profile-status">Active</span>
                        </div>

                        <!-- Display errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert-custom-error">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Display success message -->
                        <?php if (!empty($success)): ?>
                            <div class="alert-custom-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <!-- View Mode -->
                        <div class="view-mode" id="viewMode">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user"></i>First Name
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['first_name'] ?? '') ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user"></i>Middle Name
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['middle_name'] ?? '') ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user"></i>Last Name
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['last_name'] ?? '') ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-tag"></i>Suffix
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['suffix'] ?? '') ?: 'None'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-envelope"></i>Email
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-phone"></i>Phone
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['phone'] ?? '') ?: 'Not set'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-briefcase"></i>Department
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(($user['department'] ?? '') ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-calendar"></i>Join Date
                                            </span>
                                            <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-shield-alt"></i>User Type
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Profile Button at Bottom -->
                            <div class="edit-profile-btn-container">
                                <button class="btn-primary-custom" id="editProfileBtn">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </button>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <div class="edit-mode" id="editMode">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">First Name</label>
                                        <input type="text" class="form-control-custom" name="firstName" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Middle Name</label>
                                        <input type="text" class="form-control-custom" name="middleName" 
                                               value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label-custom">Last Name</label>
                                        <input type="text" class="form-control-custom" name="lastName" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-custom">Suffix</label>
                                        <select class="form-control-custom" name="suffix">
                                            <option value="">None</option>
                                            <option value="Jr." <?php echo (($user['suffix'] ?? '') == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                            <option value="Sr." <?php echo (($user['suffix'] ?? '') == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                            <option value="II" <?php echo (($user['suffix'] ?? '') == 'II') ? 'selected' : ''; ?>>II</option>
                                            <option value="III" <?php echo (($user['suffix'] ?? '') == 'III') ? 'selected' : ''; ?>>III</option>
                                            <option value="IV" <?php echo (($user['suffix'] ?? '') == 'IV') ? 'selected' : ''; ?>>IV</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Email</label>
                                        <input type="email" class="form-control-custom" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Phone</label>
                                        <input type="tel" class="form-control-custom" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Department</label>
                                        <select class="form-control-custom" name="department">
                                            <option value="">Select Department</option>
                                            <option value="Product Management" <?php echo (($user['department'] ?? '') == 'Product Management') ? 'selected' : ''; ?>>Product Management</option>
                                            <option value="Engineering" <?php echo (($user['department'] ?? '') == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                            <option value="Marketing" <?php echo (($user['department'] ?? '') == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Sales" <?php echo (($user['department'] ?? '') == 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                            <option value="Support" <?php echo (($user['department'] ?? '') == 'Support') ? 'selected' : ''; ?>>Support</option>
                                            <option value="Operations" <?php echo (($user['department'] ?? '') == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Join Date</label>
                                        <input type="text" class="form-control-custom" 
                                               value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn-secondary-custom" id="cancelEditBtn">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn-primary-custom">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="col-lg-4">
                    <div class="dashboard-card fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-light mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Account Overview
                            </h3>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $accountStats['products_managed']; ?></div>
                                    <div class="stat-label">Products Managed</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $accountStats['days_active']; ?></div>
                                    <div class="stat-label">Days Active</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $accountStats['recent_updates']; ?></div>
                                    <div class="stat-label">Recent Updates</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="stat-value">
                                        <?php echo $accountStats['profile_complete']; ?>%
                                        <?php 
                                        $completionClass = 'low';
                                        if ($accountStats['profile_complete'] >= 80) {
                                            $completionClass = 'high';
                                        } elseif ($accountStats['profile_complete'] >= 50) {
                                            $completionClass = 'medium';
                                        }
                                        ?>
                                        <span class="profile-complete-badge <?php echo $completionClass; ?>">
                                            <?php 
                                            if ($accountStats['profile_complete'] >= 80) {
                                                echo 'Complete';
                                            } elseif ($accountStats['profile_complete'] >= 50) {
                                                echo 'Good';
                                            } else {
                                                echo 'Needs Work';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="stat-label">Profile Complete</div>
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
    
    <!-- Profile Page Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all profile management features
            ProfileManager.init();
        });
    </script>
</body>
</html>