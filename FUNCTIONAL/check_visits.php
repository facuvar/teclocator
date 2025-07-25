<?php
/**
 * Script para verificar los valores de las visitas en la base de datos
 */
require_once 'includes/init.php';

// Obtener la conexión a la base de datos
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Verificar la estructura de la tabla visits
    $columnsQuery = $pdo->query("SHOW COLUMNS FROM visits");
    $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las visitas con detalles
    $visitsQuery = $pdo->query("
        SELECT 
            v.*, 
            t.id as ticket_id,
            t.title as ticket_title,
            u.name as technician_name
        FROM 
            visits v
        LEFT JOIN 
            tickets t ON v.ticket_id = t.id
        LEFT JOIN 
            users u ON t.technician_id = u.id
        ORDER BY 
            v.id DESC
    ");
    $visits = $visitsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Mostrar resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verificación de Visitas</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h2>Verificación de Visitas</h2>
                </div>
                <div class='card-body'>
                    <h4>Estructura de la tabla 'visits':</h4>
                    <div class='table-responsive mb-4'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Tipo</th>
                                    <th>Nulo</th>
                                    <th>Clave</th>
                                    <th>Predeterminado</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>";
    
    foreach ($columns as $column) {
        echo "<tr>
                <td>{$column['Field']}</td>
                <td>{$column['Type']}</td>
                <td>{$column['Null']}</td>
                <td>{$column['Key']}</td>
                <td>{$column['Default']}</td>
                <td>{$column['Extra']}</td>
              </tr>";
    }
    
    echo "      </tbody>
                        </table>
                    </div>
                    
                    <h4>Datos de Visitas:</h4>
                    <div class='table-responsive'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ticket</th>
                                    <th>Técnico</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Comentarios</th>
                                    <th>Motivo Fallo</th>
                                </tr>
                            </thead>
                            <tbody>";
    
    foreach ($visits as $visit) {
        $startTime = $visit['start_time'] ? date('d/m/Y H:i', strtotime($visit['start_time'])) : 'No iniciada';
        $endTime = $visit['end_time'] ? date('d/m/Y H:i', strtotime($visit['end_time'])) : 'En progreso';
        
        // Determinar el estado real
        $status = 'Desconocido';
        if (!$visit['start_time']) {
            $status = 'No iniciada';
        } elseif (!$visit['end_time']) {
            $status = 'En progreso';
        } elseif (isset($visit['completion_status'])) {
            if ($visit['completion_status'] === 'success') {
                $status = 'Finalizada con éxito';
            } elseif ($visit['completion_status'] === 'failure') {
                $status = 'Finalizada sin éxito';
            } else {
                $status = 'Estado: ' . $visit['completion_status'];
            }
        }
        
        echo "<tr>
                <td>{$visit['id']}</td>
                <td>#{$visit['ticket_id']} - " . htmlspecialchars($visit['ticket_title']) . "</td>
                <td>" . htmlspecialchars($visit['technician_name']) . "</td>
                <td>{$startTime}</td>
                <td>{$endTime}</td>
                <td>{$status}</td>
                <td>" . (empty($visit['comments']) ? '<em>Sin comentarios</em>' : htmlspecialchars($visit['comments'])) . "</td>
                <td>" . (empty($visit['failure_reason']) ? '<em>N/A</em>' : htmlspecialchars($visit['failure_reason'])) . "</td>
              </tr>";
    }
    
    echo "      </tbody>
                        </table>
                    </div>
                    
                    <div class='mt-4 text-center'>
                        <a href='admin/visits.php' class='btn btn-primary me-2'>Ir a Visitas</a>
                        <a href='admin/tickets.php' class='btn btn-secondary'>Ir a Tickets</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
