<?php
/**
 * Script para eliminar todas las visitas y tickets de la base de datos
 */
require_once 'includes/init.php';

// Requerir autenticación de administrador
$auth->requireAdmin();

// Obtener conexión a la base de datos
$db = Database::getInstance();

try {
    // 1. Eliminar todas las visitas (primero porque tienen foreign key a tickets)
    $stmt = $db->query("DELETE FROM visits");
    $visitCount = $stmt->rowCount();
    
    // 2. Eliminar todos los tickets
    $stmt = $db->query("DELETE FROM tickets");
    $ticketCount = $stmt->rowCount();
    
    // Mensaje de éxito
    $message = "Se han eliminado $visitCount visitas y $ticketCount tickets correctamente.";
    $alertType = "success";
} catch (Exception $e) {
    // Mensaje de error
    $message = "Error al eliminar los datos: " . $e->getMessage();
    $alertType = "danger";
}

// Incluir header
$pageTitle = "Restablecer Datos";
include_once 'templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Restablecer Datos</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?php echo $alertType; ?>">
                        <?php echo $message; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="admin/dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
