<?php
/**
 * Script directo para probar notificaciones de WhatsApp
 */

// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'includes/init.php';
require_once 'includes/WhatsAppNotifier.php';

// Título
echo "<h1>Prueba Directa de WhatsApp</h1>";

// Verificar si hay un técnico seleccionado
$technicianId = $_GET['tech_id'] ?? null;

// Obtener la conexión a la base de datos
$db = Database::getInstance();

// Obtener técnicos disponibles
$technicians = $db->select("SELECT id, name, email, phone FROM users WHERE role = 'technician' ORDER BY name");

// Mostrar lista de técnicos
echo "<h2>Seleccione un técnico:</h2>";
echo "<ul>";
foreach ($technicians as $technician) {
    $phone = !empty($technician['phone']) ? " - " . $technician['phone'] : " (Sin teléfono)";
    echo "<li><a href='test_whatsapp_direct.php?tech_id={$technician['id']}'>{$technician['name']}{$phone}</a></li>";
}
echo "</ul>";

// Si se seleccionó un técnico, enviar notificación
if ($technicianId) {
    echo "<h2>Enviando notificación...</h2>";
    
    try {
        // Obtener datos del técnico
        $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
        
        if (!$technician) {
            throw new Exception("No se pudo encontrar el técnico seleccionado.");
        }
        
        // Verificar que el técnico tenga número de teléfono
        if (empty($technician['phone'])) {
            throw new Exception("El técnico seleccionado no tiene número de teléfono registrado.");
        }
        
        echo "<p>Técnico: {$technician['name']} ({$technician['phone']})</p>";
        
        // Crear datos de prueba para el ticket y cliente
        $ticket = [
            'id' => 'TEST-' . date('YmdHis'),
            'description' => 'Ticket de prueba creado el ' . date('Y-m-d H:i:s')
        ];
        
        $client = [
            'name' => 'Cliente de Prueba',
            'business_name' => 'Empresa de Prueba'
        ];
        
        echo "<p>Ticket: {$ticket['id']} - {$ticket['description']}</p>";
        echo "<p>Cliente: {$client['name']} - {$client['business_name']}</p>";
        
        // Crear instancia del notificador en modo debug
        $whatsapp = new WhatsAppNotifier(true);
        
        // Mostrar configuración
        $config = require_once 'config/whatsapp.php';
        echo "<h3>Configuración de WhatsApp:</h3>";
        echo "<pre>";
        print_r($config);
        echo "</pre>";
        
        // Enviar notificación
        echo "<h3>Resultado del envío:</h3>";
        $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
        
        if ($result) {
            echo "<p style='color: green; font-weight: bold;'>✅ Notificación enviada correctamente.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Error al enviar la notificación.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='test_whatsapp_direct.php'>Volver a la lista de técnicos</a></p>";
}

// Enlace para volver
echo "<p><a href='admin/dashboard.php'>Volver al Dashboard</a></p>";
?>
