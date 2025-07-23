<?php
/**
 * Script para verificar y corregir el estado de finalización de las visitas
 */
require_once 'includes/init.php';

// Obtener la conexión a la base de datos
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Verificar si hay visitas con estado de finalización incorrecto
    $query = $pdo->query("
        SELECT v.*, t.title 
        FROM visits v 
        JOIN tickets t ON v.ticket_id = t.id 
        WHERE v.end_time IS NOT NULL 
        ORDER BY v.id DESC
    ");
    $visits = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedCount = 0;
    $visitDetails = [];
    
    // Mostrar resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verificación de Estado de Finalización</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h2>Verificación de Estado de Finalización</h2>
                </div>
                <div class='card-body'>
                    <h4>Visitas Finalizadas:</h4>
                    <div class='table-responsive'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ticket</th>
                                    <th>Fecha Fin</th>
                                    <th>Estado</th>
                                    <th>Motivo Fallo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>";
    
    foreach ($visits as $visit) {
        $endTime = date('d/m/Y H:i', strtotime($visit['end_time']));
        $status = $visit['completion_status'] ?? 'No definido';
        $hasFailureReason = !empty($visit['failure_reason']);
        
        // Determinar si necesita corrección
        $needsCorrection = $hasFailureReason && $status !== 'failure';
        
        if ($needsCorrection) {
            $visitDetails[] = $visit;
        }
        
        echo "<tr>
                <td>{$visit['id']}</td>
                <td>#{$visit['ticket_id']} - " . htmlspecialchars($visit['title']) . "</td>
                <td>{$endTime}</td>
                <td>" . htmlspecialchars($status) . "</td>
                <td>" . (empty($visit['failure_reason']) ? '<em>N/A</em>' : htmlspecialchars($visit['failure_reason'])) . "</td>
                <td>";
        
        if ($needsCorrection) {
            echo "<form method='post' action=''>
                    <input type='hidden' name='visit_id' value='{$visit['id']}'>
                    <button type='submit' name='fix_status' class='btn btn-sm btn-warning'>Corregir a 'failure'</button>
                  </form>";
        } else {
            echo "<span class='text-success'>OK</span>";
        }
        
        echo "</td>
              </tr>";
    }
    
    echo "      </tbody>
                        </table>
                    </div>";
    
    // Procesar solicitud de corrección
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_status']) && isset($_POST['visit_id'])) {
        $visitId = $_POST['visit_id'];
        
        // Actualizar el estado a 'failure'
        $stmt = $pdo->prepare("UPDATE visits SET completion_status = 'failure' WHERE id = ? AND failure_reason IS NOT NULL");
        $stmt->execute([$visitId]);
        $updatedCount = $stmt->rowCount();
        
        if ($updatedCount > 0) {
            echo "<div class='alert alert-success mt-4'>
                    Se ha corregido el estado de finalización de la visita #{$visitId} a 'failure'.
                  </div>";
        }
    }
    
    // Mostrar botón para corregir todas las visitas con motivo de fallo
    if (count($visitDetails) > 0) {
        echo "<div class='mt-4'>
                <form method='post' action=''>
                    <input type='hidden' name='fix_all' value='1'>
                    <button type='submit' class='btn btn-warning'>Corregir todas las visitas con motivo de fallo a 'failure'</button>
                </form>
              </div>";
    }
    
    // Procesar solicitud de corrección masiva
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_all'])) {
        $stmt = $pdo->prepare("UPDATE visits SET completion_status = 'failure' WHERE failure_reason IS NOT NULL AND (completion_status IS NULL OR completion_status = 'success')");
        $stmt->execute();
        $updatedCount = $stmt->rowCount();
        
        if ($updatedCount > 0) {
            echo "<div class='alert alert-success mt-4'>
                    Se han corregido {$updatedCount} visitas con estado incorrecto.
                  </div>";
        }
    }
    
    echo "    <div class='mt-4'>
                <h4>Corregir la API de finalización de visitas</h4>
                <p>El problema está en el archivo <code>api/end_visit.php</code>. La API siempre establece el estado de finalización como 'success', incluso cuando hay un motivo de fallo.</p>
                <form method='post' action=''>
                    <input type='hidden' name='fix_api' value='1'>
                    <button type='submit' class='btn btn-primary'>Corregir la API de finalización</button>
                </form>
              </div>";
    
    // Procesar solicitud de corrección de API
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_api'])) {
        // Leer el archivo actual
        $apiFile = '../api/end_visit.php';
        $content = file_get_contents($apiFile);
        
        // Buscar y reemplazar el código problemático
        $oldCode = "'completion_status' => 'success'  // Agregar estado de finalización";
        $newCode = "'completion_status' => isset(\$_POST['failure_reason']) && !empty(\$_POST['failure_reason']) ? 'failure' : 'success'";
        
        if (strpos($content, $oldCode) !== false) {
            $content = str_replace($oldCode, $newCode, $content);
            file_put_contents($apiFile, $content);
            echo "<div class='alert alert-success mt-4'>
                    Se ha corregido la API de finalización de visitas. Ahora establecerá el estado 'failure' cuando se proporcione un motivo de fallo.
                  </div>";
        } else {
            echo "<div class='alert alert-warning mt-4'>
                    No se pudo encontrar el código exacto para reemplazar. Por favor, edite manualmente el archivo <code>api/end_visit.php</code>.
                  </div>";
        }
    }
    
    echo "    <div class='mt-4 text-center'>
                <a href='admin/visits.php' class='btn btn-secondary me-2'>Ir a Visitas</a>
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
