<?php
/**
 * Session Helper Functions
 * Provides utility functions for session management and authentication
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is regular user
 * @return bool
 */
function isUser() {
    return isLoggedIn() && $_SESSION['user_type'] === 'user';
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'],
        'type' => $_SESSION['user_type']
    ];
}

/**
 * Redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?message=login_required');
        exit();
    }
}

/**
 * Redirect to login page if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        if (!isLoggedIn()) {
            header('Location: login.php?message=admin_required');
        } else {
            header('Location: user-dashboard.php?message=access_denied');
        }
        exit();
    }
}

/**
 * Redirect based on user type
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if (isAdmin()) {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit();
}

/**
 * Display session messages (for success/error notifications)
 * @param string $messageType
 * @return string|null
 */
function getSessionMessage($messageType = 'message') {
    // Check for flash messages in session first
    if (isset($_SESSION[$messageType])) {
        $message = $_SESSION[$messageType];
        unset($_SESSION[$messageType]); // Remove after displaying
        return $message;
    }
    
    // Fallback to URL-based messages
    if (isset($_GET[$messageType])) {
        $messages = [
            'logged_out' => 'You have been successfully logged out.',
            'login_required' => 'Please login to access this page.',
            'admin_required' => 'Admin access required.',
            'access_denied' => 'Access denied. You do not have permission to view this page.'
        ];
        
        $key = $_GET[$messageType];
        return isset($messages[$key]) ? $messages[$key] : null;
    }
    
    return null;
}

/**
 * Set a flash message in session
 * @param string $message
 * @param string $messageType
 */
function setSessionMessage($message, $messageType = 'message') {
    $_SESSION[$messageType] = $message;
}

/**
 * Generate navigation menu items based on user role and current page
 * @param string $currentPage - The current page filename (e.g., 'admin-products.php')
 * @return string HTML for navigation menu items
 */
function generateNavigation($currentPage = '') {
    if (!isLoggedIn()) {
        return '';
    }
    
    $isAdminUser = isAdmin();
    $nav = '';
    
    // Dashboard link
    $dashboardUrl = $isAdminUser ? 'admin-dashboard.php' : 'user-dashboard.php';
    $dashboardActive = ($currentPage === $dashboardUrl) ? 'active' : '';
    $nav .= '<li class="nav-item">
                <a class="nav-link ' . $dashboardActive . '" href="' . $dashboardUrl . '">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
            </li>';
    
    // Suppliers link (admin only)
    if ($isAdminUser) {
        $suppliersActive = ($currentPage === 'admin-suppliers.php') ? 'active' : '';
        $nav .= '<li class="nav-item">
                    <a class="nav-link ' . $suppliersActive . '" href="admin-suppliers.php">
                        <i class="fas fa-truck me-1"></i>Suppliers
                    </a>
                </li>';
    }
    
    // Products link (both admin and user)
    $productsActive = ($currentPage === 'admin-products.php') ? 'active' : '';
    $nav .= '<li class="nav-item">
                <a class="nav-link ' . $productsActive . '" href="admin-products.php">
                    <i class="fas fa-box-open me-1"></i>Products
                </a>
            </li>';
    
    // Inventory link (both admin and user)
    $inventoryActive = ($currentPage === 'admin-inventory.php') ? 'active' : '';
    $nav .= '<li class="nav-item">
                <a class="nav-link ' . $inventoryActive . '" href="admin-inventory.php">
                    <i class="fas fa-boxes me-1"></i>Inventory
                </a>
            </li>';
    
    // User Management link (admin only)
    if ($isAdminUser) {
        $userMgmtActive = ($currentPage === 'admin-user-management.php') ? 'active' : '';
        $nav .= '<li class="nav-item">
                    <a class="nav-link ' . $userMgmtActive . '" href="admin-user-management.php">
                        <i class="fas fa-users me-1"></i>User Management
                    </a>
                </li>';
    }
    
    // Profile link
    $profileActive = ($currentPage === 'user-profile.php') ? 'active' : '';
    $nav .= '<li class="nav-item">
                <a class="nav-link ' . $profileActive . '" href="user-profile.php">
                    <i class="fas fa-user me-1"></i>Profile
                </a>
            </li>';
    
    // Logout link
    $nav .= '<li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </li>';
    
    return $nav;
}
?>