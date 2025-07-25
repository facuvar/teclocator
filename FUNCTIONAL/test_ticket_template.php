<?php
/**
 * Script para probar la plantilla de notificación de tickets de WhatsApp
 */
require_once 'includes/init.php';
require_once 'includes/WhatsAppNotifier.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Obtener la conexión a la base de datos
$db = Database::getInstance();

// Cargar configuración de WhatsApp
$config = require_once 'config/whatsapp.php';

// Inicializar variables
$result = null;
$error = null;
$phoneNumber = $_POST['phone_number'] ?? '';
$templateName = $_POST['template_name'] ?? $config['default_template'];
$templateLanguage = $_POST['template_language'] ?? $config['default_language'];
$ticketId = $_POST['ticket_id'] ?? '123';
$clientName = $_POST['client_name'] ?? 'Cliente de Prueba';
$clientBusiness = $_POST['client_business'] ?? 'Empresa de Prueba';
$ticketDescription = $_POST['ticket_description'] ?? 'Descripción de prueba para el ticket';
$ticketUrl = $_POST['ticket_url'] ?? $config['base_url'] . 'technician/tickets.php?action=view&id=123';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    try {
        // Verificar número de teléfono
        if (empty($phoneNumber)) {
            throw new Exception("Debe ingresar un número de teléfono para enviar la notificación de prueba.");
        }
        
        // Crear instancia del notificador en modo debug
        $whatsapp = new WhatsAppNotifier(true);
        
        // Formatear el número de teléfono
        $formattedPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($formattedPhone, 0, strlen($config['country_code'])) !== $config['country_code']) {
            $formattedPhone = $config['country_code'] . $formattedPhone;
        }
        
        // Preparar parámetros para la plantilla
        $params = [
            [
                'type' => 'text',
                'text' => $ticketId
            ],
            [
                'type' => 'text',
                'text' => $clientName . " - " . $clientBusiness
            ],
            [
                'type' => 'text',
                'text' => substr($ticketDescription, 0, 150) . (strlen($ticketDescription) > 150 ? '...' : '')
            ],
            [
                'type' => 'text',
                'text' => $ticketUrl
            ]
        ];
        
        // Crear archivo de log
        $timestamp = date('Y-m-d_H-i-s');
        $logFile = "template_test_{$timestamp}.log";
        $logPath = __DIR__ . '/' . $logFile;
        
        // Iniciar el log
        file_put_contents($logPath, "[{$timestamp}] Iniciando prueba de plantilla WhatsApp\n");
        file_put_contents($logPath, "Teléfono: {$formattedPhone}\n", FILE_APPEND);
        file_put_contents($logPath, "Plantilla: {$templateName}\n", FILE_APPEND);
        file_put_contents($logPath, "Idioma: {$templateLanguage}\n", FILE_APPEND);
        file_put_contents($logPath, "Parámetros:\n" . print_r($params, true), FILE_APPEND);
        
        // Enviar mensaje de prueba
        ob_start();
        $result = $whatsapp->sendTemplateMessageWithParams($formattedPhone, $templateName, $params, $templateLanguage);
        $output = ob_get_clean();
        
        // Guardar salida en el log
        file_put_contents($logPath, "\nSalida de depuración:\n{$output}\n", FILE_APPEND);
        file_put_contents($logPath, "\nResultado del envío: " . ($result ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Título de la página
$pageTitle = 'Prueba de Plantilla de WhatsApp';
include_once 'templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Enviar Prueba de Plantilla</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($result)): ?>
                        <div class="alert <?php echo $result ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($result): ?>
                                <i class="bi bi-check-circle"></i> Plantilla enviada correctamente.
                            <?php else: ?>
                                <i class="bi bi-x-circle"></i> Error al enviar la plantilla. Revise los logs para más detalles.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Número de Teléfono:</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($phoneNumber); ?>" required>
                            <small class="form-text text-muted">Ingrese el número con o sin código de país (ej: 1151109844 o 541151109844)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="template_name" class="form-label">Nombre de la Plantilla:</label>
                            <input type="text" name="template_name" id="template_name" class="form-control" value="<?php echo htmlspecialchars($templateName); ?>" required>
                            <small class="form-text text-muted">Nombre exacto de la plantilla aprobada en WhatsApp Business</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="template_language" class="form-label">Idioma de la Plantilla:</label>
                            <input type="text" name="template_language" id="template_language" class="form-control" value="<?php echo htmlspecialchars($templateLanguage); ?>" required>
                            <small class="form-text text-muted">Código de idioma (ej: es_AR, es, en_US)</small>
                        </div>
                        
                        <h6 class="mt-4">Parámetros de la Plantilla:</h6>
                        
                        <div class="mb-3">
                            <label for="ticket_id" class="form-label">ID del Ticket:</label>
                            <input type="text" name="ticket_id" id="ticket_id" class="form-control" value="<?php echo htmlspecialchars($ticketId); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_name" class="form-label">Nombre del Cliente:</label>
                            <input type="text" name="client_name" id="client_name" class="form-control" value="<?php echo htmlspecialchars($clientName); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="client_business" class="form-label">Empresa del Cliente:</label>
                            <input type="text" name="client_business" id="client_business" class="form-control" value="<?php echo htmlspecialchars($clientBusiness); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="ticket_description" class="form-label">Descripción del Ticket:</label>
                            <textarea name="ticket_description" id="ticket_description" class="form-control" rows="3"><?php echo htmlspecialchars($ticketDescription); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ticket_url" class="form-label">URL del Ticket:</label>
                            <input type="text" name="ticket_url" id="ticket_url" class="form-control" value="<?php echo htmlspecialchars($ticketUrl); ?>">
                        </div>
                        
                        <button type="submit" name="send_test" class="btn btn-primary">
                            <i class="bi bi-send"></i> Enviar Prueba
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Información de la Plantilla</h5>
                </div>
                <div class="card-body">
                    <h6>Plantilla "nuevo_ticket"</h6>
                    <p>Esta plantilla debe estar configurada en WhatsApp Business con el siguiente formato:</p>
                    
                    <div class="p-3 border rounded bg-light">
                        <p>🔔 <strong>Nuevo Ticket #{{1}}</strong></p>
                        <p>Cliente: {{2}}</p>
                        <p>Descripción: {{3}}</p>
                        <p>Ver detalles: {{4}}</p>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> Asegúrese de que la plantilla esté aprobada en WhatsApp Business y que tenga exactamente 4 parámetros de texto en el orden correcto.
                    </div>
                    
                    <h6 class="mt-4">Pasos para crear una plantilla en WhatsApp Business:</h6>
                    <ol>
                        <li>Acceda al <a href="https://business.facebook.com/" target="_blank">Facebook Business Manager</a></li>
                        <li>Vaya a "Todos los recursos" y seleccione su cuenta de WhatsApp Business</li>
                        <li>Navegue a "Herramientas de WhatsApp" > "Administrar plantillas"</li>
                        <li>Haga clic en "Crear plantilla"</li>
                        <li>Seleccione la categoría "Utilidad" y el idioma "Español (Argentina)"</li>
                        <li>En el nombre, escriba "nuevo_ticket" (sin espacios ni mayúsculas)</li>
                        <li>En el cuerpo del mensaje, escriba el texto mostrado arriba con los parámetros {{1}}, {{2}}, etc.</li>
                        <li>Envíe la plantilla para aprobación</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
