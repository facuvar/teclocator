<?php
/**
 * Herramienta simple para probar notificaciones de WhatsApp
 */
require_once 'includes/init.php';
require_once 'includes/WhatsAppNotifier.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit;
}

// Obtener la conexión a la base de datos
$db = Database::getInstance();

// Obtener técnicos disponibles
$technicians = $db->select("SELECT id, name, email, phone FROM users WHERE role = 'technician' ORDER BY name");

// Inicializar variables
$result = null;
$error = null;
$logContent = null;
$selectedTechnicianId = $_POST['technician_id'] ?? null;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    try {
        // Verificar que se haya seleccionado un técnico
        if (empty($selectedTechnicianId)) {
            throw new Exception("Debe seleccionar un técnico para enviar la notificación de prueba.");
        }
        
        // Obtener datos del técnico
        $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$selectedTechnicianId]);
        
        if (!$technician) {
            throw new Exception("No se pudo encontrar el técnico seleccionado.");
        }
        
        // Verificar que el técnico tenga número de teléfono
        if (empty($technician['phone'])) {
            throw new Exception("El técnico seleccionado no tiene número de teléfono registrado.");
        }
        
        // Crear datos de prueba para el ticket y cliente
        $ticket = [
            'id' => 'TEST-' . date('YmdHis'),
            'description' => 'Ticket de prueba creado el ' . date('Y-m-d H:i:s')
        ];
        
        $client = [
            'name' => 'Cliente de Prueba',
            'business_name' => 'Empresa de Prueba'
        ];
        
        // Iniciar captura de salida para el log
        ob_start();
        
        // Crear instancia del notificador en modo debug
        $whatsapp = new WhatsAppNotifier(true);
        
        // Enviar notificación
        $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
        
        // Capturar la salida
        $logContent = ob_get_clean();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}

// Título de la página
$pageTitle = 'Prueba Simple de WhatsApp';
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
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Enviar Notificación de Prueba</h5>
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
                                <i class="bi bi-x-circle"></i> Error al enviar la notificación.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="technician_id" class="form-label">Seleccione un Técnico:</label>
                            <select name="technician_id" id="technician_id" class="form-select" required>
                                <option value="">-- Seleccione un técnico --</option>
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
                        
                        <button type="submit" name="send_test" class="btn btn-primary">
                            <i class="bi bi-send"></i> Enviar Notificación de Prueba
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <?php if ($logContent): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Resultado de la Prueba</h5>
                </div>
                <div class="card-body">
                    <h6>Detalles de la operación:</h6>
                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><?php echo htmlspecialchars($logContent); ?></pre>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Instrucciones</h5>
                </div>
                <div class="card-body">
                    <p>Esta herramienta le permite probar el envío de notificaciones de WhatsApp a los técnicos de forma rápida y sencilla.</p>
                    
                    <h6>Pasos:</h6>
                    <ol>
                        <li>Seleccione un técnico de la lista (asegúrese de que tenga un número de teléfono registrado)</li>
                        <li>Haga clic en "Enviar Notificación de Prueba"</li>
                        <li>Revise el resultado que aparecerá en esta sección</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Si la notificación no llega al técnico, revise los detalles del error que aparecerán en esta sección después de enviar la prueba.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
