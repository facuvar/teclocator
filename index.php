<?php
// -- INICIO: REGISTRO DE DIAGNÓSTICO --
$logFile = dirname(__FILE__) . '/logs/debug.log';
file_put_contents($logFile, "--- NUEVA SOLICITUD A INDEX.PHP ---\n", FILE_APPEND);
ini_set('log_errors', 1);
ini_set('error_log', $logFile);
// -- FIN: REGISTRO DE DIAGNÓSTICO --

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Main entry point - redirects to login page
 */
header('Location: login.php');
exit;
