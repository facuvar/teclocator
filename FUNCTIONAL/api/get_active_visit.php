<?php
/**
 * API endpoint to get an active visit by ticket ID
 */
require_once '../includes/init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Validate ticket_id parameter
if (!isset($_GET['ticket_id']) || !is_numeric($_GET['ticket_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de ticket no válido'
    ]);
    exit;
}

$ticketId = (int) $_GET['ticket_id'];

// Get database connection
$db = Database::getInstance();

// Get active visit for the ticket
$visit = $db->selectOne("
    SELECT v.*, 
           t.client_id, 
           t.description as ticket_description,
           c.latitude as client_latitude, 
           c.longitude as client_longitude,
           c.name as client_name,
           c.business_name
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE v.ticket_id = ? AND v.end_time IS NULL
    LIMIT 1
", [$ticketId]);

if (!$visit) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró una visita activa para este ticket'
    ]);
    exit;
}

// Return visit data
echo json_encode([
    'success' => true,
    'visit' => $visit
]);
