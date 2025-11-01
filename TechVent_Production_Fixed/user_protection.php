<?php
/**
 * User Dashboard Protection  
 * Include this at the top of user-dashboard.html
 */

// Include session management
require_once 'includes/session.php';

// Require user login
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
?>