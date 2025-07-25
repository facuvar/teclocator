<?php
/**
 * Script para enviar un solo mensaje de WhatsApp y registrar todo el proceso
 * 
 * Este script envía un mensaje de WhatsApp al número especificado y registra
 * todo el proceso, incluyendo la respuesta completa de la API.
 */

// Habilitar visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log
$logFile = __DIR__ . '/whatsapp_test_' . date('Y-m-d_H-i-s') . '.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

logMessage("Iniciando prueba de envío de mensaje WhatsApp");

// Cargar configuración
logMessage("Cargando configuración...");
$config = require_once __DIR__ . '/config/whatsapp.php';
logMessage("Configuración cargada");

// Datos para la prueba
$phoneNumber = '+5491151109844'; // Número específico
$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber); // Eliminar caracteres no numéricos

logMessage("Datos de configuración:");
logMessage("- API URL: " . $config['api_url']);
logMessage("- Phone Number ID: " . $config['phone_number_id']);
logMessage("- Business Account ID: " . $config['business_account_id']);
logMessage("- Número destino: " . $phoneNumber);

// Crear URL de la API
$url = $config['api_url'] . $config['phone_number_id'] . '/messages';
logMessage("URL de la API: " . $url);

// Configuración de cabeceras
$headers = [
    'Authorization: Bearer ' . $config['token'],
    'Content-Type: application/json'
];
logMessage("Cabeceras configuradas");

// Mensaje a enviar
$messageText = "🔔 Este es un mensaje de prueba desde la API de WhatsApp Business. Hora: " . date('Y-m-d H:i:s');
logMessage("Mensaje a enviar: " . $messageText);

// Datos para el mensaje directo
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
logMessage("Iniciando solicitud cURL...");
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

logMessage("Log detallado de cURL: " . $verboseLog);

// Verificar si el mensaje fue aceptado
$responseData = json_decode($response, true);
if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['messages'][0]['id'])) {
    logMessage("ÉXITO: Mensaje enviado correctamente con ID: " . $responseData['messages'][0]['id']);
} else {
    logMessage("ERROR: No se pudo enviar el mensaje");
}

// Intentar con una plantilla hello_world
logMessage("\nIntentando enviar mensaje con plantilla hello_world...");

$templateData = [
    'messaging_product' => 'whatsapp',
    'recipient_type' => 'individual',
    'to' => $phoneNumber,
    'type' => 'template',
    'template' => [
        'name' => 'hello_world',
        'language' => [
            'code' => 'en_US'
        ]
    ]
];

logMessage("Datos de la plantilla (JSON): " . json_encode($templateData, JSON_PRETTY_PRINT));

// Iniciar cURL para plantilla
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($templateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Ejecutar la solicitud
logMessage("Enviando solicitud de plantilla...");
$templateResponse = curl_exec($ch);
$templateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$templateError = curl_error($ch);
curl_close($ch);

// Registrar resultados de la plantilla
logMessage("Código de respuesta HTTP (plantilla): " . $templateHttpCode);
if (!empty($templateError)) {
    logMessage("Error de cURL (plantilla): " . $templateError);
}

logMessage("Respuesta completa (plantilla): " . $templateResponse);

// Decodificar respuesta JSON para mejor legibilidad
if (json_decode($templateResponse) !== null) {
    logMessage("Respuesta decodificada (plantilla): " . json_encode(json_decode($templateResponse), JSON_PRETTY_PRINT));
}

// Verificar si el mensaje de plantilla fue aceptado
$templateResponseData = json_decode($templateResponse, true);
if ($templateHttpCode >= 200 && $templateHttpCode < 300 && isset($templateResponseData['messages'][0]['id'])) {
    logMessage("ÉXITO: Mensaje de plantilla enviado correctamente con ID: " . $templateResponseData['messages'][0]['id']);
} else {
    logMessage("ERROR: No se pudo enviar el mensaje de plantilla");
}

logMessage("Prueba finalizada. Archivo de log guardado en: " . $logFile);

// Mostrar enlace para ver el log
echo "<p>Prueba completada. <a href='whatsapp_test_" . date('Y-m-d_H-i-s') . ".log' target='_blank'>Ver archivo de log completo</a></p>";
