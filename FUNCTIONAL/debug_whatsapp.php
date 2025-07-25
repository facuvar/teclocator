<?php
/**
 * Script de depuración para notificaciones de WhatsApp
 */
// Mostrar todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once 'includes/init.php';
require_once 'includes/WhatsAppNotifier.php';

// Obtener conexión a la base de datos
$db = Database::getInstance();

// ID del técnico a probar
$technicianId = isset($_GET['tech_id']) ? (int)$_GET['tech_id'] : 5;

// Obtener datos del técnico
$technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);

echo "<h1>Depuración de Notificaciones WhatsApp</h1>";

// Verificar si el técnico existe
if (!$technician) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "Error: El técnico con ID {$technicianId} no existe.";
    echo "</div>";
    echo "<p>Prueba con otro ID: ";
    $techs = $db->select("SELECT id, name, phone FROM users WHERE role = 'technician'");
    foreach ($techs as $tech) {
        echo "<a href='?tech_id={$tech['id']}'>{$tech['name']} (ID: {$tech['id']})</a> | ";
    }
    echo "</p>";
    exit;
}

// Mostrar información del técnico
echo "<h2>Información del Técnico:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><td>{$technician['id']}</td></tr>";
echo "<tr><th>Nombre</th><td>{$technician['name']}</td></tr>";
echo "<tr><th>Email</th><td>{$technician['email']}</td></tr>";
echo "<tr><th>Teléfono</th><td>" . ($technician['phone'] ?: '<span style="color:red">No tiene teléfono registrado</span>') . "</td></tr>";
echo "<tr><th>Zona</th><td>{$technician['zone']}</td></tr>";
echo "</table>";

// Si no tiene teléfono, ofrecer la opción de agregar uno
if (empty($technician['phone'])) {
    echo "<h3>Agregar número de teléfono para pruebas</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='tech_id' value='{$technicianId}'>";
    echo "<input type='text' name='phone' placeholder='Ej: 5491112345678' required>";
    echo "<button type='submit' name='update_phone'>Actualizar teléfono</button>";
    echo "</form>";
}

// Procesar actualización de teléfono
if (isset($_POST['update_phone']) && !empty($_POST['phone'])) {
    $phone = $_POST['phone'];
    $db->update('users', ['phone' => $phone], 'id = ?', [$technicianId]);
    echo "<div style='color:green; padding:10px; border:1px solid green;'>";
    echo "Número de teléfono actualizado a: {$phone}";
    echo "</div>";
    
    // Recargar datos del técnico
    $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
}

// Obtener un ticket para pruebas
$ticket = $db->selectOne("SELECT * FROM tickets WHERE technician_id = ? ORDER BY id DESC LIMIT 1", [$technicianId]);

// Si no hay ticket para este técnico, mostrar mensaje
if (!$ticket) {
    echo "<div style='color:orange; padding:10px; border:1px solid orange;'>";
    echo "No hay tickets asignados a este técnico. Selecciona un ticket para pruebas:";
    echo "</div>";
    
    $tickets = $db->select("SELECT t.id, t.description, c.name as client_name FROM tickets t JOIN clients c ON t.client_id = c.id ORDER BY t.id DESC LIMIT 10");
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='tech_id' value='{$technicianId}'>";
    echo "<select name='ticket_id'>";
    foreach ($tickets as $t) {
        echo "<option value='{$t['id']}'>Ticket #{$t['id']} - {$t['client_name']} - " . substr($t['description'], 0, 50) . "...</option>";
    }
    echo "</select>";
    echo "<button type='submit' name='select_ticket'>Seleccionar ticket</button>";
    echo "</form>";
    
    // Si se seleccionó un ticket
    if (isset($_POST['select_ticket']) && !empty($_POST['ticket_id'])) {
        $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
    }
}

// Si tenemos un ticket, mostrar información y probar envío
if ($ticket) {
    // Obtener datos del cliente
    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticket['client_id']]);
    
    echo "<h2>Información del Ticket:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><td>{$ticket['id']}</td></tr>";
    echo "<tr><th>Cliente</th><td>{$client['name']} ({$client['business_name']})</td></tr>";
    echo "<tr><th>Dirección</th><td>{$client['address']}</td></tr>";
    echo "<tr><th>Descripción</th><td>{$ticket['description']}</td></tr>";
    echo "<tr><th>Estado</th><td>{$ticket['status']}</td></tr>";
    echo "<tr><th>Fecha</th><td>{$ticket['created_at']}</td></tr>";
    echo "</table>";
    
    // Formulario para enviar notificación de prueba
    echo "<h2>Enviar notificación de prueba:</h2>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='tech_id' value='{$technicianId}'>";
    echo "<input type='hidden' name='ticket_id' value='{$ticket['id']}'>";
    echo "<button type='submit' name='send_notification' style='padding:10px; background-color:#4CAF50; color:white; border:none; cursor:pointer;'>Enviar notificación por WhatsApp</button>";
    echo "</form>";
    
    // Procesar envío de notificación
    if (isset($_POST['send_notification'])) {
        // Verificar que el técnico tenga teléfono
        if (empty($technician['phone'])) {
            echo "<div style='color:red; padding:10px; border:1px solid red;'>";
            echo "Error: El técnico no tiene número de teléfono registrado.";
            echo "</div>";
        } else {
            echo "<h3>Resultado del envío:</h3>";
            
            try {
                // Crear instancia del notificador
                $whatsapp = new WhatsAppNotifier();
                
                // Intentar enviar la notificación
                $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
                
                if ($result) {
                    echo "<div style='color:green; padding:10px; border:1px solid green;'>";
                    echo "✅ Notificación enviada correctamente al número: {$technician['phone']}";
                    echo "</div>";
                } else {
                    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
                    echo "❌ Error al enviar la notificación. Revisa los logs del servidor para más detalles.";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div style='color:red; padding:10px; border:1px solid red;'>";
                echo "❌ Excepción: " . $e->getMessage();
                echo "</div>";
            }
            
            // Mostrar configuración de WhatsApp
            echo "<h3>Configuración de WhatsApp utilizada:</h3>";
            $config = require 'config/whatsapp.php';
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>API URL</th><td>{$config['api_url']}</td></tr>";
            echo "<tr><th>Token</th><td>" . substr($config['token'], 0, 10) . '...' . substr($config['token'], -10) . "</td></tr>";
            echo "<tr><th>Phone Number ID</th><td>{$config['phone_number_id']}</td></tr>";
            echo "<tr><th>Country Code</th><td>{$config['country_code']}</td></tr>";
            echo "<tr><th>Base URL</th><td>{$config['base_url']}</td></tr>";
            echo "</table>";
            
            // Mostrar el mensaje que se intentó enviar
            $ticketUrl = $config['base_url'] . 'technician/ticket-detail.php?id=' . $ticket['id'];
            $message = "🔔 *Nuevo ticket asignado* 🔔\n\n";
            $message .= "*Ticket #{$ticket['id']}*\n";
            $message .= "Cliente: {$client['name']} ({$client['business_name']})\n";
            $message .= "Dirección: {$client['address']}\n\n";
            $message .= "Descripción: {$ticket['description']}\n\n";
            $message .= "Para ver los detalles y comenzar la visita, haz clic en el siguiente enlace:\n";
            $message .= $ticketUrl;
            
            echo "<h3>Mensaje enviado:</h3>";
            echo "<pre style='background-color:#f5f5f5; padding:10px; border:1px solid #ddd;'>";
            echo htmlspecialchars($message);
            echo "</pre>";
        }
    }
} else {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "No hay tickets disponibles para realizar la prueba.";
    echo "</div>";
}

// Enlace para volver
echo "<p><a href='admin/tickets.php'>Volver a la gestión de tickets</a></p>";
