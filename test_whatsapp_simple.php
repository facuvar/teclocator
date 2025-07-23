<?php
/**
 * Script simple para probar el envío de notificaciones por WhatsApp
 * Sin formulario, solo envía directamente a un número específico
 */

// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba Simple de WhatsApp</h1>";

try {
    // Cargar configuración directamente
    $configFile = __DIR__ . '/config/whatsapp.php';
    if (!file_exists($configFile)) {
        die("Error: Archivo de configuración no encontrado en: {$configFile}");
    }
    
    echo "<p>Archivo de configuración encontrado.</p>";
    
    $config = require $configFile;
    if (!is_array($config)) {
        die("Error: El archivo de configuración no devolvió un array válido");
    }
    
    echo "<p>Configuración cargada correctamente.</p>";
    echo "<pre>" . print_r($config, true) . "</pre>";
    
    // Incluir la clase WhatsAppNotifier
    require_once __DIR__ . '/includes/WhatsAppNotifier.php';
    
    echo "<p>Clase WhatsAppNotifier cargada.</p>";
    
    // Número de teléfono para prueba (reemplazar con un número real)
    $phoneNumber = '5491151109844'; // Reemplaza con tu número
    
    // Crear instancia del notificador
    $notifier = new WhatsAppNotifier();
    
    echo "<p>Instancia de WhatsAppNotifier creada.</p>";
    
    // Enviar mensaje de prueba
    echo "<p>Enviando mensaje a {$phoneNumber}...</p>";
    
    $result = $notifier->sendTemplateMessage(
        $phoneNumber, 
        $config['default_template'], 
        $config['default_language']
    );
    
    // Mostrar resultado
    if ($result) {
        echo "<p style='color:green;font-weight:bold;'>✅ Mensaje enviado correctamente.</p>";
    } else {
        echo "<p style='color:red;font-weight:bold;'>❌ Error al enviar el mensaje. Revisa los logs para más detalles.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;font-weight:bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

// Mostrar los últimos logs
echo "<h2>Logs Recientes</h2>";
$logFile = ini_get('error_log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $logs = array_filter(explode("\n", $logs), function($line) {
        return strpos($line, 'WhatsApp') !== false || strpos($line, 'Enviando') !== false;
    });
    $logs = array_slice($logs, -20); // Mostrar los últimos 20 logs relevantes
    echo '<pre>' . htmlspecialchars(implode("\n", $logs)) . '</pre>';
} else {
    echo '<p>No se encontró el archivo de logs.</p>';
}
