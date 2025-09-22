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

<!-- Add this logout link to your admin dashboard navigation -->
<!--
<li class="nav-item">
    <a class="nav-link" href="logout.php">
        <i class="fas fa-sign-out-alt me-2"></i>Logout (<?php echo htmlspecialchars($currentUser['name']); ?>)
    </a>
</li>
-->