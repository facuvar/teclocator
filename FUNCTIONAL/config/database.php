<?php
/**
 * Database configuration
 */

// Detectar si estamos en el entorno de Railway
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Configuración para Railway (usando variables de entorno de Railway)
    return [
        'host' => getenv('MYSQLHOST'),
        'dbname' => getenv('MYSQLDATABASE'),
        'username' => getenv('MYSQLUSER'),
        'password' => getenv('MYSQLPASSWORD'),
        'port' => getenv('MYSQLPORT'),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
} else {
    // Configuración para el entorno local (el que estaba antes)
    return [
        'host' => 'localhost',
        'dbname' => 'teclocate_db',
        'username' => 'company_tec',
        'password' => '24197074kube',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
}
