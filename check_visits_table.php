<?php
/**
 * Script para verificar la estructura de la tabla visits
 */
require_once 'includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener la estructura de la tabla
    $stmt = $db->query('DESCRIBE visits');
    
    echo "Estructura de la tabla visits:\n";
    echo "-----------------------------\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
