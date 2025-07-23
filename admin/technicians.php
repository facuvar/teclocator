<?php
/**
 * Technicians management page for administrators
 */
require_once '../includes/init.php';
require_once '../includes/WhatsAppNotifier.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$technicianId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update technician
    if (isset($_POST['save_technician'])) {
        // Debug - Mostrar datos del formulario
        error_log("Datos del formulario: " . print_r($_POST, true));
        
        $technicianData = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'zone' => $_POST['zone'] ?? '',
            'role' => 'technician'
        ];
        
        // Debug - Mostrar datos a guardar
        error_log("Datos a guardar: " . print_r($technicianData, true));
        
        // Add password for new technicians or if password is being changed
        if ((!isset($_POST['technician_id']) || empty($_POST['technician_id'])) || 
            (isset($_POST['password']) && !empty($_POST['password']))) {
            $technicianData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Validate required fields
        if (empty($technicianData['name']) || empty($technicianData['email']) || empty($technicianData['zone'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Check if email already exists (for new technicians or email changes)
            $existingUser = null;
            if (isset($_POST['technician_id']) && !empty($_POST['technician_id'])) {
                $existingUser = $db->selectOne(
                    "SELECT * FROM users WHERE email = ? AND id != ?", 
                    [$technicianData['email'], $_POST['technician_id']]
                );
            } else {
                $existingUser = $db->selectOne(
                    "SELECT * FROM users WHERE email = ?", 
                    [$technicianData['email']]
                );
            }
            
            if ($existingUser) {
                flash('El correo electrónico ya está en uso.', 'danger');
            } else {
                // Create or update
                if (isset($_POST['technician_id']) && !empty($_POST['technician_id'])) {
                    // Remove password from data if not provided
                    if (!isset($technicianData['password'])) {
                        unset($technicianData['password']);
                    }
                    
                    // Update existing technician
                    $db->update('users', $technicianData, 'id = ?', [$_POST['technician_id']]);
                    flash('Técnico actualizado correctamente.', 'success');
                } else {
                    // Create new technician
                    $technicianId = $db->insert('users', $technicianData);
                    flash('Técnico creado correctamente.', 'success');
                    
                    // Si el técnico tiene número de teléfono, enviar mensaje de bienvenida
                    if (!empty($technicianData['phone'])) {
                        // Obtener datos completos del técnico
                        $newTechnician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
                        
                        // Enviar mensaje de bienvenida por WhatsApp
                        $whatsapp = new WhatsAppNotifier();
                        $result = $whatsapp->sendWelcomeMessage($newTechnician);
                        
                        if ($result) {
                            flash('Se ha enviado un mensaje de bienvenida al técnico. Debe responder al mensaje para activar las notificaciones.', 'info');
                        } else {
                            flash('No se pudo enviar el mensaje de bienvenida. El técnico debe enviar un mensaje al número de WhatsApp Business para activar las notificaciones.', 'warning');
                        }
                    } else {
                        flash('El técnico no tiene número de teléfono registrado. No recibirá notificaciones por WhatsApp.', 'warning');
                    }
                }
                
                // Redirect to list view
                redirect('admin/technicians.php');
            }
        }
    }
    
    // Delete technician
    if (isset($_POST['delete_technician'])) {
        $technicianId = $_POST['technician_id'] ?? null;
        
        if ($technicianId) {
            // Check if technician has associated tickets
            $ticketsCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ?", 
                [$technicianId]
            )['count'];
            
            if ($ticketsCount > 0) {
                flash('No se puede eliminar el técnico porque tiene tickets asociados.', 'danger');
            } else {
                $db->delete('users', 'id = ?', [$technicianId]);
                flash('Técnico eliminado correctamente.', 'success');
            }
        }
        
        // Redirect to list view
        redirect('admin/technicians.php');
    }
    
    // Reenviar mensaje de WhatsApp
    if (isset($_POST['resend_whatsapp'])) {
        $technicianId = $_POST['technician_id'] ?? null;
        
        if ($technicianId) {
            // Obtener datos del técnico
            $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
            
            if ($technician && !empty($technician['phone'])) {
                // Crear directorio de logs si no existe
                $logsDir = dirname(__DIR__) . '/logs';
                if (!is_dir($logsDir)) {
                    mkdir($logsDir, 0755, true);
                }
                
                // Enviar mensaje de bienvenida por WhatsApp con modo debug activado
                $whatsapp = new WhatsAppNotifier(true); // Activar modo debug
                
                // Registrar intento en archivo de log específico
                $logFile = $logsDir . '/whatsapp_resend_' . date('Y-m-d_H-i-s') . '.log';
                file_put_contents($logFile, "Intento de reenvío de mensaje de WhatsApp\n");
                file_put_contents($logFile, "Técnico ID: {$technician['id']}\n", FILE_APPEND);
                file_put_contents($logFile, "Nombre: {$technician['name']}\n", FILE_APPEND);
                file_put_contents($logFile, "Teléfono: {$technician['phone']}\n", FILE_APPEND);
                
                // Intentar enviar el mensaje
                ob_start(); // Capturar salida para el log
                $result = $whatsapp->sendWelcomeMessage($technician);
                $output = ob_get_clean();
                
                // Guardar la salida en el log
                file_put_contents($logFile, "\nSalida del envío:\n{$output}\n", FILE_APPEND);
                file_put_contents($logFile, "Resultado: " . ($result ? "ÉXITO" : "FALLO") . "\n", FILE_APPEND);
                
                if ($result) {
                    flash('Se ha reenviado el mensaje de bienvenida al técnico. Debe responder al mensaje para activar las notificaciones.', 'success');
                } else {
                    flash('No se pudo reenviar el mensaje de bienvenida. Verifique que el número de teléfono sea correcto. Revise los logs para más detalles.', 'danger');
                }
            } else {
                flash('El técnico no tiene número de teléfono registrado. No se puede enviar el mensaje.', 'warning');
            }
            
            // Redirigir a la página de detalles del técnico
            redirect('admin/technicians.php?action=view&id=' . $technicianId);
        }
    }
}

// Get technician data for edit
$technician = null;
if (($action === 'edit' || $action === 'view') && $technicianId) {
    $technician = $db->selectOne("SELECT * FROM users WHERE id = ? AND role = 'technician'", [$technicianId]);
    if (!$technician) {
        flash('Técnico no encontrado.', 'danger');
        redirect('admin/technicians.php');
    }
}

// Get all technicians for list view
$technicians = [];
if ($action === 'list') {
    // Consulta explícita para asegurar que se obtengan todos los campos, incluida la zona
    $technicians = $db->query("SELECT id, name, email, phone, zone, role FROM users WHERE role = 'technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug - Mostrar información de zonas
    error_log("Técnicos encontrados: " . count($technicians));
    foreach ($technicians as $tech) {
        error_log("Técnico: " . $tech['name'] . ", Zona: " . ($tech['zone'] ?? 'No asignada'));
    }
}

// Page title
$pageTitle = 'Gestión de Técnicos'; // Valor por defecto
switch($action) {
    case 'create':
        $pageTitle = 'Crear Técnico';
        break;
    case 'edit':
        $pageTitle = 'Editar Técnico';
        break;
    case 'view':
        $pageTitle = 'Ver Técnico';
        break;
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Técnico
            </a>
        <?php else: ?>
            <a href="technicians.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Technicians List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($technicians) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Zona</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($technicians as $tech): ?>
                                    <tr>
                                        <td><?php echo $tech['id']; ?></td>
                                        <td><?php echo escape($tech['name']); ?></td>
                                        <td><?php echo escape($tech['email']); ?></td>
                                        <td><?php echo escape($tech['phone']); ?></td>
                                        <td><?php echo escape($tech['zone'] ?? 'No asignada'); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?action=view&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $tech['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $tech['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar el técnico <strong><?php echo escape($tech['name']); ?></strong>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="<?php echo BASE_URL; ?>admin/technicians.php">
                                                                <input type="hidden" name="technician_id" value="<?php echo $tech['id']; ?>">
                                                                <button type="submit" name="delete_technician" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay técnicos registrados</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Technician Form -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/technicians.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="technician_id" value="<?php echo $technician['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo $action === 'edit' ? escape($technician['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $action === 'edit' ? escape($technician['email']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo $action === 'edit' ? escape($technician['phone']) : ''; ?>">
                            <div class="form-text">
                                <i class="bi bi-whatsapp text-success"></i> Se utilizará para enviar notificaciones por WhatsApp. 
                                El técnico deberá responder al mensaje inicial para activar las notificaciones.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="zone" class="form-label">Zona *</label>
                            <select class="form-select" id="zone" name="zone" required>
                                <option value="">Seleccionar zona</option>
                                <option value="Zona 1" <?php echo ($action === 'edit' && $technician['zone'] === 'Zona 1') ? 'selected' : ''; ?>>Zona 1</option>
                                <option value="Zona 2" <?php echo ($action === 'edit' && $technician['zone'] === 'Zona 2') ? 'selected' : ''; ?>>Zona 2</option>
                                <option value="Zona 3" <?php echo ($action === 'edit' && $technician['zone'] === 'Zona 3') ? 'selected' : ''; ?>>Zona 3</option>
                                <option value="Zona 4" <?php echo ($action === 'edit' && $technician['zone'] === 'Zona 4') ? 'selected' : ''; ?>>Zona 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <?php echo $action === 'edit' ? 'Contraseña (dejar en blanco para mantener la actual)' : 'Contraseña *'; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               <?php echo $action === 'create' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/technicians.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" name="save_technician" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php elseif ($action === 'view' && $technician): ?>
        <!-- View Technician Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Información del Técnico</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo escape($technician['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo escape($technician['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo escape($technician['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Zona:</th>
                                <td><?php echo escape($technician['zone']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Registro:</th>
                                <td><?php echo date('d/m/Y', strtotime($technician['created_at'])); ?></td>
                            </tr>
                        </table>
                        
                        <div class="d-flex">
                            <a href="?action=edit&id=<?php echo $technician['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if (!empty($technician['phone'])): ?>
                                <form method="post" action="<?php echo BASE_URL; ?>admin/technicians.php" class="ms-2 d-inline">
                                    <input type="hidden" name="technician_id" value="<?php echo $technician['id']; ?>">
                                    <button type="submit" name="resend_whatsapp" class="btn btn-info">
                                        <i class="bi bi-whatsapp"></i> Reenviar Mensaje de WhatsApp
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Technician Tickets -->
                <?php
                $technicianTickets = $db->select("
                    SELECT t.id, t.description, t.status, t.created_at, c.name as client_name
                    FROM tickets t
                    JOIN clients c ON t.client_id = c.id
                    WHERE t.technician_id = ?
                    ORDER BY t.created_at DESC
                ", [$technician['id']]);
                ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Tickets Asignados</h5>
                        <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=create&technician_id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Asignar Ticket
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($technicianTickets) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($technicianTickets as $ticket): ?>
                                            <tr>
                                                <td><?php echo $ticket['id']; ?></td>
                                                <td><?php echo escape($ticket['client_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    
                                                    switch ($ticket['status']) {
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            $statusText = 'Pendiente';
                                                            break;
                                                        case 'in_progress':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'En Progreso';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'bg-success';
                                                            $statusText = 'Completado';
                                                            break;
                                                        case 'not_completed':
                                                            $statusClass = 'bg-danger';
                                                            $statusText = 'No Completado';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No hay tickets asignados a este técnico</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Technician Statistics -->
                <?php
                $completedCount = $db->selectOne(
                    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'completed'",
                    [$technician['id']]
                )['count'];
                
                $notCompletedCount = $db->selectOne(
                    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'not_completed'",
                    [$technician['id']]
                )['count'];
                
                $pendingCount = $db->selectOne(
                    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'pending'",
                    [$technician['id']]
                )['count'];
                
                $inProgressCount = $db->selectOne(
                    "SELECT COUNT(*) as count FROM tickets WHERE technician_id = ? AND status = 'in_progress'",
                    [$technician['id']]
                )['count'];
                
                $totalCount = $completedCount + $notCompletedCount + $pendingCount + $inProgressCount;
                $completionRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                ?>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark-surface-2">
                                    <div class="card-body text-center">
                                        <h3><?php echo $totalCount; ?></h3>
                                        <p>Total de Tickets</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark-success">
                                    <div class="card-body text-center">
                                        <h3><?php echo $completionRate; ?>%</h3>
                                        <p>Tasa de Finalización</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card bg-dark-warning">
                                    <div class="card-body text-center">
                                        <h4><?php echo $pendingCount; ?></h4>
                                        <p>Pendientes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-dark-info">
                                    <div class="card-body text-center">
                                        <h4><?php echo $inProgressCount; ?></h4>
                                        <p>En Progreso</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-dark-success">
                                    <div class="card-body text-center">
                                        <h4><?php echo $completedCount; ?></h4>
                                        <p>Completados</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-dark-danger">
                                    <div class="card-body text-center">
                                        <h4><?php echo $notCompletedCount; ?></h4>
                                        <p>No Completados</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>
