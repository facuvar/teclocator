<?php
// -- INICIO: REGISTRO DE DIAGNÓSTICO --
$logFile = dirname(__DIR__) . '/logs/debug.log';
file_put_contents($logFile, "[OK] init.php cargado.\n", FILE_APPEND);
// -- FIN: REGISTRO DE DIAGNÓSTICO --

/**
 * Initialization file
 * Include this file at the beginning of every page
 */

// Cargar configuración de zona horaria para Argentina
require_once dirname(__FILE__) . '/timezone_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('INCLUDE_PATH', BASE_PATH . '/includes');
define('TEMPLATE_PATH', BASE_PATH . '/templates');

// Determinar el protocolo (http o https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Determinar el nombre del host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Definir BASE_URL dinámicamente
define('BASE_URL', $protocol . $host . '/');

// Load required files
require_once INCLUDE_PATH . '/Database.php';
require_once INCLUDE_PATH . '/Auth.php';

// Initialize auth
$auth = new Auth();

// Helper functions
function redirect($url) {
    // If URL doesn't start with http or /, add BASE_URL
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = BASE_URL . $url;
    } elseif (strpos($url, '/') === 0 && strpos($url, BASE_URL) !== 0) {
        $url = BASE_URL . $url;
    }
    header("Location: $url");
    exit;
}

function escape($string) {
    // Verificar si el valor es nulo y devolver una cadena vacía en ese caso
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function flash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function isActive($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return $currentPage === $page ? 'active' : '';
}
