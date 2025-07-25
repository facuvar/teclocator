<?php
/**
 * Visit completion page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get visit ID from query string
$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    flash('ID de visita no proporcionado.', 'danger');
    redirect('dashboard.php');
}

// Get visit details
$visit = $db->selectOne("
    SELECT v.*, 
           t.id as ticket_id, t.description, t.status as ticket_status,
           c.id as client_id, c.name as client_name, c.business_name, c.address,
           c.latitude, c.longitude
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE v.id = ? AND t.technician_id = ?
", [$visitId, $technicianId]);

if (!$visit) {
    flash('Visita no encontrada o no autorizada.', 'danger');
    redirect('dashboard.php');
}

// Check if visit is not ended
if (!$visit['end_time']) {
    redirect('active_visit.php?id=' . $visitId);
}

// Calculate visit duration
$startTime = new DateTime($visit['start_time']);
$endTime = new DateTime($visit['end_time']);
$duration = $startTime->diff($endTime);

// Page title
$pageTitle = 'Visita Completada';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header <?php echo $visit['completion_status'] === 'success' ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <?php if ($visit['completion_status'] === 'success'): ?>
                            <i class="bi bi-check-circle"></i> Visita Finalizada con Éxito
                        <?php else: ?>
                            <i class="bi bi-x-circle"></i> Visita No Completada
                        <?php endif; ?>
                        - Ticket #<?php echo $visit['ticket_id']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cliente:</h6>
                            <p>
                                <strong><?php echo escape($visit['client_name']); ?></strong><br>
                                <?php echo escape($visit['business_name']); ?><br>
                                <?php echo escape($visit['address']); ?><br>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Información de la Visita:</h6>
                            <p>
                                <strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($visit['start_time'])); ?><br>
                                <strong>Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($visit['end_time'])); ?><br>
                                <strong>Duración:</strong> 
                                <?php 
                                    if ($duration->days > 0) {
                                        echo $duration->format('%d días, %h horas, %i minutos');
                                    } else {
                                        echo $duration->format('%h horas, %i minutos');
                                    }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Descripción del Problema:</h6>
                        <p><?php echo nl2br(escape($visit['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($visit['start_notes'])): ?>
                        <div class="mt-4">
                            <h6>Comentarios Iniciales:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['start_notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($visit['completion_status'] === 'success'): ?>
                        <div class="mt-4">
                            <h6>Comentarios de Finalización:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['comments'])); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4">
                            <h6>Motivo de No Finalización:</h6>
                            <div class="p-3 border rounded bg-danger text-white">
                                <?php echo nl2br(escape($visit['failure_reason'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-house"></i> Ir al Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Visit Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Resumen de la Visita</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <?php if ($visit['completion_status'] === 'success'): ?>
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill text-danger" style="font-size: 2rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Estado Final:</h6>
                            <p class="mb-0">
                                <?php echo $visit['completion_status'] === 'success' ? 'Reparación Exitosa' : 'No Completada'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6>Ticket:</h6>
                        <p class="mb-0">
                            #<?php echo $visit['ticket_id']; ?> - 
                            <?php
                                $statusClass = '';
                                $statusText = '';
                                
                                switch ($visit['ticket_status']) {
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
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Fecha de Finalización:</h6>
                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($visit['end_time'])); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Duración Total:</h6>
                        <p class="mb-0">
                            <?php 
                                if ($duration->days > 0) {
                                    echo $duration->format('%d días, %h horas, %i minutos');
                                } else {
                                    echo $duration->format('%h horas, %i minutos');
                                }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Map -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Ubicación del Cliente</h5>
                </div>
                <div class="card-body">
                    <div id="map" class="map-container" style="height: 200px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const clientLat = <?php echo $visit['latitude']; ?>;
    const clientLng = <?php echo $visit['longitude']; ?>;
    
    const map = L.map('map').setView([clientLat, clientLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add marker at client location
    L.marker([clientLat, clientLng])
        .addTo(map)
        .bindPopup("<?php echo escape($visit['client_name']); ?><br><?php echo escape($visit['address']); ?>");
    
    // Make map refresh when it becomes visible
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
});
</script>

<?php include_once '../templates/footer.php'; ?>
