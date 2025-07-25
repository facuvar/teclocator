<?php
header('Content-Type: text/plain; charset=utf-8');
$logFile = __DIR__ . '/logs/diagnostic.log';

if (file_exists($logFile)) {
    echo "--- Contenido de logs/diagnostic.log ---\n\n";
    echo file_get_contents($logFile);
} else {
    echo "El archivo de registro 'diagnostic.log' aún no existe.\n";
    echo "Intenta cargar la página principal (la que da error) para generarlo.";
} 