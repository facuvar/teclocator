<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Main entry point - redirects to login page
 */
header('Location: login.php');
exit;
