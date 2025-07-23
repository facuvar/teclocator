<?php
/**
 * Dashboard redirector
 * Redirects users to the appropriate dashboard based on their role
 */
require_once 'includes/init.php';

// Require login
$auth->requireLogin();

// Redirect based on role
if ($auth->isAdmin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
} elseif ($auth->isTechnician()) {
    redirect(BASE_URL . '/technician/dashboard.php');
} else {
    // Fallback - should not happen
    redirect(BASE_URL . '/index.php');
}
?>
