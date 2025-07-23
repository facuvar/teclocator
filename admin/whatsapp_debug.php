<?php
/**
 * Herramienta de diagnóstico para WhatsApp
 * Permite probar el envío de mensajes y ver logs detallados
 */
require_once '../includes/init.php';
require_once '../includes/WhatsAppNotifier.php';

// Requerir autenticación de administrador
$auth->requireAdmin();

// Obtener la conexión a la base de datos
$db = Database::getInstance();

// Inicializar variables
$technicians = [];
$selectedTechnicianId = $_POST['technician_id'] ?? null;
$selectedTechnician = null;
$message = null;
$messageType = null;
$debugOutput = '';
$logFiles = [];

// Obtener lista de técnicos
$technicians = $db->query("SELECT id, name, email, phone FROM users WHERE role = 'technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Si se seleccionó un técnico, obtener sus datos
if ($selectedTechnicianId) {
    $selectedTechnician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$selectedTechnicianId]);
}

// Procesar formulario de envío de mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if ($selectedTechnician && !empty($selectedTechnician['phone'])) {
        // Crear directorio de logs si no existe
        $logsDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        // Iniciar captura de salida para debug
        ob_start();
        
        // Crear instancia de WhatsAppNotifier con modo debug activado
        $whatsapp = new WhatsAppNotifier(true);
        
        // Enviar mensaje según el tipo seleccionado
        $messageType = $_POST['message_type'] ?? 'welcome';
        $result = false;
        
        if ($messageType === 'welcome') {
            echo "<h4>Enviando mensaje de bienvenida</h4>";
            $result = $whatsapp->sendWelcomeMessage($selectedTechnician);
        } elseif ($messageType === 'test') {
            echo "<h4>Enviando mensaje de prueba</h4>";
            // Formatear el número de teléfono
            $phone = preg_replace('/[^0-9]/', '', $selectedTechnician['phone']);
            if (substr($phone, 0, 2) !== '54') {
                $phone = '54' . $phone;
            }
            
            echo "Teléfono formateado: {$phone}<br>";
            
            // Enviar mensaje de texto directo
            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'text',
                'text' => [
                    'body' => "Este es un mensaje de prueba para {$selectedTechnician['name']}. Por favor, responde para confirmar que lo has recibido."
                ]
            ];
            
            $headers = [
                'Authorization: Bearer ' . $whatsapp->getToken(),
                'Content-Type: application/json'
            ];
            
            $url = $whatsapp->getApiUrl() . $whatsapp->getPhoneNumberId() . '/messages';
            echo "URL: {$url}<br>";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            
            echo "<h5>Información detallada de cURL:</h5>";
            echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
            
            curl_close($ch);
            
            $result = ($httpCode >= 200 && $httpCode < 300);
            
            echo "<h5>Respuesta HTTP {$httpCode}:</h5>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            
            if (!$result) {
                echo "<h5>Error de cURL:</h5>";
                echo "<pre>" . htmlspecialchars($curlError) . "</pre>";
            }
        }
        
        // Capturar la salida para mostrarla en la página
        $debugOutput = ob_get_clean();
        
        // Guardar la salida en un archivo de log
        $logFile = $logsDir . '/whatsapp_debug_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents($logFile, "Diagnóstico de WhatsApp\n");
        file_put_contents($logFile, "Técnico: {$selectedTechnician['name']} (ID: {$selectedTechnician['id']})\n", FILE_APPEND);
        file_put_contents($logFile, "Teléfono: {$selectedTechnician['phone']}\n", FILE_APPEND);
        file_put_contents($logFile, "Tipo de mensaje: {$messageType}\n", FILE_APPEND);
        file_put_contents($logFile, "Resultado: " . ($result ? "ÉXITO" : "FALLO") . "\n\n", FILE_APPEND);
        file_put_contents($logFile, $debugOutput, FILE_APPEND);
        
        // Mostrar mensaje según el resultado
        if ($result) {
            $message = "Mensaje enviado correctamente a {$selectedTechnician['name']}";
            $messageType = 'success';
        } else {
            $message = "Error al enviar el mensaje. Revisa los logs para más detalles.";
            $messageType = 'danger';
        }
    } else {
        $message = "El técnico seleccionado no tiene número de teléfono.";
        $messageType = 'warning';
    }
}

// Obtener archivos de log
$logsDir = dirname(__DIR__) . '/logs';
if (is_dir($logsDir)) {
    $files = scandir($logsDir);
    foreach ($files as $file) {
        if (strpos($file, 'whatsapp_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $logFiles[] = $file;
        }
    }
    // Ordenar por fecha (más recientes primero)
    rsort($logFiles);
}

// Cargar contenido de un archivo de log si se solicita
$selectedLog = $_GET['log'] ?? null;
$logContent = '';
if ($selectedLog && file_exists($logsDir . '/' . $selectedLog)) {
    $logContent = file_get_contents($logsDir . '/' . $selectedLog);
}

// Incluir el encabezado
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <h1>Diagnóstico de WhatsApp</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Enviar mensaje de prueba</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="technician_id" class="form-label">Seleccionar técnico</label>
                            <select class="form-select" id="technician_id" name="technician_id" required>
                                <option value="">Seleccionar técnico</option>
                                <?php foreach ($technicians as $technician): ?>
                                    <option value="<?php echo $technician['id']; ?>" <?php echo $selectedTechnicianId == $technician['id'] ? 'selected' : ''; ?>>
                                        <?php echo $technician['name']; ?> 
                                        <?php if (!empty($technician['phone'])): ?>
                                            (<?php echo $technician['phone']; ?>)
                                        <?php else: ?>
                                            (Sin teléfono)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo de mensaje</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="message_type" id="message_type_welcome" value="welcome" checked>
                                <label class="form-check-label" for="message_type_welcome">
                                    Mensaje de bienvenida
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="message_type" id="message_type_test" value="test">
                                <label class="form-check-label" for="message_type_test">
                                    Mensaje de prueba directo
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="send_message" class="btn btn-primary">Enviar mensaje</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($debugOutput)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Resultado de la prueba</h5>
                    </div>
                    <div class="card-body">
                        <div class="debug-output text-dark">
                            <?php echo $debugOutput; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Archivos de log</h5>
                </div>
                <div class="card-body">
                    <?php if (count($logFiles) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($logFiles as $file): ?>
                                <a href="?log=<?php echo urlencode($file); ?>" class="list-group-item list-group-item-action <?php echo $selectedLog === $file ? 'active' : ''; ?>">
                                    <?php echo $file; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No hay archivos de log disponibles</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($logContent)): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Contenido del log: <?php echo $selectedLog; ?></h5>
                        <a href="<?php echo BASE_URL; ?>logs/<?php echo urlencode($selectedLog); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Ver completo</a>
                    </div>
                    <div class="card-body">
                        <pre class="log-content text-dark"><?php echo htmlspecialchars($logContent); ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.debug-output {
    max-height: 400px;
    overflow-y: auto;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    font-size: 0.9rem;
    line-height: 1.5;
}
.log-content {
    max-height: 400px;
    overflow-y: auto;
    font-size: 0.85rem;
    white-space: pre-wrap;
    font-family: 'Courier New', monospace;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
.debug-output h4, .debug-output h5 {
    color: #0d6efd;
    margin-top: 15px;
    margin-bottom: 10px;
}
.debug-output pre {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    padding: 10px;
    border-radius: 4px;
}
</style>

<?php include_once '../templates/footer.php'; ?>
