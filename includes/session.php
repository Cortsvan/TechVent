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
            header('Location: user-dashboard.html?message=access_denied');
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
        header('Location: admin-dashboard.html');
    } else {
        header('Location: user-dashboard.html');
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
?>