<?php
/**
 * Ticket detail page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get ticket ID from query string
$ticketId = $_GET['id'] ?? null;

if (!$ticketId) {
    flash('ID de ticket no proporcionado.', 'danger');
    redirect('dashboard.php');
}

// Get ticket details
$ticket = $db->selectOne("
    SELECT t.*, 
           c.name as client_name, c.business_name, c.address,
           c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.id = ? AND t.technician_id = ?
", [$ticketId, $technicianId]);

if (!$ticket) {
    flash('Ticket no encontrado o no asignado a usted.', 'danger');
    redirect('dashboard.php');
}

// Get visit history
$visits = $db->select("
    SELECT * FROM visits
    WHERE ticket_id = ?
    ORDER BY start_time DESC
", [$ticketId]);

// Check if there's an active visit
$activeVisit = null;
foreach ($visits as $visit) {
    if (!$visit['end_time']) {
        $activeVisit = $visit;
        break;
    }
}

// Page title
$pageTitle = 'Detalle de Ticket #' . $ticketId;

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <?php if (!$activeVisit && $ticket['status'] !== 'completed' && $ticket['status'] !== 'not_completed'): ?>
                <a href="scan_qr.php?action=<?php echo ($ticket['status'] === 'in_progress') ? 'end' : 'start'; ?>&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-qr-code-scan"></i> Escanear QR en Ascensor
                </a>
            <?php elseif ($activeVisit): ?>
                <a href="active_visit.php?id=<?php echo $activeVisit['id']; ?>" class="btn btn-info">
                    <i class="bi bi-eye"></i> Ver Visita Activa
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Información del Ticket</h5>
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
                        <h6>Descripción del Problema:</h6>
                        <p><?php echo nl2br(escape($ticket['description'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Fechas:</h6>
                        <p>
                            <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?><br>
                            <strong>Última Actualización:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Visit History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Historial de Visitas</h5>
                </div>
                <div class="card-body">
                    <?php if (count($visits) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visits as $visit): ?>
                                        <tr>
                                            <td><?php echo $visit['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($visit['start_time'])); ?></td>
                                            <td>
                                                <?php echo $visit['end_time'] ? date('d/m/Y H:i', strtotime($visit['end_time'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if (!$visit['end_time']): ?>
                                                    <span class="badge bg-info">En Progreso</span>
                                                <?php elseif ($visit['completion_status'] === 'success'): ?>
                                                    <span class="badge bg-success">Finalizada con Éxito</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">No Finalizada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$visit['end_time']): ?>
                                                    <a href="active_visit.php?id=<?php echo $visit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                <?php else: ?>
                                                    <a href="visit_completed.php?id=<?php echo $visit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No hay visitas registradas para este ticket</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Client Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Información del Cliente</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Nombre:</h6>
                        <p><?php echo escape($ticket['client_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Empresa:</h6>
                        <p><?php echo escape($ticket['business_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Dirección:</h6>
                        <p><?php echo escape($ticket['address']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Map -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Ubicación</h5>
                </div>
                <div class="card-body">
                    <div id="map" class="map-container" style="height: 300px;"></div>
                    <div class="mt-3">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $ticket['latitude']; ?>,<?php echo $ticket['longitude']; ?>" 
                           class="btn btn-outline-primary w-100" target="_blank">
                            <i class="bi bi-map"></i> Abrir en Google Maps
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Instrucciones para Escaneo -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Instrucciones</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Para iniciar o finalizar una visita:
                        <ol class="mb-0 mt-2">
                            <li>Diríjase a la ubicación del ascensor</li>
                            <li>Escanee el código QR que está físicamente pegado en el ascensor</li>
                            <li>El sistema verificará que usted se encuentra dentro del rango permitido (50 metros)</li>
                            <li>Complete la información solicitada para iniciar o finalizar la visita</li>
                        </ol>
                    </div>
                    <a href="scan_qr.php?action=<?php echo ($ticket['status'] === 'in_progress') ? 'end' : 'start'; ?>&ticket_id=<?php echo $ticket['id']; ?>" class="btn btn-primary w-100">
                        <i class="bi bi-qr-code-scan"></i> Escanear Código QR en Ascensor
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const clientLat = <?php echo $ticket['latitude']; ?>;
    const clientLng = <?php echo $ticket['longitude']; ?>;
    
    const map = L.map('map').setView([clientLat, clientLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add marker at client location
    L.marker([clientLat, clientLng])
        .addTo(map)
        .bindPopup("<?php echo escape($ticket['client_name']); ?><br><?php echo escape($ticket['address']); ?>");
    
    // Make map refresh when it becomes visible
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
});
</script>

<?php include_once '../templates/footer.php'; ?>
