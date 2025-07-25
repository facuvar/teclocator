<?php
/**
 * Script de prueba para notificaciones de WhatsApp
 */
require_once 'includes/init.php';
require_once 'includes/WhatsAppNotifier.php';

// Habilitar visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener conexión a la base de datos
$db = Database::getInstance();

// ID del técnico a probar
$technicianId = 5;

// Obtener datos del técnico
$technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);

// Verificar si el técnico existe
if (!$technician) {
    die("Error: El técnico con ID {$technicianId} no existe.");
}

// Mostrar información del técnico
echo "<h2>Información del Técnico:</h2>";
echo "<pre>";
// Ocultar la contraseña por seguridad
$technicianInfo = $technician;
$technicianInfo['password'] = '********';
print_r($technicianInfo);
echo "</pre>";

// Verificar si tiene número de teléfono
if (empty($technician['phone'])) {
    echo "<p style='color:red'>Error: El técnico no tiene número de teléfono registrado.</p>";
    
    // Actualizar el número de teléfono para pruebas (reemplazar con un número real)
    echo "<h3>Actualizando número de teléfono para pruebas...</h3>";
    $db->update('users', ['phone' => '5491112345678'], 'id = ?', [$technicianId]);
    echo "<p>Número de teléfono actualizado a: 5491112345678</p>";
    
    // Obtener datos actualizados
    $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
}

// Obtener un ticket existente para pruebas
$ticket = $db->selectOne("SELECT * FROM tickets WHERE technician_id = ? LIMIT 1", [$technicianId]);

// Si no hay ticket, obtener cualquier ticket
if (!$ticket) {
    $ticket = $db->selectOne("SELECT * FROM tickets LIMIT 1");
    
    if (!$ticket) {
        die("Error: No hay tickets en el sistema para realizar la prueba.");
    }
}

// Obtener datos del cliente
$client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticket['client_id']]);

// Mostrar información del ticket
echo "<h2>Información del Ticket:</h2>";
echo "<pre>";
print_r($ticket);
echo "</pre>";

// Mostrar información del cliente
echo "<h2>Información del Cliente:</h2>";
echo "<pre>";
print_r($client);
echo "</pre>";

// Probar envío de notificación
echo "<h2>Probando envío de notificación:</h2>";

try {
    $whatsapp = new WhatsAppNotifier();
    $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
    
    if ($result) {
        echo "<p style='color:green'>Notificación enviada correctamente.</p>";
    } else {
        echo "<p style='color:red'>Error al enviar la notificación.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Excepción: " . $e->getMessage() . "</p>";
}

// Mostrar configuración de WhatsApp
echo "<h2>Configuración de WhatsApp:</h2>";
echo "<pre>";
$config = require_once 'config/whatsapp.php';
// Ocultar token por seguridad
$config['token'] = substr($config['token'], 0, 10) . '...' . substr($config['token'], -10);
print_r($config);
echo "</pre>";
