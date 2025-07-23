<?php
// Incluir configuración y clases necesarias
require_once 'config/config.php';
require_once 'config/database.php';

// Crear instancia de la base de datos
$db = new Database();

// Obtener un cliente específico (puedes cambiar el ID)
$clientId = 1; // Cambia esto al ID de un cliente que sepas que existe
$client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);

// Imprimir información del cliente
echo "<h1>Información del Cliente ID: {$clientId}</h1>";
echo "<pre>";
print_r($client);
echo "</pre>";

// Obtener un ticket relacionado con este cliente
$ticket = $db->selectOne("
    SELECT t.*, 
           c.name as client_name, 
           c.business_name, 
           c.address, 
           c.latitude, 
           c.longitude, 
           u.name as technician_name
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    WHERE c.id = ?
    LIMIT 1
", [$clientId]);

// Imprimir información del ticket
if ($ticket) {
    echo "<h1>Información del Ticket relacionado</h1>";
    echo "<pre>";
    print_r($ticket);
    echo "</pre>";
    
    echo "<h2>Valores específicos de coordenadas:</h2>";
    echo "Latitud: " . (isset($ticket['latitude']) ? $ticket['latitude'] : 'No disponible') . "<br>";
    echo "Longitud: " . (isset($ticket['longitude']) ? $ticket['longitude'] : 'No disponible') . "<br>";
    
    // Verificar si son valores numéricos
    if (isset($ticket['latitude']) && isset($ticket['longitude'])) {
        echo "<h3>Prueba de conversión:</h3>";
        echo "Latitud como número: " . floatval($ticket['latitude']) . "<br>";
        echo "Longitud como número: " . floatval($ticket['longitude']) . "<br>";
    }
} else {
    echo "<p>No se encontró ningún ticket para este cliente.</p>";
}
?>
