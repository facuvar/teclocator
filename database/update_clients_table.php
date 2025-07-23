<?php
/**
 * Script para actualizar la tabla de clientes añadiendo el campo de teléfono
 */

// Usar ruta absoluta para incluir el archivo de inicialización
$rootPath = dirname(__DIR__);
require_once $rootPath . '/includes/init.php';

// Crear instancia de Auth y verificar si es administrador
$auth = new Auth();
$auth->requireAdmin();

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la columna ya existe
    $checkColumnQuery = "SHOW COLUMNS FROM clients LIKE 'phone'";
    $stmt = $db->prepare($checkColumnQuery);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // La columna no existe, agregarla
        $alterTableQuery = "ALTER TABLE clients ADD COLUMN phone VARCHAR(20) NULL AFTER address";
        $db->exec($alterTableQuery);
        echo "Se ha añadido correctamente la columna 'phone' a la tabla de clientes.";
    } else {
        echo "La columna 'phone' ya existe en la tabla de clientes.";
    }
    
} catch (PDOException $e) {
    echo "Error al actualizar la tabla: " . $e->getMessage();
}
?>
