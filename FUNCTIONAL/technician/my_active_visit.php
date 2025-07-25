<?php
/**
 * Redirección automática a la visita activa del técnico
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Find active visit for this technician
$activeVisit = $db->selectOne("
    SELECT v.id
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    WHERE t.technician_id = ? AND v.end_time IS NULL
    ORDER BY v.start_time DESC
    LIMIT 1
", [$technicianId]);

if ($activeVisit) {
    // Redirect to active visit page
    redirect('active_visit.php?id=' . $activeVisit['id']);
} else {
    // No active visit found
    flash('No tiene visitas activas en este momento.', 'info');
    redirect('dashboard.php');
}
?>
