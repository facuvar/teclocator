<?php
/**
 * Herramienta de diagnóstico para notificaciones de WhatsApp
 * 
 * Este script permite probar el envío de notificaciones de WhatsApp a técnicos
 * y registra información detallada para diagnosticar problemas.
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

// Obtener técnicos disponibles
$technicians = $db->select("SELECT id, name, email, phone FROM users WHERE role = 'technician' ORDER BY name");

// Obtener tickets recientes
$tickets = $db->select("
    SELECT t.*, c.name as client_name, c.business_name, u.name as technician_name 
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");

// Inicializar variables
$result = null;
$logFile = null;
$selectedTechnicianId = $_POST['technician_id'] ?? null;
$selectedTicketId = $_POST['ticket_id'] ?? null;
$useCustomTemplate = isset($_POST['use_custom_template']) && $_POST['use_custom_template'] == 1;
$customTemplate = $_POST['custom_template'] ?? 'nuevo_ticket';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    try {
        // Verificar que se hayan seleccionado técnico y ticket
        if (empty($selectedTechnicianId) || empty($selectedTicketId)) {
            throw new Exception("Debe seleccionar un técnico y un ticket para enviar la notificación de prueba.");
        }
        
        // Obtener datos del técnico, ticket y cliente
        $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$selectedTechnicianId]);
        $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$selectedTicketId]);
        $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticket['client_id']]);
        
        if (!$technician || !$ticket || !$client) {
            throw new Exception("No se pudieron obtener todos los datos necesarios para la notificación.");
        }
        
        // Verificar que el técnico tenga número de teléfono
        if (empty($technician['phone'])) {
            throw new Exception("El técnico seleccionado no tiene número de teléfono registrado.");
        }
        
        // Crear archivo de log
        $timestamp = date('Y-m-d_H-i-s');
        $logFile = "whatsapp_debug_{$timestamp}.log";
        $logPath = __DIR__ . '/' . $logFile;
        
        // Iniciar el log
        file_put_contents($logPath, "[{$timestamp}] Iniciando prueba de notificación WhatsApp\n");
        file_put_contents($logPath, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
        file_put_contents($logPath, "Ticket: #{$ticket['id']} - {$ticket['description']}\n", FILE_APPEND);
        file_put_contents($logPath, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);
        
        // Cargar configuración de WhatsApp
        $config = require_once 'config/whatsapp.php';
        file_put_contents($logPath, "\nConfiguración de WhatsApp:\n" . print_r($config, true), FILE_APPEND);
        
        // Crear instancia del notificador
        $whatsapp = new WhatsAppNotifier();
        
        // Enviar notificación
        if ($useCustomTemplate) {
            file_put_contents($logPath, "\nUsando plantilla personalizada: {$customTemplate}\n", FILE_APPEND);
            $result = $whatsapp->sendTicketNotification($technician, $ticket, $client, $customTemplate);
        } else {
            file_put_contents($logPath, "\nUsando plantilla por defecto\n", FILE_APPEND);
            $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
        }
        
        // Registrar resultado
        file_put_contents($logPath, "\nResultado del envío: " . ($result ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
        
        // Verificar si hay errores en el log de PHP
        $phpErrors = error_get_last();
        if ($phpErrors) {
            file_put_contents($logPath, "\nErrores de PHP detectados:\n" . print_r($phpErrors, true), FILE_APPEND);
        }
        
        // Finalizar log
        file_put_contents($logPath, "\n[{$timestamp}] Prueba finalizada\n", FILE_APPEND);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        if ($logFile) {
            file_put_contents(__DIR__ . '/' . $logFile, "\nERROR: {$error}\n", FILE_APPEND);
        }
    }
}

// Cargar configuración actual
$whatsappConfig = require_once 'config/whatsapp.php';

// Título de la página
$pageTitle = 'Diagnóstico de Notificaciones WhatsApp';
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
                    <h5 class="card-title">Enviar Notificación de Prueba</h5>
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
                                <i class="bi bi-check-circle"></i> Notificación enviada correctamente.
                            <?php else: ?>
                                <i class="bi bi-x-circle"></i> Error al enviar la notificación. Revise el archivo de log para más detalles.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="technician_id" class="form-label">Técnico:</label>
                            <select name="technician_id" id="technician_id" class="form-select" required>
                                <option value="">Seleccione un técnico</option>
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
                            <label for="ticket_id" class="form-label">Ticket:</label>
                            <select name="ticket_id" id="ticket_id" class="form-select" required>
                                <option value="">Seleccione un ticket</option>
                                <?php foreach ($tickets as $ticket): ?>
                                    <option value="<?php echo $ticket['id']; ?>" <?php echo $selectedTicketId == $ticket['id'] ? 'selected' : ''; ?>>
                                        #<?php echo $ticket['id']; ?> - <?php echo $ticket['client_name']; ?> - 
                                        <?php echo substr($ticket['description'], 0, 50) . (strlen($ticket['description']) > 50 ? '...' : ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="use_custom_template" id="use_custom_template" value="1" <?php echo $useCustomTemplate ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="use_custom_template">
                                    Usar plantilla personalizada
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="custom_template_container" style="<?php echo $useCustomTemplate ? '' : 'display: none;'; ?>">
                            <label for="custom_template" class="form-label">Nombre de la plantilla:</label>
                            <input type="text" name="custom_template" id="custom_template" class="form-control" value="<?php echo $customTemplate; ?>">
                            <small class="form-text text-muted">Debe ser una plantilla aprobada en WhatsApp Business</small>
                        </div>
                        
                        <button type="submit" name="send_test" class="btn btn-primary">
                            <i class="bi bi-send"></i> Enviar Notificación de Prueba
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($logFile): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Resultado de la Prueba</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            <a href="<?php echo $logFile; ?>" class="btn btn-outline-info" target="_blank">
                                <i class="bi bi-file-text"></i> Ver archivo de log completo
                            </a>
                        </p>
                        
                        <div class="mt-3">
                            <h6>Contenido del log:</h6>
                            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/' . $logFile)); ?></pre>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Configuración Actual de WhatsApp</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>API URL:</th>
                                    <td><?php echo $whatsappConfig['api_url']; ?></td>
                                </tr>
                                <tr>
                                    <th>Token:</th>
                                    <td>
                                        <?php echo substr($whatsappConfig['token'], 0, 10) . '...' . substr($whatsappConfig['token'], -10); ?>
                                        <button class="btn btn-sm btn-outline-secondary" id="show-token">Mostrar</button>
                                        <span id="full-token" style="display: none;"><?php echo $whatsappConfig['token']; ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone Number ID:</th>
                                    <td><?php echo $whatsappConfig['phone_number_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Business Account ID:</th>
                                    <td><?php echo $whatsappConfig['business_account_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Account ID:</th>
                                    <td><?php echo $whatsappConfig['account_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Código de País:</th>
                                    <td><?php echo $whatsappConfig['country_code']; ?></td>
                                </tr>
                                <tr>
                                    <th>URL Base:</th>
                                    <td><?php echo $whatsappConfig['base_url']; ?></td>
                                </tr>
                                <tr>
                                    <th>Plantilla por Defecto:</th>
                                    <td><?php echo $whatsappConfig['default_template']; ?></td>
                                </tr>
                                <tr>
                                    <th>Idioma por Defecto:</th>
                                    <td><?php echo $whatsappConfig['default_language']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Instrucciones</h5>
                </div>
                <div class="card-body">
                    <h6>Pasos para diagnosticar problemas:</h6>
                    <ol>
                        <li>Seleccione un técnico que tenga un número de teléfono válido registrado.</li>
                        <li>Seleccione un ticket existente para la notificación de prueba.</li>
                        <li>Haga clic en "Enviar Notificación de Prueba".</li>
                        <li>Revise el archivo de log generado para identificar posibles errores.</li>
                    </ol>
                    
                    <h6>Posibles problemas y soluciones:</h6>
                    <ul>
                        <li><strong>Error de autenticación:</strong> Verifique que el token de acceso sea válido y no haya expirado.</li>
                        <li><strong>Error de plantilla:</strong> Asegúrese de que la plantilla esté aprobada en WhatsApp Business y el nombre sea correcto.</li>
                        <li><strong>Error de formato de número:</strong> Verifique que el número de teléfono del técnico tenga el formato correcto (incluido el código de país).</li>
                        <li><strong>Error de conexión:</strong> Verifique la conectividad a internet y que la API de WhatsApp esté disponible.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campo de plantilla personalizada
    const useCustomTemplate = document.getElementById('use_custom_template');
    const customTemplateContainer = document.getElementById('custom_template_container');
    
    useCustomTemplate.addEventListener('change', function() {
        customTemplateContainer.style.display = this.checked ? 'block' : 'none';
    });
    
    // Mostrar/ocultar token completo
    const showTokenBtn = document.getElementById('show-token');
    const fullToken = document.getElementById('full-token');
    
    showTokenBtn.addEventListener('click', function() {
        if (fullToken.style.display === 'none') {
            fullToken.style.display = 'inline';
            showTokenBtn.textContent = 'Ocultar';
        } else {
            fullToken.style.display = 'none';
            showTokenBtn.textContent = 'Mostrar';
        }
    });
});
</script>

<?php include_once 'templates/footer.php'; ?>
