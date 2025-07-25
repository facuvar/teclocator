<?php
/**
 * Script para agregar el campo start_notes a la tabla visits
 */
// Usar ruta absoluta para incluir el archivo de inicialización
$rootPath = dirname(__DIR__);
require_once $rootPath . '/includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Verificando si el campo start_notes existe en la tabla visits...\n";
    
    // Verificar si el campo ya existe
    $stmt = $db->query("SHOW COLUMNS FROM visits LIKE 'start_notes'");
    
    if ($stmt->rowCount() > 0) {
        echo "El campo start_notes ya existe en la tabla visits.\n";
    } else {
        echo "Agregando el campo start_notes a la tabla visits...\n";
        
        // Agregar el campo start_notes después del campo start_time
        $db->exec("ALTER TABLE visits ADD COLUMN start_notes TEXT AFTER start_time");
        
        echo "Campo start_notes agregado correctamente.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
