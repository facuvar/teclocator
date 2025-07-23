<?php
/**
 * Script para verificar la estructura de la tabla clients
 */
require_once 'includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener la estructura de la tabla
    $stmt = $db->query('DESCRIBE clients');
    
    echo "Estructura de la tabla clients:\n";
    echo "-----------------------------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
