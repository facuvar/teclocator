<?php
/**
 * Script para enviar un mensaje personalizado con datos del ticket ID 10
 * 
 * Este script aprovecha la ventana de conversación activa para enviar un mensaje
 * personalizado con los datos específicos del ticket.
 */

// Habilitar visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log
$logFile = __DIR__ . '/ticket_message_' . date('Y-m-d_H-i-s') . '.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

logMessage("Iniciando envío de mensaje personalizado para ticket ID 10");

// Cargar configuración
require_once __DIR__ . '/includes/init.php';
$config = require_once __DIR__ . '/config/whatsapp.php';

// Obtener datos del ticket ID 10
$db = Database::getInstance();
$ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [10]);

if (!$ticket) {
    logMessage("ERROR: No se encontró el ticket con ID 10");
    die("No se encontró el ticket con ID 10");
}

// Obtener datos del cliente
$client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticket['client_id']]);
if (!$client) {
    logMessage("ERROR: No se encontró el cliente asociado al ticket");
    die("No se encontró el cliente asociado al ticket");
}

// Obtener datos del técnico
$technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticket['technician_id']]);
if (!$technician) {
    logMessage("ERROR: No se encontró el técnico asociado al ticket");
    die("No se encontró el técnico asociado al ticket");
}

// Mostrar información obtenida
logMessage("Información del ticket:");
logMessage("- ID: " . $ticket['id']);
logMessage("- Cliente: " . $client['name'] . " (" . $client['business_name'] . ")");
logMessage("- Dirección: " . $client['address']);
logMessage("- Descripción: " . $ticket['description']);
logMessage("- Estado: " . $ticket['status']);
logMessage("- Técnico: " . $technician['name'] . " (" . $technician['phone'] . ")");

// Número de teléfono del técnico
$phoneNumber = $technician['phone'];
$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber); // Eliminar caracteres no numéricos

// Verificar que el número tenga el formato correcto
if (substr($phoneNumber, 0, 2) !== '54') {
    $phoneNumber = '54' . $phoneNumber;
}

logMessage("Número de teléfono formateado: " . $phoneNumber);

// Crear URL de la API
$url = $config['api_url'] . $config['phone_number_id'] . '/messages';

// Configuración de cabeceras
$headers = [
    'Authorization: Bearer ' . $config['token'],
    'Content-Type: application/json'
];

// Crear el enlace al panel del técnico
$ticketUrl = $config['base_url'] . 'technician/ticket-detail.php?id=' . $ticket['id'];

// Crear mensaje personalizado con datos del ticket
$messageText = "🔔 *Nuevo ticket asignado* 🔔\n\n";
$messageText .= "*Ticket #" . $ticket['id'] . "*\n";
$messageText .= "Cliente: " . $client['name'] . " (" . $client['business_name'] . ")\n";
$messageText .= "Dirección: " . $client['address'] . "\n\n";
$messageText .= "Descripción: " . $ticket['description'] . "\n\n";
$messageText .= "Para ver los detalles y comenzar la visita, haz clic en el siguiente enlace:\n";
$messageText .= $ticketUrl;

logMessage("Mensaje a enviar:\n" . $messageText);

// Datos para el mensaje
$messageData = [
    'messaging_product' => 'whatsapp',
    'recipient_type' => 'individual',
    'to' => $phoneNumber,
    'type' => 'text',
    'text' => [
        'body' => $messageText
    ]
];

logMessage("Datos del mensaje (JSON): " . json_encode($messageData, JSON_PRETTY_PRINT));

// Iniciar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messageData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capturar información detallada de cURL
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Ejecutar la solicitud
logMessage("Enviando solicitud...");
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Obtener información detallada de cURL
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

// Registrar resultados
logMessage("Código de respuesta HTTP: " . $httpCode);
if (!empty($error)) {
    logMessage("Error de cURL: " . $error);
}

logMessage("Respuesta completa: " . $response);

// Decodificar respuesta JSON para mejor legibilidad
if (json_decode($response) !== null) {
    logMessage("Respuesta decodificada: " . json_encode(json_decode($response), JSON_PRETTY_PRINT));
}

// Verificar si el mensaje fue aceptado
$responseData = json_decode($response, true);
if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['messages'][0]['id'])) {
    logMessage("ÉXITO: Mensaje personalizado enviado correctamente con ID: " . $responseData['messages'][0]['id']);
    
    echo "<div style='background-color:#dff0d8; color:#3c763d; padding:15px; border-radius:5px; margin:20px 0;'>";
    echo "<h2>✅ Mensaje enviado correctamente</h2>";
    echo "<p>Se ha enviado un mensaje personalizado con los datos del ticket ID 10 al número {$phoneNumber}.</p>";
    echo "<p>ID del mensaje: " . $responseData['messages'][0]['id'] . "</p>";
    echo "</div>";
} else {
    logMessage("ERROR: No se pudo enviar el mensaje personalizado");
    
    echo "<div style='background-color:#f2dede; color:#a94442; padding:15px; border-radius:5px; margin:20px 0;'>";
    echo "<h2>❌ Error al enviar el mensaje</h2>";
    echo "<p>No se pudo enviar el mensaje personalizado al número {$phoneNumber}.</p>";
    echo "<p>Código de respuesta: {$httpCode}</p>";
    echo "<p>Consulta el archivo de log para más detalles.</p>";
    echo "</div>";
}

logMessage("Proceso finalizado. Archivo de log guardado en: " . $logFile);

// Mostrar enlace para ver el log
echo "<p><a href='" . basename($logFile) . "' target='_blank'>Ver archivo de log completo</a></p>";
echo "<p><a href='admin/tickets.php'>Volver a la gestión de tickets</a></p>";
