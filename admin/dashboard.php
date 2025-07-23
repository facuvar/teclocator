<?php
/**
 * Admin Dashboard
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get counts for dashboard stats
$clientsCount = $db->selectOne("SELECT COUNT(*) as count FROM clients")['count'];
$techniciansCount = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'technician'")['count'];
$ticketsCount = $db->selectOne("SELECT COUNT(*) as count FROM tickets")['count'];

// Get tickets by status
$ticketsByStatus = $db->select("
    SELECT status, COUNT(*) as count 
    FROM tickets 
    GROUP BY status
");

// Format status counts for easier access
$statusCounts = [
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'not_completed' => 0
];

foreach ($ticketsByStatus as $status) {
    $statusCounts[$status['status']] = $status['count'];
}

// Get recent tickets
$recentTickets = $db->select("
    SELECT t.id, t.description, t.status, t.created_at, 
           c.name as client_name, u.name as technician_name
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Page title
$pageTitle = 'Dashboard de Administrador';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
    
    <!-- Stats Row -->
    <div class="row">
        <!-- Clients Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-primary">
                <div class="stat-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $clientsCount; ?></h3>
                    <p>Clientes</p>
                </div>
            </div>
        </div>
        
        <!-- Technicians Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-secondary">
                <div class="stat-icon">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $techniciansCount; ?></h3>
                    <p>Técnicos</p>
                </div>
            </div>
        </div>
        
        <!-- Tickets Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-info">
                <div class="stat-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $ticketsCount; ?></h3>
                    <p>Tickets Totales</p>
                </div>
            </div>
        </div>
        
        <!-- Completed Visits Stat -->
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-dark-success">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $statusCounts['completed']; ?></h3>
                    <p>Visitas Completadas</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tickets Status Chart and Recent Tickets -->
    <div class="row mt-4">
        <!-- Tickets Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Estado de Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning">Pendientes</span>
                                    </td>
                                    <td><?php echo $statusCounts['pending']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['pending'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-info">En Progreso</span>
                                    </td>
                                    <td><?php echo $statusCounts['in_progress']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['in_progress'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-success">Completados</span>
                                    </td>
                                    <td><?php echo $statusCounts['completed']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['completed'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">No Completados</span>
                                    </td>
                                    <td><?php echo $statusCounts['not_completed']; ?></td>
                                    <td>
                                        <?php 
                                        $percent = $ticketsCount > 0 ? round(($statusCounts['not_completed'] / $ticketsCount) * 100) : 0;
                                        echo $percent . '%'; 
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Tickets Recientes</h5>
                    <a href="tickets.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentTickets) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Técnico</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo $ticket['id']; ?></td>
                                            <td><?php echo escape($ticket['client_name']); ?></td>
                                            <td><?php echo escape($ticket['technician_name']); ?></td>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No hay tickets recientes</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-chat-dots text-success"></i> Notificaciones
                    </h5>
                    <p class="card-text">Gestione y pruebe las notificaciones por WhatsApp para técnicos.</p>
                    <div class="d-grid gap-2">
                        <a href="../test_whatsapp_direct.php" class="btn btn-outline-success">
                            <i class="bi bi-tools"></i> Diagnóstico de WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Herramientas de WhatsApp</h5>
                    <p class="card-text">Herramientas para diagnosticar y probar el sistema de notificaciones por WhatsApp.</p>
                    <a href="<?php echo BASE_URL; ?>admin/whatsapp_debug.php" class="btn btn-primary">
                        <i class="bi bi-whatsapp"></i> Diagnóstico Avanzado de WhatsApp
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="clients.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> Nuevo Cliente
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="technicians.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> Nuevo Técnico
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="tickets.php?action=create" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-plus-circle"></i> Nuevo Ticket
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="visits.php" class="btn btn-primary d-block mb-2">
                                <i class="bi bi-clipboard-check"></i> Ver Visitas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
