<?php
/**
 * Script para probar la API de WhatsApp Business desde la línea de comandos
 */

// Cargar configuración
$config = require_once __DIR__ . '/config/whatsapp.php';

// Datos para la prueba
$phoneNumber = '5491151109844'; // Número del técnico
$apiUrl = $config['api_url'];
$token = $config['token'];
$phoneNumberId = $config['phone_number_id'];
$businessAccountId = $config['business_account_id'];

// Crear URL de la API
$url = $apiUrl . $phoneNumberId . '/messages';

echo "=== PRUEBA DE API WHATSAPP BUSINESS ===\n\n";
echo "Configuración:\n";
echo "- API URL: {$apiUrl}\n";
echo "- Phone Number ID: {$phoneNumberId}\n";
echo "- Business Account ID: {$businessAccountId}\n";
echo "- Número destino: {$phoneNumber}\n\n";

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

// Configuración de cabeceras
$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];

// 1. Probar mensaje directo
echo "PRUEBA 1: Envío de mensaje directo\n";
$directMessageData = [
    'messaging_product' => 'whatsapp',
    'recipient_type' => 'individual',
    'to' => $phoneNumber,
    'type' => 'text',
    'text' => [
        'body' => "🔔 Este es un mensaje de prueba directo desde la API de WhatsApp Business. Hora: " . date('Y-m-d H:i:s')
    ]
];

$directResult = sendApiRequest($url, $directMessageData, $headers);
echo "Código HTTP: " . $directResult['http_code'] . "\n";
echo "Respuesta: " . $directResult['response'] . "\n";
if (!empty($directResult['error'])) {
    echo "Error: " . $directResult['error'] . "\n";
}
echo "\n";

// 2. Verificar plantillas disponibles
echo "PRUEBA 2: Verificar plantillas disponibles\n";
$templatesUrl = $apiUrl . $businessAccountId . "/message_templates";
$ch = curl_init($templatesUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$templatesResponse = curl_exec($ch);
$templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$templatesError = curl_error($ch);
curl_close($ch);

echo "Código HTTP: " . $templatesHttpCode . "\n";
echo "Respuesta: " . $templatesResponse . "\n";
if (!empty($templatesError)) {
    echo "Error: " . $templatesError . "\n";
}
echo "\n";

// 3. Probar mensaje con plantilla hello_world (si existe)
echo "PRUEBA 3: Envío de mensaje con plantilla hello_world\n";
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

$templateResult = sendApiRequest($url, $templateMessageData, $headers);
echo "Código HTTP: " . $templateResult['http_code'] . "\n";
echo "Respuesta: " . $templateResult['response'] . "\n";
if (!empty($templateResult['error'])) {
    echo "Error: " . $templateResult['error'] . "\n";
}
echo "\n";

echo "=== FIN DE LA PRUEBA ===\n";
