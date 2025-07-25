<?php
/**
 * Script para eliminar todos los clientes de la base de datos
 * IMPORTANTE: Este script eliminará también todos los tickets y visitas relacionados
 * debido a las restricciones de clave foránea
 */
require_once 'includes/init.php';

// Requerir autenticación de administrador
$auth = new Auth();
$auth->requireAdmin();

// Obtener conexión a la base de datos
$db = Database::getInstance();
$connection = $db->getConnection();

try {
    // Iniciar transacción para asegurar la integridad de los datos
    $connection->beginTransaction();
    
    // 1. Eliminar todas las visitas (primero porque tienen foreign key a tickets)
    $stmt = $connection->query("DELETE FROM visits");
    $visitCount = $stmt->rowCount();
    
    // 2. Eliminar todos los tickets (porque tienen foreign key a clientes)
    $stmt = $connection->query("DELETE FROM tickets");
    $ticketCount = $stmt->rowCount();
    
    // 3. Eliminar todos los clientes
    $stmt = $connection->query("DELETE FROM clients");
    $clientCount = $stmt->rowCount();
    
    // Confirmar transacción
    $connection->commit();
    
    // Mensaje de éxito
    $message = "Se han eliminado $clientCount clientes, $ticketCount tickets y $visitCount visitas correctamente.";
    $alertType = "success";
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $connection->rollBack();
    
    // Mensaje de error
    $message = "Error al eliminar los datos: " . $e->getMessage();
    $alertType = "danger";
}

// Incluir header
$pageTitle = "Restablecer Clientes";
include_once 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Restablecer Clientes</h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Resultado</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-<?php echo $alertType; ?>">
                    <?php echo $message; ?>
                </div>
                
                <div class="mt-4">
                    <a href="admin/clients.php" class="btn btn-primary me-2">
                        <i class="bi bi-people"></i> Ir a Clientes
                    </a>
                    <a href="admin/import_clients.php" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Importar Clientes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
