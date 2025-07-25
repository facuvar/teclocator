<?php
/**
 * Completed visits page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$clientId = $_GET['client_id'] ?? '';

// Build query conditions
$conditions = ['t.technician_id = ?'];
$params = [$technicianId];

if ($status === 'success') {
    $conditions[] = "v.completion_status = 'success'";
} elseif ($status === 'failure') {
    $conditions[] = "v.completion_status = 'failure'";
} else {
    $conditions[] = "v.end_time IS NOT NULL"; // All completed visits
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(v.start_time) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(v.end_time) <= ?";
    $params[] = $dateTo;
}

if (!empty($clientId)) {
    $conditions[] = "c.id = ?";
    $params[] = $clientId;
}

// Build the WHERE clause
$whereClause = implode(' AND ', $conditions);

// Get completed visits
$visits = $db->select("
    SELECT v.*, 
           t.id as ticket_id, t.description,
           c.id as client_id, c.name as client_name, c.business_name, c.address
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE $whereClause
    ORDER BY v.end_time DESC
", $params);

// Get clients for filter dropdown
$clients = $db->select("
    SELECT DISTINCT c.id, c.name, c.business_name
    FROM clients c
    JOIN tickets t ON c.id = t.client_id
    JOIN visits v ON t.id = v.ticket_id
    WHERE t.technician_id = ? AND v.end_time IS NOT NULL
    ORDER BY c.name
", [$technicianId]);

// Page title
$pageTitle = 'Historial de Visitas';

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
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="get" action="completed-visits.php" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos</option>
                        <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Finalizadas con Éxito</option>
                        <option value="failure" <?php echo $status === 'failure' ? 'selected' : ''; ?>>No Finalizadas</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="client_id" class="form-label">Cliente</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $clientId == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($client['name']) . ' - ' . escape($client['business_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filtrar
                    </button>
                    <a href="completed-visits.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Visits List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Visitas Completadas</h5>
        </div>
        <div class="card-body">
            <?php if (count($visits) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ticket</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visits as $visit): ?>
                                <?php
                                // Calculate duration
                                $startTime = new DateTime($visit['start_time']);
                                $endTime = new DateTime($visit['end_time']);
                                $duration = $startTime->diff($endTime);
                                ?>
                                <tr>
                                    <td><?php echo $visit['id']; ?></td>
                                    <td>
                                        <a href="ticket-detail.php?id=<?php echo $visit['ticket_id']; ?>" class="text-decoration-none">
                                            #<?php echo $visit['ticket_id']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong><?php echo escape($visit['client_name']); ?></strong><br>
                                        <small><?php echo escape($visit['business_name']); ?></small>
                                    </td>
                                    <td>
                                        <strong>Inicio:</strong> <?php echo formatDateTime($visit['start_time']); ?><br>
                                        <strong>Fin:</strong> <?php echo formatDateTime($visit['end_time']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($duration->days > 0) {
                                                echo $duration->format('%d días, %h h, %i min');
                                            } else {
                                                echo $duration->format('%h h, %i min');
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($visit['completion_status'] === 'success'): ?>
                                            <span class="badge bg-success">Finalizada con Éxito</span>
                                        <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                            <span class="badge bg-danger">Finalizada sin Éxito</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Estado Desconocido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="visit_completed.php?id=<?php echo $visit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No hay visitas completadas que coincidan con los filtros</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics -->
    <?php
    // Get statistics
    $totalVisits = count($visits);
    $successfulVisits = 0;
    $failedVisits = 0;
    $totalDuration = 0;
    
    foreach ($visits as $visit) {
        if ($visit['completion_status'] === 'success') {
            $successfulVisits++;
        } else {
            $failedVisits++;
        }
        
        $startTime = new DateTime($visit['start_time']);
        $endTime = new DateTime($visit['end_time']);
        $durationMinutes = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 60;
        $totalDuration += $durationMinutes;
    }
    
    $avgDuration = $totalVisits > 0 ? round($totalDuration / $totalVisits) : 0;
    $successRate = $totalVisits > 0 ? round(($successfulVisits / $totalVisits) * 100) : 0;
    ?>
    
    <?php if ($totalVisits > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-card bg-dark-primary p-3 rounded">
                                    <h6>Total de Visitas</h6>
                                    <h3><?php echo $totalVisits; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-dark-success p-3 rounded">
                                    <h6>Visitas Exitosas</h6>
                                    <h3><?php echo $successfulVisits; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-dark-danger p-3 rounded">
                                    <h6>Visitas No Completadas</h6>
                                    <h3><?php echo $failedVisits; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-dark-info p-3 rounded">
                                    <h6>Tasa de Éxito</h6>
                                    <h3><?php echo $successRate; ?>%</h3>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="stat-card bg-dark-secondary p-3 rounded">
                                    <h6>Duración Promedio de Visita</h6>
                                    <h3>
                                        <?php 
                                            $hours = floor($avgDuration / 60);
                                            $minutes = $avgDuration % 60;
                                            echo $hours > 0 ? "$hours horas, $minutes minutos" : "$minutes minutos"; 
                                        ?>
                                    </h3>
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
