<?php
/**
 * Visits management page for administrators
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$visitId = $_GET['id'] ?? null;

// Get all visits for list view
$visits = [];
if ($action === 'list') {
    $visits = $db->select("
        SELECT v.*, t.id as ticket_id, t.description, t.status as ticket_status,
               c.name as client_name, c.business_name, u.name as technician_name
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        ORDER BY v.start_time DESC
    ");
}

// Get visit details for view
$visit = null;
if ($action === 'view' && $visitId) {
    $visit = $db->selectOne("
        SELECT v.*, t.id as ticket_id, t.description, t.status as ticket_status,
               c.name as client_name, c.business_name, c.address, c.latitude, c.longitude,
               u.name as technician_name, u.email as technician_email, u.phone as technician_phone
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        WHERE v.id = ?
    ", [$visitId]);
    
    if (!$visit) {
        flash('Visita no encontrada.', 'danger');
        redirect('visits.php');
    }
}

// Page title
$pageTitle = $action === 'view' ? 'Detalles de Visita' : 'Gestión de Visitas';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'view'): ?>
            <a href="visits.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Visits List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($visits) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ticket</th>
                                    <th>Cliente</th>
                                    <th>Técnico</th>
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
                                        <td><?php echo $visit['ticket_id']; ?></td>
                                        <td><?php echo escape($visit['client_name']); ?></td>
                                        <td><?php echo escape($visit['technician_name']); ?></td>
                                        <td><?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : '-'; ?></td>
                                        <td><?php echo $visit['end_time'] ? formatDateTime($visit['end_time']) : '-'; ?></td>
                                        <td>
                                            <?php if (!$visit['end_time']): ?>
                                                <span class="badge bg-info">En Progreso</span>
                                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                                <span class="badge bg-success">Finalizada con Éxito</span>
                                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                                <span class="badge bg-danger">Finalizada sin Éxito</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Estado Desconocido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?action=view&id=<?php echo $visit['id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay visitas registradas</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'view' && $visit): ?>
        <!-- View Visit Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Información de la Visita #<?php echo $visit['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Estado:</h6>
                            <?php if (!$visit['start_time']): ?>
                                <span class="badge bg-secondary fs-6">No iniciada</span>
                            <?php elseif (!$visit['end_time']): ?>
                                <span class="badge bg-info fs-6">En progreso</span>
                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                <span class="badge bg-success fs-6">Finalizada con éxito</span>
                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                <span class="badge bg-danger fs-6">Finalizada sin Éxito</span>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6">Estado desconocido</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Ticket:</h6>
                            <p>
                                <strong>#<?php echo $visit['ticket_id']; ?></strong><br>
                                <?php echo escape(substr($visit['description'], 0, 100)) . (strlen($visit['description']) > 100 ? '...' : ''); ?>
                                <br>
                                <a href="../admin/tickets.php?action=view&id=<?php echo $visit['ticket_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-ticket-perforated"></i> Ver Ticket Completo
                                </a>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Cliente:</h6>
                            <p>
                                <strong><?php echo escape($visit['client_name']); ?></strong><br>
                                <?php echo escape($visit['business_name']); ?><br>
                                <?php echo escape($visit['address']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Técnico:</h6>
                            <p>
                                <strong><?php echo escape($visit['technician_name']); ?></strong><br>
                                Email: <?php echo escape($visit['technician_email']); ?><br>
                                Teléfono: <?php echo escape($visit['technician_phone']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6>Fechas:</h6>
                            <p>
                                <strong>Inicio:</strong> <?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : 'No iniciada'; ?><br>
                                <strong>Fin:</strong> <?php echo $visit['end_time'] ? formatDateTime($visit['end_time']) : 'En progreso'; ?>
                            </p>
                        </div>
                        
                        <!-- Estado de la visita -->
                        <div class="mb-4">
                            <h6>Estado:</h6>
                            <?php if (!$visit['start_time']): ?>
                                <span class="badge bg-secondary fs-6">No iniciada</span>
                            <?php elseif (!$visit['end_time']): ?>
                                <span class="badge bg-info fs-6">En progreso</span>
                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                <span class="badge bg-success fs-6">Finalizada con éxito</span>
                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                <span class="badge bg-danger fs-6">Finalizada sin Éxito</span>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6">Estado desconocido</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comentarios iniciales (si existen) -->
                        <?php if (!empty($visit['start_notes'])): ?>
                        <div class="mb-4">
                            <h6>Comentarios Iniciales:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['start_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Comentarios de finalización (si existen) -->
                        <?php if (!empty($visit['comments'])): ?>
                        <div class="mb-4">
                            <h6>Comentarios de Finalización:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['comments'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Motivo de no finalización (si aplica) -->
                        <?php if ($visit['end_time'] && $visit['completion_status'] !== 'success' && !empty($visit['failure_reason'])): ?>
                        <div class="mb-4">
                            <h6>Motivo de No Finalización:</h6>
                            <div class="p-3 border rounded bg-danger bg-opacity-10">
                                <?php echo nl2br(escape($visit['failure_reason'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Map -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Ubicación</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" class="map-container"></div>
                        
                        <!-- Contador de tiempo -->
                        <div class="mt-3 p-3 border rounded">
                            <h6>
                                <i class="bi bi-clock"></i> 
                                <?php if (!$visit['start_time']): ?>
                                    Visita no iniciada
                                <?php elseif (!$visit['end_time']): ?>
                                    Tiempo transcurrido:
                                <?php else: ?>
                                    Duración total:
                                <?php endif; ?>
                            </h6>
                            
                            <?php if (!$visit['start_time']): ?>
                                <div class="text-center">
                                    <span class="badge bg-secondary">00:00:00</span>
                                </div>
                            <?php elseif (!$visit['end_time']): ?>
                                <div class="text-center">
                                    <span id="visit-duration" class="badge bg-info fs-5">Calculando...</span>
                                </div>
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
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        // Inicializar con los valores calculados en PHP
                                        let seconds = <?php echo $initialSeconds; ?>;
                                        let minutes = <?php echo $initialMinutes; ?>;
                                        let hours = <?php echo $initialHours; ?>;
                                        
                                        // Establecer el valor inicial
                                        document.getElementById('visit-duration').textContent = 
                                            '<?php echo $initialTimeFormatted; ?>';
                                        
                                        // Función para incrementar el contador cada segundo
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
                                            
                                            // Actualizar el elemento en la página
                                            document.getElementById('visit-duration').textContent = 
                                                `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
                                        }
                                        
                                        // Incrementar el contador cada segundo
                                        setInterval(incrementTimer, 1000);
                                    });
                                </script>
                            <?php else: ?>
                                <?php
                                    // Calcular duración
                                    $startTime = new DateTime($visit['start_time']);
                                    $endTime = new DateTime($visit['end_time']);
                                    $duration = $startTime->diff($endTime);
                                    
                                    // Formatear duración
                                    if ($duration->days > 0) {
                                        $durationText = $duration->format('%d días, %h horas, %i minutos');
                                    } else {
                                        $durationText = $duration->format('%h horas, %i minutos');
                                    }
                                ?>
                                <div class="text-center">
                                    <span class="badge bg-success fs-5"><?php echo $durationText; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript for map display -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
                map.invalidateSize();
            });
        </script>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>
