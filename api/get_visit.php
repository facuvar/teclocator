<?php
/**
 * API endpoint to get visit information
 */
require_once '../includes/init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Require authentication
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Get visit ID from request
$visitId = $_GET['id'] ?? null;

if (!$visitId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de visita no proporcionado'
    ]);
    exit;
}

// Get database connection
$db = Database::getInstance();

// Get visit information
$visit = $db->selectOne("
    SELECT v.*, 
           t.id as ticket_id, t.description, t.status, t.technician_id,
           c.name as client_name, c.business_name, c.address,
           c.latitude as client_latitude, c.longitude as client_longitude
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE v.id = ?
", [$visitId]);

if (!$visit) {
    echo json_encode([
        'success' => false,
        'message' => 'Visita no encontrada'
    ]);
    exit;
}

// Check if user is authorized to view this visit
if ($auth->isTechnician() && $visit['technician_id'] != $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'No está autorizado para ver esta visita'
    ]);
    exit;
}

// Return visit information
echo json_encode([
    'success' => true,
    'visit' => $visit
]);
