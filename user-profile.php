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
    $location = trim($_POST['location'] ?? '');
    $timezone = trim($_POST['timezone'] ?? '');
    
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
            $sql = "UPDATE users SET 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    suffix = ?, 
                    email = ?, 
                    phone = ?, 
                    department = ?, 
                    location = ?, 
                    timezone = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $params = [
                $firstName,
                $middleName ?: null,
                $lastName,
                $suffix ?: null,
                $email,
                $phone ?: null,
                $department ?: null,
                $location ?: null,
                $timezone ?: null,
                $currentUser['id']
            ];
            
            executeQuery($sql, $params);
            
            // Update session data
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            
            // Refresh user data
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$currentUser['id']]);
            
            $success = "Profile updated successfully!";
            
        } catch (Exception $e) {
            $errors[] = "Failed to update profile. Please try again.";
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
    $fields = ['first_name', 'last_name', 'email', 'phone', 'department', 'location', 'timezone'];
    $completed = 0;
    foreach ($fields as $field) {
        if (!empty($user[$field])) {
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - TechVent</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --navy: #1a202c;
            --cyan: #3182ce;
            --light-cyan: #63b3ed;
            --dark-bg: #0f1419;
            --card-bg: #2d3748;
            --text-light: #e2e8f0;
            --text-muted: #a0aec0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--navy) 100%);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar-custom {
            background: rgba(26, 32, 44, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(49, 130, 206, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-nav .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--cyan) !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after {
            width: 100%;
        }

        .navbar-nav .nav-link.active::after {
            width: 100%;
        }

        /* Main Content */
        .main-content {
            min-height: calc(100vh - 200px);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%233182ce' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }

        /* Dashboard Header */
        .profile-header {
            position: relative;
            z-index: 2;
            margin-bottom: 40px;
        }

        .profile-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--text-light), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .profile-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .last-updated-text {
            color: var(--text-light);
            font-size: 1rem;
            opacity: 0.8;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(49, 130, 206, 0.1);
            height: 100%;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(49, 130, 206, 0.2);
            border-color: var(--cyan);
        }

        /* Profile Avatar Section */
        .profile-avatar-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px 20px;
            background: rgba(45, 55, 72, 0.4);
            border-radius: 15px;
            border: 1px solid rgba(49, 130, 206, 0.15);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(49, 130, 206, 0.3);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar::after {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar i {
            position: relative;
            z-index: 2;
            color: var(--cyan);
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .profile-status {
            display: inline-block;
            padding: 6px 15px;
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Form Styles */
        .form-control-custom {
            background: rgba(15, 20, 25, 0.7);
            border: 1px solid rgba(49, 130, 206, 0.25);
            border-radius: 10px;
            color: var(--text-light);
            padding: 12px 15px;
            height: 48px;
        }

        .form-control-custom:focus {
            background: rgba(15, 20, 25, 0.9);
            border-color: var(--cyan);
            box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25);
            color: var(--text-light);
        }

        .form-control-custom::placeholder {
            color: var(--text-muted);
        }

        .form-control-custom:disabled {
            background: rgba(15, 20, 25, 0.5);
            color: var(--text-muted);
            opacity: 0.7;
        }

        .form-label-custom {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
            min-height: 20px;
        }

        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: white;
            min-width: 120px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
            color: white;
        }

        .btn-secondary-custom {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.3);
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .btn-secondary-custom:hover {
            background: rgba(156, 163, 175, 0.3);
            color: #9ca3af;
            transform: translateY(-2px);
        }

        /* Info Cards */
        .info-card {
            background: rgba(45, 55, 72, 0.4);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(49, 130, 206, 0.15);
            backdrop-filter: blur(5px);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(49, 130, 206, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label i {
            color: var(--cyan);
            width: 16px;
        }

        .info-value {
            color: var(--text-light);
            font-weight: 600;
        }

        /* Statistics Cards */
        .stat-card {
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

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            transition: width 0.3s ease;
        }

        .stat-card:hover::before {
            width: 8px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, var(--cyan), var(--light-cyan));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 6px 20px rgba(49, 130, 206, 0.3);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Alert styles */
        .alert-custom-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 10px;
            color: #dc3545;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .alert-custom-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            border-radius: 10px;
            color: #28a745;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-title {
                font-size: 2rem;
            }
            
            .dashboard-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .main-content {
                padding: 100px 0 40px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .profile-name {
                font-size: 1.5rem;
            }
        }

        /* Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Edit Mode Styles */
        .edit-mode {
            display: none;
        }

        .edit-mode.active {
            display: block;
        }

        .view-mode.editing {
            display: none;
        }

        /* Form validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
        }

        .is-valid {
            border-color: #28a745 !important;
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
                    <?php if ($currentUser['type'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-dashboard.html">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-user-management.html">
                                <i class="fas fa-users me-1"></i>User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user-profile.php">
                                <i class="fas fa-user me-1"></i>Profile
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user-dashboard.html">
                                <i class="fas fa-boxes me-1"></i>My Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user-profile.php">
                                <i class="fas fa-user me-1"></i>Profile
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
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
                    <div class="dashboard-card fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-light mb-0">
                                <i class="fas fa-user me-2"></i>Profile Information
                            </h3>
                            <button class="btn-primary-custom" id="editProfileBtn">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                        </div>

                        <!-- Profile Avatar Section -->
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'])); ?>
                                <?php if (!empty($user['suffix'])): ?>
                                    <?php echo htmlspecialchars($user['suffix']); ?>
                                <?php endif; ?>
                            </h2>
                            <p class="profile-role"><?php echo htmlspecialchars($user['department'] ?: 'Team Member'); ?></p>
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
                                            <span class="info-value"><?php echo htmlspecialchars($user['first_name'] ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user"></i>Middle Name
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['middle_name'] ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user"></i>Last Name
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['last_name'] ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-tag"></i>Suffix
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['suffix'] ?: 'None'); ?></span>
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
                                            <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Not set'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card">
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-briefcase"></i>Department
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['department'] ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-calendar"></i>Join Date
                                            </span>
                                            <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-map-marker-alt"></i>Location
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['location'] ?: 'Not set'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-clock"></i>Timezone
                                            </span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['timezone'] ?: 'Not set'); ?></span>
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
                                            <option value="Jr." <?php echo ($user['suffix'] == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                            <option value="Sr." <?php echo ($user['suffix'] == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                            <option value="II" <?php echo ($user['suffix'] == 'II') ? 'selected' : ''; ?>>II</option>
                                            <option value="III" <?php echo ($user['suffix'] == 'III') ? 'selected' : ''; ?>>III</option>
                                            <option value="IV" <?php echo ($user['suffix'] == 'IV') ? 'selected' : ''; ?>>IV</option>
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
                                            <option value="Product Management" <?php echo ($user['department'] == 'Product Management') ? 'selected' : ''; ?>>Product Management</option>
                                            <option value="Engineering" <?php echo ($user['department'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                                            <option value="Marketing" <?php echo ($user['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Sales" <?php echo ($user['department'] == 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                            <option value="Support" <?php echo ($user['department'] == 'Support') ? 'selected' : ''; ?>>Support</option>
                                            <option value="Operations" <?php echo ($user['department'] == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Location</label>
                                        <input type="text" class="form-control-custom" name="location" 
                                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                               placeholder="e.g., San Francisco, CA">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Timezone</label>
                                        <select class="form-control-custom" name="timezone">
                                            <option value="">Select Timezone</option>
                                            <option value="PST (UTC-8)" <?php echo ($user['timezone'] == 'PST (UTC-8)') ? 'selected' : ''; ?>>PST (UTC-8)</option>
                                            <option value="MST (UTC-7)" <?php echo ($user['timezone'] == 'MST (UTC-7)') ? 'selected' : ''; ?>>MST (UTC-7)</option>
                                            <option value="CST (UTC-6)" <?php echo ($user['timezone'] == 'CST (UTC-6)') ? 'selected' : ''; ?>>CST (UTC-6)</option>
                                            <option value="EST (UTC-5)" <?php echo ($user['timezone'] == 'EST (UTC-5)') ? 'selected' : ''; ?>>EST (UTC-5)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Join Date</label>
                                        <input type="text" class="form-control-custom" 
                                               value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-12 text-end">
                                        <button type="button" class="btn-secondary-custom me-2" id="cancelEditBtn">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                        <button type="submit" class="btn-primary-custom">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
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
                                    <div class="stat-value"><?php echo $accountStats['profile_complete']; ?>%</div>
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
    
    <!-- Custom JavaScript -->
    <script>
        // Initialize profile page
        document.addEventListener('DOMContentLoaded', function() {
            // Setup event listeners
            setupEventListeners();

            // Animation trigger
            setTimeout(() => {
                document.querySelectorAll('.fade-in').forEach(element => {
                    element.classList.add('visible');
                });
            }, 300);
        });

        function setupEventListeners() {
            const editBtn = document.getElementById('editProfileBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');

            // Edit profile button
            editBtn.addEventListener('click', function() {
                toggleEditMode(true);
            });

            // Cancel edit button
            cancelBtn.addEventListener('click', function() {
                toggleEditMode(false);
            });
        }

        function toggleEditMode(isEditing) {
            const viewMode = document.getElementById('viewMode');
            const editMode = document.getElementById('editMode');
            const editBtn = document.getElementById('editProfileBtn');

            if (isEditing) {
                viewMode.classList.add('editing');
                editMode.classList.add('active');
                editBtn.innerHTML = '<i class="fas fa-eye me-2"></i>View Mode';
                editBtn.onclick = () => toggleEditMode(false);
            } else {
                viewMode.classList.remove('editing');
                editMode.classList.remove('active');
                editBtn.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Profile';
                editBtn.onclick = () => toggleEditMode(true);
            }
        }

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(26, 32, 44, 0.98)';
            } else {
                navbar.style.background = 'rgba(26, 32, 44, 0.95)';
            }
        });
    </script>
</body>
</html>