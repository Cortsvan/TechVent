<?php
/**
 * Admin Dashboard Protection
 * Include this at the top of admin-dashboard.html
 */

// Include session management
require_once 'includes/session.php';

// Require admin access
requireAdmin();

// Get current user info
$currentUser = getCurrentUser();
?>