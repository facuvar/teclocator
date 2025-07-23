<?php
/**
 * Active visit page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get visit ID from query string or find active visit
$visitId = $_GET['id'] ?? null;

// If no visit ID is provided, check for active visits
if (!$visitId) {
    $activeVisit = $db->selectOne("
        SELECT v.id
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        WHERE t.technician_id = ? AND v.end_time IS NULL
        ORDER BY v.start_time DESC
        LIMIT 1
    ", [$technicianId]);
    
    if ($activeVisit) {
        $visitId = $activeVisit['id'];
    } else {
        // No active visit found, redirect to dashboard
        flash('No tiene visitas activas.', 'warning');
        redirect('dashboard.php');
    }
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

// Check if visit is already ended
if ($visit['end_time']) {
    redirect('visit_completed.php?id=' . $visitId);
}

// Page title
$pageTitle = 'Visita Activa';

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
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Visita en Progreso - Ticket #<?php echo $visit['ticket_id']; ?>
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
                                <strong>Duración:</strong> 
                                <span id="visit-duration">
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Descripción del Problema:</h6>
                        <p><?php echo nl2br(escape($visit['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($visit['start_notes'])): ?>
                        <div class="mt-4">
                            <h6>Notas Iniciales:</h6>
                            <p><?php echo nl2br(escape($visit['start_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="scan_qr.php?action=end&visit_id=<?php echo $visitId; ?>" class="btn btn-primary">
                            <i class="bi bi-qr-code-scan"></i> Escanear QR para Finalizar
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- End Visit Form -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Finalizar Visita</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Para finalizar la visita, debe escanear el código QR que está físicamente pegado en el ascensor y estar a menos de 50 metros de la ubicación registrada.
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="scan_qr.php?action=end&visit_id=<?php echo $visitId; ?>" class="btn btn-primary mb-2">
                            <i class="bi bi-qr-code-scan"></i> Ir a Escanear QR
                        </a>
                        
                        <button id="finalizarDirectamente" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Finalizar Directamente (Usar Mi Ubicación)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Map -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Ubicación del Cliente</h5>
                </div>
                <div class="card-body">
                    <div id="map" class="map-container" style="height: 300px;"></div>
                    <div class="mt-3">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $visit['latitude']; ?>,<?php echo $visit['longitude']; ?>" 
                           class="btn btn-outline-primary w-100" target="_blank">
                            <i class="bi bi-map"></i> Abrir en Google Maps
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Timer -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Tiempo Transcurrido</h5>
                </div>
                <div class="card-body text-center">
                    <div id="timer" class="display-4 mb-3">00:00:00</div>
                    <p class="text-muted">Desde el inicio de la visita</p>
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
    
    <?php
    // Calcular duración inicial en PHP
    $startTime = new DateTime($visit['start_time']);
    $now = new DateTime();
    $initialDuration = $now->getTimestamp() - $startTime->getTimestamp();
    $initialHours = floor($initialDuration / 3600);
    $initialMinutes = floor(($initialDuration % 3600) / 60);
    $initialSeconds = $initialDuration % 60;
    
    // Formatear para mostrar
    $initialHoursFormatted = str_pad($initialHours, 2, '0', STR_PAD_LEFT);
    $initialMinutesFormatted = str_pad($initialMinutes, 2, '0', STR_PAD_LEFT);
    $initialSecondsFormatted = str_pad($initialSeconds, 2, '0', STR_PAD_LEFT);
    $initialTimeFormatted = "$initialHoursFormatted:$initialMinutesFormatted:$initialSecondsFormatted";
    ?>
    
    // Initialize timer with PHP calculated values
    let seconds = <?php echo $initialSeconds; ?>;
    let minutes = <?php echo $initialMinutes; ?>;
    let hours = <?php echo $initialHours; ?>;
    
    // Set initial value
    document.getElementById('timer').textContent = '<?php echo $initialTimeFormatted; ?>';
    
    function incrementTimer() {
        seconds++;
        
        if (seconds >= 60) {
            seconds = 0;
            minutes++;
            
            if (minutes >= 60) {
                minutes = 0;
                hours++;
            }
        }
        
        // Formatear con ceros a la izquierda
        const formattedHours = String(hours).padStart(2, '0');
        const formattedMinutes = String(minutes).padStart(2, '0');
        const formattedSeconds = String(seconds).padStart(2, '0');
        
        // Actualizar el contador en formato HH:MM:SS
        document.getElementById('timer').textContent = 
            `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
    }
    
    // Increment timer every second
    setInterval(incrementTimer, 1000);
    
    // Make map refresh when it becomes visible
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
    
    // Botón para finalizar directamente
    document.getElementById('finalizarDirectamente').addEventListener('click', function() {
        // Mostrar mensaje de carga
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="bi bi-hourglass"></i> Obteniendo ubicación...';
        this.disabled = true;
        
        // Establecer un timeout para evitar que se quede congelado
        const timeoutId = setTimeout(() => {
            document.getElementById('finalizarDirectamente').innerHTML = originalText;
            document.getElementById('finalizarDirectamente').disabled = false;
            alert('La operación ha tardado demasiado tiempo. Por favor, intente nuevamente.');
        }, 15000); // 15 segundos de timeout
        
        // Obtener la ubicación actual
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Cancelar el timeout
                    clearTimeout(timeoutId);
                    
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Redirigir a la página de finalización con las coordenadas
                    window.location.href = `scan_qr.php?action=end&visit_id=<?php echo $visitId; ?>&lat=${lat}&lng=${lng}`;
                },
                function(error) {
                    // Cancelar el timeout
                    clearTimeout(timeoutId);
                    
                    // Mostrar error específico basado en el código de error
                    let errorMsg = 'Error al obtener la ubicación.';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Acceso a la ubicación denegado. Por favor, permita el acceso a su ubicación en la configuración de su navegador.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'La información de ubicación no está disponible en este momento.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'La solicitud de ubicación ha expirado.';
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMsg = 'Ha ocurrido un error desconocido al obtener la ubicación.';
                            break;
                    }
                    
                    alert(errorMsg);
                    document.getElementById('finalizarDirectamente').innerHTML = originalText;
                    document.getElementById('finalizarDirectamente').disabled = false;
                },
                { 
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            // Cancelar el timeout
            clearTimeout(timeoutId);
            
            alert('Su navegador no soporta geolocalización.');
            document.getElementById('finalizarDirectamente').innerHTML = originalText;
            document.getElementById('finalizarDirectamente').disabled = false;
        }
    });
});
</script>

<?php include_once '../templates/footer.php'; ?>
