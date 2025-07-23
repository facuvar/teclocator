<?php
/**
 * Select Ticket page for technicians after scanning QR code
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get location parameters if available
$latitude = $_GET['lat'] ?? null;
$longitude = $_GET['lng'] ?? null;
$hasLocation = ($latitude && $longitude);

// Get assigned tickets
$tickets = $db->select("
    SELECT t.*, 
           c.name as client_name, c.business_name, c.address,
           c.latitude, c.longitude
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ? AND t.status != 'completed' AND t.status != 'not_completed'
    ORDER BY 
        CASE 
            WHEN t.status = 'pending' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            ELSE 3
        END,
        t.created_at DESC
", [$technicianId]);

// Check if there are any active visits
$activeVisits = $db->select("
    SELECT v.*, t.id as ticket_id, t.description, c.name as client_name
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ? AND v.end_time IS NULL
", [$technicianId]);

// Page title
$pageTitle = 'Seleccionar Ticket';

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
    
    <?php if ($hasLocation): ?>
    <div class="alert alert-info">
        <i class="bi bi-geo-alt"></i> Su ubicación ha sido registrada. Se verificará que esté dentro del rango permitido (50 metros) del cliente seleccionado.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No se ha detectado su ubicación. Para iniciar o finalizar una visita, debe permitir el acceso a su ubicación.
        <div class="mt-2">
            <button id="get-location" class="btn btn-primary">
                <i class="bi bi-geo-alt"></i> Obtener Mi Ubicación
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($activeVisits) > 0): ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">Visitas Activas</h5>
        </div>
        <div class="card-body">
            <p>Tiene <?php echo count($activeVisits); ?> visita(s) activa(s). Puede finalizarlas seleccionando el ticket correspondiente:</p>
            
            <div class="list-group">
                <?php foreach ($activeVisits as $visit): ?>
                <a href="scan.php?action=end&visit_id=<?php echo $visit['id']; ?><?php echo $hasLocation ? '&lat=' . $latitude . '&lng=' . $longitude : ''; ?>" 
                   class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Ticket #<?php echo $visit['ticket_id']; ?> - <?php echo escape($visit['client_name']); ?></h5>
                        <small>Iniciada: <?php echo date('d/m/Y H:i', strtotime($visit['start_time'])); ?></small>
                    </div>
                    <p class="mb-1"><?php echo escape($visit['description']); ?></p>
                    <small>Haga clic para finalizar esta visita</small>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Tickets Asignados</h5>
        </div>
        <div class="card-body">
            <?php if (count($tickets) > 0): ?>
                <p>Seleccione un ticket para iniciar una visita:</p>
                
                <div class="list-group">
                    <?php foreach ($tickets as $ticket): 
                        // Check if this ticket already has an active visit
                        $hasActiveVisit = false;
                        foreach ($activeVisits as $visit) {
                            if ($visit['ticket_id'] == $ticket['id']) {
                                $hasActiveVisit = true;
                                break;
                            }
                        }
                        
                        // Skip tickets with active visits
                        if ($hasActiveVisit) continue;
                    ?>
                    <a href="scan.php?action=start&ticket_id=<?php echo $ticket['id']; ?><?php echo $hasLocation ? '&lat=' . $latitude . '&lng=' . $longitude : ''; ?>" 
                       class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Ticket #<?php echo $ticket['id']; ?> - <?php echo escape($ticket['client_name']); ?></h5>
                            <span class="badge <?php echo $ticket['status'] === 'pending' ? 'bg-warning' : 'bg-info'; ?>">
                                <?php echo $ticket['status'] === 'pending' ? 'Pendiente' : 'En Progreso'; ?>
                            </span>
                        </div>
                        <p class="mb-1"><?php echo escape($ticket['description']); ?></p>
                        <small><?php echo escape($ticket['address']); ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No tiene tickets pendientes asignados</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get location button
    const getLocationBtn = document.getElementById('get-location');
    if (getLocationBtn) {
        getLocationBtn.addEventListener('click', function() {
            if (navigator.geolocation) {
                getLocationBtn.disabled = true;
                getLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Obteniendo ubicación...';
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Redirect with location parameters
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const currentUrl = new URL(window.location.href);
                        
                        currentUrl.searchParams.set('lat', lat);
                        currentUrl.searchParams.set('lng', lng);
                        
                        window.location.href = currentUrl.toString();
                    },
                    function(error) {
                        getLocationBtn.disabled = false;
                        getLocationBtn.innerHTML = '<i class="bi bi-geo-alt"></i> Obtener Mi Ubicación';
                        
                        let errorMsg = 'Error al obtener la ubicación.';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = 'Permiso denegado para obtener la ubicación.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = 'Información de ubicación no disponible.';
                                break;
                            case error.TIMEOUT:
                                errorMsg = 'Tiempo de espera agotado al obtener la ubicación.';
                                break;
                        }
                        
                        alert(errorMsg);
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                alert('Su navegador no soporta geolocalización.');
            }
        });
    }
});
</script>

<?php include_once '../templates/footer.php'; ?>
