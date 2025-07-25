<?php
/**
 * API endpoint to get ticket information
 */
require_once '../includes/init.php';

// Require authentication
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Get ticket ID from request
$ticketId = $_GET['id'] ?? null;

if (!$ticketId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de ticket no proporcionado'
    ]);
    exit;
}

// Get database connection
$db = Database::getInstance();

// Get ticket information
$ticket = $db->selectOne("
    SELECT t.*, 
           c.name as client_name, 
           c.business_name, 
           c.address as client_address,
           c.latitude as client_latitude,
           c.longitude as client_longitude,
           u.name as technician_name,
           u.id as technician_id
    FROM tickets t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON t.technician_id = u.id
    WHERE t.id = ?
", [$ticketId]);

if (!$ticket) {
    echo json_encode([
        'success' => false,
        'message' => 'Ticket no encontrado'
    ]);
    exit;
}

// Check if there's an active visit for this ticket
$activeVisit = $db->selectOne("
    SELECT * FROM visits 
    WHERE ticket_id = ? AND end_time IS NULL
    ORDER BY id DESC LIMIT 1
", [$ticketId]);

// Return ticket information
echo json_encode([
    'success' => true,
    'ticket' => $ticket,
    'active_visit' => $activeVisit
]);
