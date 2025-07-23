<?php
/**
 * Script para actualizar la tabla de clientes añadiendo los campos necesarios
 * para la estructura completa importada desde Excel
 */

// Usar ruta absoluta para incluir el archivo de inicialización
$rootPath = dirname(__DIR__);
require_once $rootPath . '/includes/init.php';

// Comentamos la autenticación para poder ejecutar desde línea de comandos
// $auth = new Auth();
// $auth->requireAdmin();

try {
    echo "Iniciando actualización de la tabla clients...\n";
    
    $db = Database::getInstance()->getConnection();
    
    // Array de columnas a verificar y añadir si no existen
    $columns = [
        'client_number' => "ALTER TABLE clients ADD COLUMN client_number VARCHAR(50) NULL AFTER id",
        'group_vendor' => "ALTER TABLE clients ADD COLUMN group_vendor VARCHAR(100) NULL AFTER phone"
    ];
    
    $addedColumns = [];
    $existingColumns = [];
    
    foreach ($columns as $column => $query) {
        // Verificar si la columna ya existe
        $checkColumnQuery = "SHOW COLUMNS FROM clients LIKE '$column'";
        $stmt = $db->prepare($checkColumnQuery);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // La columna no existe, agregarla
            echo "Añadiendo columna '$column'...\n";
            $db->exec($query);
            $addedColumns[] = $column;
        } else {
            echo "La columna '$column' ya existe.\n";
            $existingColumns[] = $column;
        }
    }
    
    // Mensaje de éxito
    if (count($addedColumns) > 0) {
        echo "Se han añadido las siguientes columnas a la tabla de clientes: " . implode(", ", $addedColumns) . "\n";
    } else {
        echo "No se han añadido nuevas columnas. Las siguientes columnas ya existían: " . implode(", ", $existingColumns) . "\n";
    }
    
    echo "Actualización completada con éxito.\n";
    
} catch (PDOException $e) {
    // Mensaje de error
    echo "Error al actualizar la tabla: " . $e->getMessage() . "\n";
}
?>
