<?php
/**
 * API Endpoint: Get Tickets Assigned to Technician
 * Returns all tickets assigned to the logged-in technician
 */
require_once __DIR__ . '/../includes/init.php';

// Para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a technician
if (!$auth->isLoggedIn() || !$auth->isTechnician()) {
    echo json_encode([
        'success' => false,
        'message' => 'Acceso no autorizado'
    ]);
    exit;
}

// Get technician ID
$technicianId = $_SESSION['user_id'];

try {
    // Get tickets assigned to this technician
    $query = "SELECT t.*, c.name as client_name, c.address, c.latitude as client_latitude, 
                c.longitude as client_longitude, u.name as technician_name
              FROM tickets t
              JOIN clients c ON t.client_id = c.id
              LEFT JOIN users u ON t.technician_id = u.id
              WHERE t.technician_id = ? AND t.status != 'closed'
              ORDER BY 
                CASE 
                    WHEN t.status = 'in_progress' THEN 1
                    WHEN t.status = 'assigned' THEN 2
                    ELSE 3
                END, 
                t.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$technicianId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay tickets, devolver un array vacío en lugar de null
    if (empty($tickets)) {
        echo json_encode([
            'success' => true,
            'tickets' => [],
            'message' => 'No tiene tickets asignados actualmente'
        ]);
        exit;
    }
    
    // Check for active visits for each ticket
    foreach ($tickets as &$ticket) {
        // Check if there's an active visit for this ticket
        $visitQuery = "SELECT * FROM visits 
                      WHERE ticket_id = ? AND end_time IS NULL 
                      ORDER BY start_time DESC LIMIT 1";
        $visitStmt = $db->prepare($visitQuery);
        $visitStmt->execute([$ticket['id']]);
        $activeVisit = $visitStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($activeVisit) {
            $ticket['active_visit'] = $activeVisit;
        }
    }
    
    // Return success with tickets data
    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Error in get_technician_tickets.php: ' . $e->getMessage());
    
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los tickets: ' . $e->getMessage()
    ]);
}
