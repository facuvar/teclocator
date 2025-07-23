<?php
/**
 * Database configuration
 */
return [
    'host' => 'localhost',
    'dbname' => 'teclocate_db',
    'username' => 'teclocate_user',
    'password' => 'contraseña_segura', // Reemplazar con la contraseña real en producción
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
