<?php
/**
 * Script para reiniciar las visitas (solo para pruebas)
 */
require_once './includes/init.php';

// Verificar que el usuario esté autenticado (técnico o administrador)
if (!$auth->isLoggedIn()) {
    // Redirigir a la página de inicio de sesión
    header('Location: login.php');
    exit;
}

// Obtener conexión a la base de datos
$db = Database::getInstance();

// Obtener el ID del usuario
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Determinar qué visitas reiniciar
$whereClause = "";
$params = [];

if ($userRole === 'technician') {
    // Si es técnico, solo reiniciar sus visitas
    $whereClause = "WHERE t.technician_id = ?";
    $params = [$userId];
} else {
    // Si es administrador, reiniciar todas las visitas
    $whereClause = "";
}

// Buscar visitas activas
$query = "
    SELECT v.id, v.ticket_id, t.description, u.name as technician_name
    FROM visits v
    JOIN tickets t ON v.ticket_id = t.id
    JOIN users u ON t.technician_id = u.id
    $whereClause
    AND v.end_time IS NULL
";

$activeVisits = $db->query($query, $params);

// Convertir a array para poder usar count()
$activeVisitsArray = is_array($activeVisits) ? $activeVisits : iterator_to_array($activeVisits);

// Mostrar mensaje de confirmación
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Actualizar todas las visitas activas para marcarlas como finalizadas
    foreach ($activeVisitsArray as $visit) {
        $db->update('visits', [
            'end_time' => date('Y-m-d H:i:s'),
            'comments' => 'Visita finalizada automáticamente por el sistema de reinicio',
            'completion_status' => 'success'
        ], 'id = ?', [$visit['id']]);
        
        // Actualizar el estado del ticket a 'pending'
        $db->update('tickets', ['status' => 'pending'], 'id = ?', [$visit['ticket_id']]);
    }
    
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visitas Reiniciadas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-5">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h2>Visitas reiniciadas correctamente</h2>
                </div>
                <div class="card-body">
                    <p class="lead">Se han finalizado todas las visitas activas. Ahora puede iniciar nuevas visitas.</p>
                    <div class="mt-4 text-center">';
    
    if ($userRole === 'technician') {
        echo '<a href="technician/dashboard.php" class="btn btn-primary">Volver al Dashboard</a>';
    } else {
        echo '<a href="admin/dashboard.php" class="btn btn-primary">Volver al Dashboard</a>';
    }
    
    echo '      </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Mostrar formulario de confirmación
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiniciar Visitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2>Reiniciar Visitas</h2>
            </div>
            <div class="card-body">
                <?php if (count($activeVisitsArray) > 0): ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading">¡Atención!</h4>
                        <p>Está a punto de finalizar <?php echo count($activeVisitsArray); ?> visitas activas:</p>
                        <ul>
                            <?php foreach ($activeVisitsArray as $visit): ?>
                                <li>
                                    Ticket #<?php echo $visit['ticket_id']; ?> - 
                                    <?php echo htmlspecialchars($visit['description']); ?> 
                                    (Técnico: <?php echo htmlspecialchars($visit['technician_name']); ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p>Esta acción no se puede deshacer.</p>
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="confirm" value="yes">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-danger">Finalizar todas las visitas</button>
                            <?php if ($userRole === 'technician'): ?>
                                <a href="technician/dashboard.php" class="btn btn-secondary">Cancelar</a>
                            <?php else: ?>
                                <a href="admin/dashboard.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>No hay visitas activas para finalizar.</p>
                    </div>
                    <div class="mt-3">
                        <?php if ($userRole === 'technician'): ?>
                            <a href="technician/dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
                        <?php else: ?>
                            <a href="admin/dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
