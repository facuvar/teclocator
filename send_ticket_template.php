<?php
/**
 * Script para probar el envío de notificaciones de WhatsApp usando plantillas
 */

// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicializar variables
$message = '';
$status = '';
$response = '';
$phoneNumber = '';
$templateName = '';
$languageCode = '';

// Cargar configuración de WhatsApp
$configFile = __DIR__ . '/config/whatsapp.php';
if (!file_exists($configFile)) {
    die("Error: Archivo de configuración no encontrado en: {$configFile}");
}

$config = require $configFile;
if (!is_array($config)) {
    die("Error: El archivo de configuración no devolvió un array válido");
}

// Valores por defecto
$defaultTemplate = $config['default_template'] ?? 'nuevo_ticket';
$defaultLanguage = $config['default_language'] ?? 'es_AR';

// Incluir archivos necesarios
require_once __DIR__ . '/includes/WhatsAppNotifier.php';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $phoneNumber = $_POST['phone_number'] ?? '';
        $templateName = $_POST['template_name'] ?? $defaultTemplate;
        $languageCode = $_POST['language_code'] ?? $defaultLanguage;
        
        // Validar número de teléfono
        if (empty($phoneNumber)) {
            $status = 'error';
            $message = 'Por favor, ingresa un número de teléfono válido.';
        } else {
            // Crear instancia del notificador
            $notifier = new WhatsAppNotifier();
            
            // Registrar inicio de la solicitud
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Enviando solicitud...";
            error_log($logMessage);
            echo $logMessage . "<br>";
            
            // Enviar mensaje de prueba
            $result = $notifier->sendTemplateMessage($phoneNumber, $templateName, $languageCode);
            
            // Establecer mensaje de resultado
            if ($result) {
                $status = 'success';
                $message = 'Mensaje enviado correctamente a ' . $phoneNumber;
            } else {
                $status = 'error';
                $message = 'Error al enviar el mensaje. Revisa los logs para más detalles.';
            }
        }
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Error: ' . $e->getMessage();
        error_log('Error en WhatsApp: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Notificación por WhatsApp</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        .card { margin-top: 20px; }
        .logs { max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba de Notificación por WhatsApp</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                Enviar Mensaje de Prueba
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="phone_number">Número de Teléfono (con código de país):</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" 
                               value="<?php echo htmlspecialchars($phoneNumber); ?>" 
                               placeholder="Ejemplo: 5491112345678" required>
                        <small class="form-text text-muted">Ingresa el número completo con código de país (54 para Argentina)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="template_name">Nombre de la Plantilla:</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" 
                               value="<?php echo htmlspecialchars($templateName ?: $defaultTemplate); ?>">
                        <small class="form-text text-muted">Nombre exacto de la plantilla aprobada en WhatsApp Business</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="language_code">Código de Idioma:</label>
                        <input type="text" class="form-control" id="language_code" name="language_code" 
                               value="<?php echo htmlspecialchars($languageCode ?: $defaultLanguage); ?>">
                        <small class="form-text text-muted">Código de idioma de la plantilla (ej: es_AR)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enviar Notificación</button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($response)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    Respuesta de la API
                </div>
                <div class="card-body">
                    <pre><?php echo htmlspecialchars($response); ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header">
                Logs Recientes
            </div>
            <div class="card-body logs">
                <?php
                // Mostrar los últimos logs
                $logFile = ini_get('error_log');
                if (file_exists($logFile)) {
                    $logs = file_get_contents($logFile);
                    $logs = array_filter(explode("\n", $logs), function($line) {
                        return strpos($line, 'WhatsApp') !== false || strpos($line, 'Enviando solicitud') !== false;
                    });
                    $logs = array_slice($logs, -20); // Mostrar los últimos 20 logs relevantes
                    echo '<pre>' . htmlspecialchars(implode("\n", $logs)) . '</pre>';
                } else {
                    echo '<p>No se encontró el archivo de logs.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
