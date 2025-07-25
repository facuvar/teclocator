<?php
header('Content-Type: text/plain');
$logFile = dirname(__FILE__) . '/logs/debug.log';
if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "El archivo de registro no existe todavía. Intenta cargar la página principal primero.";
} 