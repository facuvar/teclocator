<?php
/**
 * Script to add service_requests table to the database
 */
require_once __DIR__ . '/../includes/init.php';

// Get database connection
$db = Database::getInstance();

// Read SQL file
$sql = file_get_contents(__DIR__ . '/add_service_requests_table.sql');

// Execute SQL
try {
    $db->getPdo()->exec($sql);
    echo "La tabla de solicitudes de servicio se ha creado correctamente.\n";
} catch (PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage() . "\n";
}
