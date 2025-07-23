<?php
/**
 * API endpoint to check if the technician has an active visit
 */
require_once '../includes/init.php';

// Require technician authentication
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'technician') {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get database connection
$db = Database::getInstance();

// Check for active visits
$activeVisit = $db->selectOne("
    SELECT v.*, t.id as ticket_id, t.description, c.name as client_name, c.address as client_address
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN clients c ON t.client_id = c.id
    WHERE t.technician_id = ? AND v.end_time IS NULL
    ORDER BY v.start_time DESC
    LIMIT 1
", [$technicianId]);

// Return result
echo json_encode([
    'success' => true,
    'active_visit' => $activeVisit
]);
