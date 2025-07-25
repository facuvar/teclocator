<?php
/**
 * Script simple para añadir la columna de teléfono a la tabla de clientes
 * Este script no requiere autenticación
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'elevator_repair_system';
$username = 'root';
$password = '';

try {
    // Conectar a la base de datos
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
