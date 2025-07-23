<?php
/**
 * Script para verificar los comentarios de las visitas en la base de datos
 */
require_once 'includes/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener todas las visitas con sus comentarios
    $stmt = $db->query("
        SELECT v.id, v.ticket_id, v.start_time, v.end_time, v.comments, v.completion_status, 
               t.description as ticket_description,
               c.name as client_name
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        ORDER BY v.id DESC
        LIMIT 10
    ");
    
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Últimas 10 visitas registradas:\n";
    echo "==============================\n\n";
    
    foreach ($visits as $visit) {
        echo "ID de Visita: " . $visit['id'] . "\n";
        echo "Ticket: #" . $visit['ticket_id'] . " - " . substr($visit['ticket_description'], 0, 50) . "...\n";
        echo "Cliente: " . $visit['client_name'] . "\n";
        echo "Inicio: " . $visit['start_time'] . "\n";
        echo "Fin: " . ($visit['end_time'] ? $visit['end_time'] : 'No finalizada') . "\n";
        echo "Estado: " . $visit['completion_status'] . "\n";
        echo "Comentarios: " . ($visit['comments'] ? '"' . $visit['comments'] . '"' : 'Sin comentarios') . "\n";
        echo "-------------------------------\n\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
