<?php
/**
 * Logout functionality
 */
require_once 'includes/init.php';

// Logout the user
$auth->logout();
// Redirect happens in the logout method
