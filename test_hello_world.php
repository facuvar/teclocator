<?php
/**
 * Script para probar la plantilla hello_world de WhatsApp
 */
require_once 'includes/init.php';
$config = require_once 'config/whatsapp.php';

// Número de teléfono para la prueba
$phoneNumber = '5491151109844'; // Reemplaza con el número que quieras probar

// Formatear número
$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
if (substr($phoneNumber, 0, 2) !== '54') {
    $phoneNumber = '54' . $phoneNumber;
}

// URL de la API
$url = $config['api_url'] . $config['phone_number_id'] . '/messages';

// Datos para la plantilla hello_world
$data = [
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

// Cabeceras
$headers = [
    'Authorization: Bearer ' . $config['token'],
    'Content-Type: application/json'
];

// Iniciar cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Ejecutar la solicitud
echo "<h1>Probando plantilla hello_world</h1>";
echo "<p>Enviando a: {$phoneNumber}</p>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Resultado:</h2>";
echo "<p>Código HTTP: {$httpCode}</p>";

if (!empty($error)) {
    echo "<p>Error cURL: {$error}</p>";
}

echo "<h3>Respuesta completa:</h3>";
echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

// Verificar si fue exitoso
if ($httpCode >= 200 && $httpCode < 300) {
    echo "<p style='color:green;font-weight:bold;'>✅ Mensaje enviado correctamente</p>";
} else {
    echo "<p style='color:red;font-weight:bold;'>❌ Error al enviar el mensaje</p>";
}

// Mostrar opciones para verificar plantillas disponibles
echo "<h2>Verificar plantillas disponibles</h2>";
echo "<p>Para ver qué plantillas están disponibles en tu cuenta, puedes usar el siguiente endpoint:</p>";
echo "<pre>GET {$config['api_url']}{$config['business_account_id']}/message_templates</pre>";

// Botón para verificar plantillas
echo "<form method='post'>";
echo "<button type='submit' name='check_templates' style='padding:10px;background:#4CAF50;color:white;border:none;cursor:pointer;'>Verificar plantillas disponibles</button>";
echo "</form>";

// Verificar plantillas si se hizo clic en el botón
if (isset($_POST['check_templates'])) {
    $templatesUrl = $config['api_url'] . $config['business_account_id'] . "/message_templates";
    $ch = curl_init($templatesUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $templatesResponse = curl_exec($ch);
    $templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>Plantillas disponibles:</h3>";
    echo "<p>Código HTTP: {$templatesHttpCode}</p>";
    echo "<pre>" . json_encode(json_decode($templatesResponse), JSON_PRETTY_PRINT) . "</pre>";
}
