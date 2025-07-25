<?php
/**
 * Script para actualizar la estructura de la base de datos
 */
require_once __DIR__ . '/../includes/init.php';

// Obtener conexión a la base de datos
$db = Database::getInstance();

// Ejecutar la actualización
try {
    // Añadir campos de latitud y longitud a la tabla visits
    $db->query("
        ALTER TABLE visits 
        ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL,
        ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL
    ");
    
    echo "La base de datos se ha actualizado correctamente.\n";
    echo "Se han añadido los campos de latitud y longitud a la tabla de visitas.\n";
} catch (Exception $e) {
    echo "Error al actualizar la base de datos: " . $e->getMessage() . "\n";
}
