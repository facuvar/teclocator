<?php
/**
 * Tickets management page for administrators
 */
require_once '../includes/init.php';
require_once '../includes/WhatsAppNotifier.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$ticketId = $_GET['id'] ?? null;
$clientId = $_GET['client_id'] ?? null;
$technicianId = $_GET['technician_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update ticket
    if (isset($_POST['save_ticket'])) {
        $ticketData = [
            'client_id' => $_POST['client_id'] ?? null,
            'technician_id' => $_POST['technician_id'] ?? null,
            'description' => $_POST['description'] ?? '',
            'persat_ot' => $_POST['persat_ot'] ?? null,
            'status' => $_POST['status'] ?? 'pending'
        ];
        
        // Validate required fields
        if (empty($ticketData['client_id']) || empty($ticketData['technician_id']) || empty($ticketData['description'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Create or update
            if (isset($_POST['ticket_id']) && !empty($_POST['ticket_id'])) {
                // Obtener datos del ticket antes de actualizar
                $oldTicket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
                
                // Update existing ticket
                $db->update('tickets', $ticketData, 'id = ?', [$_POST['ticket_id']]);
                flash('Ticket actualizado correctamente.', 'success');
                
                // Si se cambió el técnico asignado, enviar notificación
                if ($oldTicket['technician_id'] != $ticketData['technician_id']) {
                    $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticketData['technician_id']]);
                    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticketData['client_id']]);
                    $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$_POST['ticket_id']]);
                    
                    // Enviar notificación por WhatsApp
                    $whatsapp = new WhatsAppNotifier();
                    $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
                    
                    // Registrar resultado en un archivo de log
                    $logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
                    file_put_contents($logFile, "Resultado de notificación para ticket #{$ticket['id']}: " . ($result ? "ÉXITO" : "ERROR") . "\n");
                    file_put_contents($logFile, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
                    file_put_contents($logFile, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);
                }
            } else {
                // Create new ticket
                $ticketId = $db->insert('tickets', $ticketData);
                flash('Ticket creado correctamente.', 'success');
                
                // Obtener datos para la notificación
                $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$ticketData['technician_id']]);
                $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$ticketData['client_id']]);
                $ticket = $db->selectOne("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
                
                // Enviar notificación por WhatsApp
                $whatsapp = new WhatsAppNotifier();
                $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);
                
                // Registrar resultado en un archivo de log
                $logFile = __DIR__ . '/../ticket_notification_' . date('Y-m-d_H-i-s') . '.log';
                file_put_contents($logFile, "Resultado de notificación para ticket #{$ticket['id']}: " . ($result ? "ÉXITO" : "ERROR") . "\n");
                file_put_contents($logFile, "Técnico: {$technician['name']} ({$technician['phone']})\n", FILE_APPEND);
                file_put_contents($logFile, "Cliente: {$client['name']} ({$client['business_name']})\n", FILE_APPEND);
            }
            
            // Redirect to list view
            redirect('admin/tickets.php');
        }
    }
    
    // Delete ticket
    if (isset($_POST['delete_ticket'])) {
        $ticketId = $_POST['ticket_id'] ?? null;
        
        if ($ticketId) {
            // Check if ticket has visits
            $visitsCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM visits WHERE ticket_id = ?", 
                [$ticketId]
            )['count'];
            
            if ($visitsCount > 0) {
                flash('No se puede eliminar el ticket porque tiene visitas registradas.', 'danger');
            } else {
                $db->delete('tickets', 'id = ?', [$ticketId]);
                flash('Ticket eliminado correctamente.', 'success');
            }
        }
        
        // Redirect to list view
        redirect('admin/tickets.php');
    }
}

// Get ticket data for edit or view
$ticket = null;
if (($action === 'edit' || $action === 'view') && $ticketId) {
    $ticket = $db->selectOne("
        SELECT t.*, 
               c.name as client_name, 
               c.business_name, 
               c.address, 
               c.latitude, 
               c.longitude, 
               u.name as technician_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        WHERE t.id = ?
    ", [$ticketId]);
    
    if (!$ticket) {
        flash('Ticket no encontrado.', 'danger');
        redirect('admin/tickets.php');
    }
    
    // Debug - Imprimir valores
    if ($action === 'view') {
        error_log("Ticket ID: " . $ticketId);
        error_log("Latitude: " . ($ticket['latitude'] ?? 'NULL'));
        error_log("Longitude: " . ($ticket['longitude'] ?? 'NULL'));
    }
}

// Get all tickets for list view
$tickets = [];
if ($action === 'list') {
    $tickets = $db->select("
        SELECT t.*, c.name as client_name, u.name as technician_name
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        ORDER BY t.created_at DESC
    ");
}

// Get all clients and technicians for forms
$clients = $db->select("SELECT id, name, business_name FROM clients ORDER BY name");
$technicians = $db->select("SELECT id, name, zone FROM users WHERE role = 'technician' ORDER BY name");

// Page title
$pageTitle = 'Gestión de Tickets'; // Valor por defecto
switch($action) {
    case 'create':
        $pageTitle = 'Crear Ticket';
        break;
    case 'edit':
        $pageTitle = 'Editar Ticket';
        break;
    case 'view':
        $pageTitle = 'Ver Ticket';
        break;
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Ticket
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Tickets List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($tickets) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Técnico</th>
                                    <th>Descripción</th>
                                    <th>PERSAT OT</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo $ticket['id']; ?></td>
                                        <td><?php echo escape($ticket['client_name']); ?></td>
                                        <td><?php echo escape($ticket['technician_name']); ?></td>
                                        <td><?php echo escape(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo !empty($ticket['persat_ot']) ? escape($ticket['persat_ot']) : '-'; ?></td>
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
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $ticket['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $ticket['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar el ticket #<?php echo $ticket['id']; ?>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="<?php echo BASE_URL; ?>admin/tickets.php">
                                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                                <button type="submit" name="delete_ticket" class="btn btn-danger">Eliminar</button>
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
                    <p class="text-center">No hay tickets registrados</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Ticket Form -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/tickets.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            <?php echo (($action === 'edit' && $ticket['client_id'] == $client['id']) || 
                                                       ($action === 'create' && $clientId == $client['id'])) ? 'selected' : ''; ?>>
                                        <?php echo escape($client['name']) . ' - ' . escape($client['business_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="technician_id" class="form-label">Técnico *</label>
                            <select class="form-select" id="technician_id" name="technician_id" required>
                                <option value="">Seleccionar técnico</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>" 
                                            <?php echo (($action === 'edit' && $ticket['technician_id'] == $tech['id']) || 
                                                       ($action === 'create' && $technicianId == $tech['id'])) ? 'selected' : ''; ?>>
                                        <?php echo escape($tech['name']) . ' (Zona: ' . escape($tech['zone']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción del Problema *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $action === 'edit' ? escape($ticket['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="persat_ot" class="form-label">PERSAT OT</label>
                        <input type="text" class="form-control" id="persat_ot" name="persat_ot" value="<?php echo $action === 'edit' ? escape($ticket['persat_ot']) : ''; ?>">
                    </div>
                    
                    <?php if ($action === 'edit'): ?>
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo $ticket['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                                <option value="completed" <?php echo $ticket['status'] === 'completed' ? 'selected' : ''; ?>>Completado</option>
                                <option value="not_completed" <?php echo $ticket['status'] === 'not_completed' ? 'selected' : ''; ?>>No Completado</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/tickets.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" name="save_ticket" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        
    <?php elseif ($action === 'view' && $ticket): ?>
        <!-- View Ticket Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Información del Ticket #<?php echo $ticket['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Estado:</h6>
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
                            <span class="badge <?php echo $statusClass; ?> fs-6">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Cliente:</h6>
                            <p>
                                <strong><?php echo escape($ticket['client_name']); ?></strong><br>
                                <?php echo escape($ticket['business_name']); ?><br>
                                <?php echo escape($ticket['address']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Técnico Asignado:</h6>
                            <p><?php echo escape($ticket['technician_name']); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Descripción del Problema:</h6>
                            <p><?php echo nl2br(escape($ticket['description'])); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>PERSAT OT:</h6>
                            <p><?php echo !empty($ticket['persat_ot']) ? escape($ticket['persat_ot']) : 'No especificado'; ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Fechas:</h6>
                            <p>
                                <strong>Creado:</strong> <?php echo formatDateTime($ticket['created_at']); ?><br>
                                <strong>Última Actualización:</strong> <?php echo formatDateTime($ticket['updated_at']); ?>
                            </p>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=edit&id=<?php echo $ticket['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Editar Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <?php 
                // Convertir coordenadas a formato correcto
                $lat = str_replace(',', '.', $ticket['latitude'] ?? '');
                $lng = str_replace(',', '.', $ticket['longitude'] ?? '');
                $hasCoordinates = (!empty($lat) && !empty($lng) && is_numeric($lat) && is_numeric($lng));
                ?>
                
                <!-- Client Location Map -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Ubicación del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Dirección:</h6>
                            <p><?php echo escape($ticket['address']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Coordenadas:</h6>
                            <?php if ($hasCoordinates): ?>
                                <p class="coordinates">
                                    <?php echo $lat; ?>, <?php echo $lng; ?>
                                </p>
                            <?php else: ?>
                                <p class="text-warning">No hay coordenadas válidas disponibles</p>
                            <?php endif; ?>
                        </div>
                
                        <?php if ($hasCoordinates): ?>
                            <!-- Mapa de Leaflet -->
                            <div id="ticket-map" class="map-container" style="height: 300px; width: 100%; border-radius: 5px;"></div>
                            
                            <div class="mt-3">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $lat; ?>,<?php echo $lng; ?>" 
                                   class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-map"></i> Ver en Google Maps
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> No se pueden mostrar las coordenadas en el mapa. 
                                Por favor, verifique que el cliente tenga coordenadas válidas en su perfil.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php 
                // Obtener la visita más reciente para este ticket
                $visit = $db->selectOne("
                    SELECT * FROM visits WHERE ticket_id = ? ORDER BY id DESC LIMIT 1
                ", [$ticket['id']]);
                
                // Obtener todas las visitas para este ticket
                $allVisits = $db->select("
                    SELECT * FROM visits WHERE ticket_id = ? ORDER BY start_time DESC
                ", [$ticket['id']]);
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Información de Visita</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($visit): ?>
                            <div class="mb-3">
                                <h6>Estado:</h6>
                                <?php if (!$visit['start_time']): ?>
                                    <span class="badge bg-secondary fs-6">No iniciada</span>
                                <?php elseif (!$visit['end_time']): ?>
                                    <span class="badge bg-info fs-6">En progreso</span>
                                <?php elseif ($visit['completion_status'] === 'success'): ?>
                                    <span class="badge bg-success fs-6">Finalizada con éxito</span>
                                <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                    <span class="badge bg-danger fs-6">Finalizada sin éxito</span>
                                <?php else: ?>
                                    <span class="badge bg-warning fs-6">Estado desconocido</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Hora de Inicio:</h6>
                                <p><?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : 'No iniciada'; ?></p>
                            </div>
                            
                            <?php if (!empty($visit['start_notes'])): ?>
                            <div class="mb-3">
                                <h6>Comentarios Iniciales:</h6>
                                <div class="p-3 border rounded bg-info text-white">
                                    <?php echo nl2br(escape($visit['start_notes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($visit['comments'])): ?>
                            <div class="mb-3">
                                <h6>Comentarios de Finalización:</h6>
                                <div class="p-3 border rounded bg-info text-white">
                                    <?php echo nl2br(escape($visit['comments'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($visit['end_time']): ?>
                                <div class="mb-3">
                                    <h6>Hora de Finalización:</h6>
                                    <p><?php echo formatDateTime($visit['end_time']); ?></p>
                                </div>
                                
                                <?php if ($visit['completion_status'] !== 'success' && !empty($visit['failure_reason'])): ?>
                                <div class="mb-3">
                                    <h6>Motivo de No Finalización:</h6>
                                    <div class="p-3 border rounded bg-danger bg-opacity-10">
                                        <?php echo nl2br(escape($visit['failure_reason'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Historial de visitas si hay más de una -->
                            <?php if (count($allVisits) > 1): ?>
                                <div class="mt-4">
                                    <h6>Historial de Visitas:</h6>
                                    <div class="accordion" id="visitsAccordion">
                                        <?php foreach ($allVisits as $index => $historyVisit): ?>
                                            <?php if ($index > 0 || $historyVisit['id'] !== $visit['id']): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#visit<?php echo $historyVisit['id']; ?>" aria-expanded="false">
                                                            Visita del <?php echo formatDate($historyVisit['start_time'] ?? $historyVisit['created_at']); ?>
                                                            <?php if ($historyVisit['completion_status'] === 'success'): ?>
                                                                <span class="badge bg-success ms-2">Éxito</span>
                                                            <?php elseif ($historyVisit['completion_status'] === 'failure'): ?>
                                                                <span class="badge bg-danger ms-2">Fallida</span>
                                                            <?php elseif ($historyVisit['start_time'] && !$historyVisit['end_time']): ?>
                                                                <span class="badge bg-info ms-2">En progreso</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary ms-2">No iniciada</span>
                                                            <?php endif; ?>
                                                        </button>
                                                    </h2>
                                                    <div id="visit<?php echo $historyVisit['id']; ?>" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <?php if ($historyVisit['start_time']): ?>
                                                                <p><strong>Inicio:</strong> <?php echo formatDateTime($historyVisit['start_time']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($historyVisit['end_time']): ?>
                                                                <p><strong>Finalización:</strong> <?php echo formatDateTime($historyVisit['end_time']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($historyVisit['start_notes'])): ?>
                                                            <div class="mb-3">
                                                                <h6>Comentarios Iniciales:</h6>
                                                                <div class="p-3 border rounded bg-info text-white">
                                                                    <?php echo nl2br(escape($historyVisit['start_notes'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($historyVisit['comments'])): ?>
                                                            <div class="mb-3">
                                                                <h6>Comentarios de Finalización:</h6>
                                                                <div class="p-3 border rounded bg-info text-white">
                                                                    <?php echo nl2br(escape($historyVisit['comments'])); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($historyVisit['completion_status'] === 'failure' && !empty($historyVisit['failure_reason'])): ?>
                                                                <div class="mb-3">
                                                                    <h6>Motivo de No Finalización:</h6>
                                                                    <div class="p-3 border rounded bg-danger bg-opacity-10">
                                                                        <?php echo nl2br(escape($historyVisit['failure_reason'])); ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center">No hay visitas registradas para este ticket</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>

<?php if ($action === 'view' && $hasCoordinates): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar un momento para asegurarse de que el DOM esté completamente cargado
        setTimeout(function() {
            try {
                console.log("Inicializando mapa con coordenadas:", <?php echo $lat; ?>, <?php echo $lng; ?>);
                
                // Crear mapa
                const map = L.map('ticket-map').setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 15);
                
                // Añadir capa de tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Añadir marcador
                L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
                    .addTo(map)
                    .bindPopup("<?php echo escape($ticket['client_name']); ?><br><?php echo escape($ticket['address']); ?>");
                
                // Invalidar tamaño para asegurar que se renderice correctamente
                map.invalidateSize();
            } catch (error) {
                console.error("Error al inicializar el mapa:", error);
            }
        }, 500);
    });
</script>
<?php endif; ?>
