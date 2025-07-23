<?php
/**
 * API endpoint to end a visit
 */
require_once '../includes/init.php';

// Configurar encabezados para API
header('Content-Type: application/json; charset=UTF-8');
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
$visitId = $_POST['visit_id'] ?? null;
$comments = $_POST['comments'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$failureReason = $_POST['failure_reason'] ?? '';
$clientDistance = isset($_POST['client_distance']) ? floatval($_POST['client_distance']) : null;

// Debug - Registrar los datos recibidos
error_log("Datos recibidos en end_visit.php: " . print_r($_POST, true));

try {
    // Validate required fields
    if (!$visitId) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de visita no proporcionado',
            'debug' => [
                'post_data' => $_POST
            ]
        ]);
        exit;
    }

    // Validate location data
    if (!$latitude || !$longitude) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos de ubicación no proporcionados',
            'debug' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]
        ]);
        exit;
    }

    // Get database connection
    $db = Database::getInstance();

    // Verificar si tenemos un ID de visita válido
    $visit = $db->selectOne("
        SELECT v.*, t.client_id, t.technician_id, c.latitude as client_latitude, c.longitude as client_longitude 
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        WHERE v.id = ? AND v.end_time IS NULL
    ", [$visitId]);

    // Si no se encontró la visita, verificar si pertenece al técnico actual
    if (!$visit) {
        echo json_encode([
            'success' => false,
            'message' => 'Esta visita no existe o ya ha sido finalizada',
            'debug' => [
                'visit_id' => $visitId
            ]
        ]);
        exit;
    }

    // Verificar si la visita pertenece al técnico actual
    if ($visit['technician_id'] != $technicianId) {
        echo json_encode([
            'success' => false,
            'message' => 'Esta visita no está asignada a usted',
            'debug' => [
                'visit_technician_id' => $visit['technician_id'],
                'current_technician_id' => $technicianId
            ]
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
    $clientLat = (float)str_replace(',', '.', $visit['client_latitude']);
    $clientLng = (float)str_replace(',', '.', $visit['client_longitude']);

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
                'message' => "Usted se encuentra a {$distance} metros del cliente. Debe estar a menos de {$maxDistance} metros para finalizar una visita."
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
                'message' => "Usted se encuentra a {$distance} metros del cliente. Debe estar a menos de {$maxDistance} metros para finalizar una visita."
            ]);
            exit;
        }
    }

    // Update the visit
    $updateData = [
        'end_time' => getCurrentDateTime(),
        'comments' => $comments,  
        'latitude' => $latitude,  
        'longitude' => $longitude,
        'completion_status' => !empty($failureReason) ? 'failure' : 'success'
    ];

    // Agregar el motivo de fallo si existe
    if (!empty($failureReason)) {
        $updateData['failure_reason'] = $failureReason;
    }

    // Ejecutar la actualización de la visita
    $db->update('visits', $updateData, 'id = ?', [$visitId]);
    
    // Update ticket status to completed
    $db->update('tickets', ['status' => 'completed'], 'id = ?', [$visit['ticket_id']]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Visita finalizada correctamente'
    ]);

} catch (Exception $e) {
    // Registrar el error
    error_log("Error en end_visit.php: " . $e->getMessage());
    
    // Devolver respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
