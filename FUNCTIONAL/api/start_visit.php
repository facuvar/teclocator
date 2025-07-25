<?php
/**
 * API endpoint to start a new visit
 */
require_once '../includes/init.php';

// Configurar encabezados para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Capturar errores PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode([
        'success' => false,
        'message' => 'Error PHP: ' . $errstr,
        'details' => [
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

// Require technician authentication
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'technician') {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Get technician ID
$technicianId = $_SESSION['user_id'];

// Get form data
$ticketId = $_POST['ticket_id'] ?? null;
$startNotes = $_POST['start_notes'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$clientDistance = isset($_POST['client_distance']) ? floatval($_POST['client_distance']) : null;

// Debug - Registrar los datos recibidos
error_log("Datos recibidos en start_visit.php: " . print_r($_POST, true));

// Validate required fields
if (!$ticketId) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de ticket no proporcionado'
    ]);
    exit;
}

// Validate location data
if (!$latitude || !$longitude) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos de ubicación no proporcionados'
    ]);
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance();

    // Verify that the technician is assigned to this ticket
    $ticket = $db->selectOne("
        SELECT t.*, c.latitude as client_latitude, c.longitude as client_longitude 
        FROM tickets t
        JOIN clients c ON t.client_id = c.id
        WHERE t.id = ? AND t.technician_id = ?
    ", [$ticketId, $technicianId]);

    if (!$ticket) {
        echo json_encode([
            'success' => false,
            'message' => 'Este ticket no está asignado a usted o no existe'
        ]);
        exit;
    }

    // Calculate distance between technician and client location
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Radio de la Tierra en metros
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $latDiffRad = deg2rad($lat2 - $lat1);
        $lonDiffRad = deg2rad($lon2 - $lon1);
        
        $a = sin($latDiffRad/2) * sin($latDiffRad/2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($lonDiffRad/2) * sin($lonDiffRad/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c; // Distancia en metros
    }

    // Convert coordinates to proper format
    $techLat = (float)str_replace(',', '.', $latitude);
    $techLng = (float)str_replace(',', '.', $longitude);
    $clientLat = (float)str_replace(',', '.', $ticket['client_latitude']);
    $clientLng = (float)str_replace(',', '.', $ticket['client_longitude']);

    // Validate coordinates
    if (!is_numeric($techLat) || !is_numeric($techLng) || !is_numeric($clientLat) || !is_numeric($clientLng)) {
        echo json_encode([
            'success' => false,
            'message' => 'Coordenadas inválidas',
            'debug' => [
                'techLat' => $techLat,
                'techLng' => $techLng,
                'clientLat' => $clientLat,
                'clientLng' => $clientLng
            ]
        ]);
        exit;
    }

    // Validar la distancia al cliente
    $maxDistance = 200; // 200 metros en producción

    // Si se proporciona la distancia desde el cliente, usarla
    if (isset($_POST['client_distance'])) {
        $distance = floatval($_POST['client_distance']);
        
        if ($distance > $maxDistance) {
            echo json_encode([
                'success' => false,
                'message' => "Usted se encuentra a {$distance} metros del cliente. Debe estar a menos de {$maxDistance} metros para iniciar una visita."
            ]);
            exit;
        }
    } 
    // Si no se proporciona la distancia, calcularla en el servidor
    else if (isset($_POST['client_latitude']) && isset($_POST['client_longitude'])) {
        $clientLat = floatval($_POST['client_latitude']);
        $clientLng = floatval($_POST['client_longitude']);
        
        // Calcular distancia usando la fórmula de Haversine
        $distance = calculateDistance($techLat, $techLng, $clientLat, $clientLng);
        
        if ($distance > $maxDistance) {
            echo json_encode([
                'success' => false,
                'message' => "Usted se encuentra a {$distance} metros del cliente. Debe estar a menos de {$maxDistance} metros para iniciar una visita."
            ]);
            exit;
        }
    }

    // Check if there's already an active visit for this ticket
    $activeVisit = $db->selectOne("
        SELECT * FROM visits WHERE ticket_id = ? AND end_time IS NULL
    ", [$ticketId]);

    if ($activeVisit) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe una visita activa para este ticket'
        ]);
        exit;
    }

    // Check if there's an active visit for this technician
    $techActiveVisit = $db->selectOne("
        SELECT v.* FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        WHERE t.technician_id = ? AND v.end_time IS NULL
    ", [$technicianId]);

    if ($techActiveVisit) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya tiene una visita activa. Finalice esa visita antes de iniciar una nueva.'
        ]);
        exit;
    }

    // Create a new visit
    $visitData = [
        'ticket_id' => $ticketId,
        'start_time' => getCurrentDateTime(),
        'start_notes' => $startNotes,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];

    $visitId = $db->insert('visits', $visitData);

    if (!$visitId) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear la visita'
        ]);
        exit;
    }

    // Update ticket status to in_progress
    $db->update('tickets', ['status' => 'in_progress'], 'id = ?', [$ticketId]);

    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Visita iniciada correctamente',
        'visit_id' => $visitId
    ]);
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en start_visit.php: " . $e->getMessage());
    
    // Devolver respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
