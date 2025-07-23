<?php
/**
 * Gestión de solicitudes de servicio para administradores
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$requestId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update request status
    if (isset($_POST['update_status'])) {
        $requestId = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? 'pending';
        $notes = $_POST['notes'] ?? '';
        
        if ($requestId) {
            $data = [
                'status' => $status,
                'notes' => $notes
            ];
            
            $db->update('service_requests', $data, 'id = ?', [$requestId]);
            flash('Estado de la solicitud actualizado correctamente.', 'success');
        }
        
        // Redirect to list view
        redirect('admin/service_requests.php');
    }
    
    // Delete request
    if (isset($_POST['delete_request'])) {
        $requestId = $_POST['request_id'] ?? null;
        
        if ($requestId) {
            $db->delete('service_requests', 'id = ?', [$requestId]);
            flash('Solicitud eliminada correctamente.', 'success');
        }
        
        // Redirect to list view
        redirect('admin/service_requests.php');
    }
}

// Get request data for edit or view
$request = null;
if (($action === 'edit' || $action === 'view') && $requestId) {
    $request = $db->selectOne("SELECT * FROM service_requests WHERE id = ?", [$requestId]);
}

// Get all service requests for list view
$requests = [];
if ($action === 'list') {
    $requests = $db->select("
        SELECT * FROM service_requests
        ORDER BY created_at DESC
    ");
}

// Page title
$pageTitle = 'Solicitudes de Servicio';
switch($action) {
    case 'edit':
        $pageTitle = 'Editar Solicitud de Servicio';
        break;
    case 'view':
        $pageTitle = 'Ver Solicitud de Servicio';
        break;
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>service_request.php" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-eye"></i> Ver Formulario Público
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/service_requests.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Requests List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><?php echo $req['id']; ?></td>
                                        <td>
                                            <?php 
                                            $requestTypeLabels = [
                                                'encerrada' => '<span class="badge bg-danger">Persona encerrada</span>',
                                                'fuera_servicio' => '<span class="badge bg-warning text-dark">Fuera de servicio</span>',
                                                'fallas' => '<span class="badge bg-info">Con fallas</span>'
                                            ];
                                            echo $requestTypeLabels[$req['request_type']] ?? $req['request_type'];
                                            ?>
                                        </td>
                                        <td><?php echo escape($req['name']); ?></td>
                                        <td><?php echo escape($req['phone']); ?></td>
                                        <td><?php echo escape($req['address'] ?? 'No disponible'); ?></td>
                                        <td><?php echo formatDateTime($req['created_at']); ?></td>
                                        <td>
                                            <?php 
                                            $statusLabels = [
                                                'pending' => '<span class="badge bg-secondary">Pendiente</span>',
                                                'in_progress' => '<span class="badge bg-primary">En proceso</span>',
                                                'completed' => '<span class="badge bg-success">Completada</span>'
                                            ];
                                            echo $statusLabels[$req['status']] ?? $req['status'];
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=view&id=<?php echo $req['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=edit&id=<?php echo $req['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $req['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $req['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $req['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $req['id']; ?>">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar esta solicitud de servicio?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="">
                                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                                <button type="submit" name="delete_request" class="btn btn-danger">Eliminar</button>
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
                    <p class="text-center">No hay solicitudes de servicio registradas</p>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($action === 'view' && $request): ?>
        <!-- View Request -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Detalles de la Solicitud</h5>
                        <table class="table">
                            <tr>
                                <th>ID:</th>
                                <td><?php echo $request['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Tipo:</th>
                                <td>
                                    <?php 
                                    $requestTypes = [
                                        'encerrada' => 'Persona encerrada',
                                        'fuera_servicio' => 'Ascensor fuera de servicio',
                                        'fallas' => 'Ascensor con fallas'
                                    ];
                                    echo $requestTypes[$request['request_type']] ?? $request['request_type'];
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo escape($request['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo escape($request['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Dirección:</th>
                                <td><?php echo escape($request['address'] ?? 'No disponible'); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha:</th>
                                <td><?php echo formatDateTime($request['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <?php 
                                    $statusLabels = [
                                        'pending' => '<span class="badge bg-secondary">Pendiente</span>',
                                        'in_progress' => '<span class="badge bg-primary">En proceso</span>',
                                        'completed' => '<span class="badge bg-success">Completada</span>'
                                    ];
                                    echo $statusLabels[$request['status']] ?? $request['status'];
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="card-title">Notas</h5>
                        <div class="p-3 border rounded bg-light">
                            <?php echo !empty($request['notes']) ? nl2br(escape($request['notes'])) : 'No hay notas disponibles'; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=edit&id=<?php echo $request['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </div>
                
                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ¿Está seguro de que desea eliminar esta solicitud de servicio?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="delete_request" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'edit' && $request): ?>
        <!-- Edit Request -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5 class="card-title">Detalles de la Solicitud</h5>
                            <table class="table">
                                <tr>
                                    <th>ID:</th>
                                    <td><?php echo $request['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo:</th>
                                    <td>
                                        <?php 
                                        $requestTypes = [
                                            'encerrada' => 'Persona encerrada',
                                            'fuera_servicio' => 'Ascensor fuera de servicio',
                                            'fallas' => 'Ascensor con fallas'
                                        ];
                                        echo $requestTypes[$request['request_type']] ?? $request['request_type'];
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td><?php echo escape($request['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono:</th>
                                    <td><?php echo escape($request['phone']); ?></td>
                                </tr>
                                <tr>
                                    <th>Dirección:</th>
                                    <td><?php echo escape($request['address'] ?? 'No disponible'); ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha:</th>
                                    <td><?php echo formatDateTime($request['created_at']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>En proceso</option>
                                    <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completada</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="5"><?php echo escape($request['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </form>
                
                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ¿Está seguro de que desea eliminar esta solicitud de servicio?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="delete_request" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>
