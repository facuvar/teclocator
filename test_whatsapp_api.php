<?php
/**
 * Script para probar la API de WhatsApp Business directamente
 */

// Cargar configuración
$config = require_once 'config/whatsapp.php';

// Datos para la prueba
$phoneNumber = '5491151109844'; // Reemplazar con el número de teléfono del técnico
$apiUrl = $config['api_url'];
$token = $config['token'];
$phoneNumberId = $config['phone_number_id'];

// Crear URL de la API
$url = $apiUrl . $phoneNumberId . '/messages';

// Mensaje directo (sin plantilla)
$directMessageData = [
    'messaging_product' => 'whatsapp',
    'recipient_type' => 'individual',
    'to' => $phoneNumber,
    'type' => 'text',
    'text' => [
        'body' => "🔔 Este es un mensaje de prueba directo desde la API de WhatsApp Business. Hora: " . date('Y-m-d H:i:s')
    ]
];

// Mensaje con plantilla (asumiendo que existe una plantilla 'sample_template')
$templateMessageData = [
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

// Configuración de cabeceras
$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];

// Función para enviar solicitud a la API
function sendApiRequest($url, $data, $headers) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Mostrar información de configuración
echo "<h1>Prueba de API de WhatsApp Business</h1>";
echo "<h2>Configuración:</h2>";
echo "<pre>";
echo "API URL: " . $apiUrl . "\n";
echo "Phone Number ID: " . $phoneNumberId . "\n";
echo "Token: " . substr($token, 0, 10) . "..." . substr($token, -10) . "\n";
echo "Número de destino: " . $phoneNumber . "\n";
echo "</pre>";

// Probar mensaje directo
echo "<h2>Prueba de mensaje directo:</h2>";
echo "<pre>";
echo "Datos enviados:\n";
echo json_encode($directMessageData, JSON_PRETTY_PRINT) . "\n\n";

$directResult = sendApiRequest($url, $directMessageData, $headers);

echo "Código de respuesta HTTP: " . $directResult['http_code'] . "\n";
echo "Respuesta:\n";
echo json_encode(json_decode($directResult['response']), JSON_PRETTY_PRINT) . "\n";

if (!empty($directResult['error'])) {
    echo "Error de cURL: " . $directResult['error'] . "\n";
}
echo "</pre>";

// Probar mensaje con plantilla
echo "<h2>Prueba de mensaje con plantilla:</h2>";
echo "<pre>";
echo "Datos enviados:\n";
echo json_encode($templateMessageData, JSON_PRETTY_PRINT) . "\n\n";

$templateResult = sendApiRequest($url, $templateMessageData, $headers);

echo "Código de respuesta HTTP: " . $templateResult['http_code'] . "\n";
echo "Respuesta:\n";
echo json_encode(json_decode($templateResult['response']), JSON_PRETTY_PRINT) . "\n";

if (!empty($templateResult['error'])) {
    echo "Error de cURL: " . $templateResult['error'] . "\n";
}
echo "</pre>";

// Verificar si el token es válido
echo "<h2>Verificación del token:</h2>";
$debugUrl = "https://graph.facebook.com/debug_token?input_token=" . $token . "&access_token=" . $token;

$ch = curl_init($debugUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$debugResponse = curl_exec($ch);
$debugHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "Código de respuesta HTTP: " . $debugHttpCode . "\n";
echo "Respuesta:\n";
echo json_encode(json_decode($debugResponse), JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Verificar plantillas disponibles
echo "<h2>Plantillas disponibles:</h2>";
$templatesUrl = $apiUrl . $config['business_account_id'] . "/message_templates";
$ch = curl_init($templatesUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$templatesResponse = curl_exec($ch);
$templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>";
echo "Código de respuesta HTTP: " . $templatesHttpCode . "\n";
echo "Respuesta:\n";
echo json_encode(json_decode($templatesResponse), JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Versión de línea de comandos para ejecutar desde la consola
if (php_sapi_name() === 'cli') {
    echo "Ejecutando prueba desde línea de comandos...\n";
    
    // Mensaje directo
    $directResult = sendApiRequest($url, $directMessageData, $headers);
    echo "Mensaje directo - Código HTTP: " . $directResult['http_code'] . "\n";
    echo "Respuesta: " . $directResult['response'] . "\n";
    
    // Mensaje con plantilla
    $templateResult = sendApiRequest($url, $templateMessageData, $headers);
    echo "Mensaje con plantilla - Código HTTP: " . $templateResult['http_code'] . "\n";
    echo "Respuesta: " . $templateResult['response'] . "\n";
}
