<?php
/**
 * Database configuration
 */
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
