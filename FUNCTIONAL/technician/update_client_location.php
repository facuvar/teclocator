<?php
/**
 * Script para actualizar la ubicación del cliente para pruebas
 */
require_once '../includes/init.php';

// Requiere autenticación
$auth->requireLogin();

// Obtener la base de datos
$db = Database::getInstance();

// Ticket ID a actualizar
$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 4;

// Nuevas coordenadas (se pueden pasar como parámetros)
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : -34.4784896;
$longitude = isset($_GET['lng']) ? floatval($_GET['lng']) : -58.4974336;

// Actualizar el ticket
try {
    // Primero obtenemos el cliente asociado al ticket
    $ticket = $db->selectOne("SELECT client_id FROM tickets WHERE id = ?", [$ticketId]);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
        exit;
    }
    
    // Actualizamos las coordenadas del cliente
    $result = $db->update(
        "UPDATE clients SET latitude = ?, longitude = ? WHERE id = ?",
        [$latitude, $longitude, $ticket['client_id']]
    );
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Ubicación del cliente actualizada correctamente',
            'data' => [
                'ticket_id' => $ticketId,
                'client_id' => $ticket['client_id'],
                'latitude' => $latitude,
                'longitude' => $longitude
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la ubicación del cliente']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
