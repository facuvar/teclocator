<?php
/**
 * Página de acceso rápido a herramientas de WhatsApp
 */
require_once 'includes/init.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Título de la página
$pageTitle = 'Herramientas de WhatsApp';
include_once 'templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tools"></i> Diagnóstico de WhatsApp
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Herramienta para diagnosticar problemas con las notificaciones de WhatsApp. 
                        Permite probar el envío de notificaciones a técnicos con tickets reales y 
                        genera archivos de log detallados para identificar errores.
                    </p>
                    <div class="d-grid">
                        <a href="debug_whatsapp_notification.php" class="btn btn-primary">
                            <i class="bi bi-tools"></i> Abrir Herramienta de Diagnóstico
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-chat-text"></i> Prueba de Plantillas
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Herramienta para probar las plantillas de WhatsApp utilizadas para notificaciones.
                        Permite personalizar todos los parámetros y enviar mensajes de prueba para verificar
                        que las plantillas estén configuradas correctamente.
                    </p>
                    <div class="d-grid">
                        <a href="test_ticket_template.php" class="btn btn-success">
                            <i class="bi bi-chat-text"></i> Probar Plantillas de WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Información de WhatsApp Business
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Requisitos para las notificaciones de WhatsApp:</h6>
                    <ol>
                        <li>Tener una cuenta de WhatsApp Business API configurada</li>
                        <li>Tener plantillas aprobadas para enviar notificaciones</li>
                        <li>Contar con un token de acceso válido</li>
                        <li>Asegurarse de que los números de teléfono de los técnicos estén correctamente formateados</li>
                    </ol>
                    
                    <h6>Plantilla "nuevo_ticket":</h6>
                    <p>Esta plantilla debe estar configurada en WhatsApp Business con el siguiente formato:</p>
                    
                    <div class="p-3 border rounded bg-light">
                        <p>🔔 <strong>Nuevo Ticket #{{1}}</strong></p>
                        <p>Cliente: {{2}}</p>
                        <p>Descripción: {{3}}</p>
                        <p>Ver detalles: {{4}}</p>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> Si estás teniendo problemas con las notificaciones, 
                        utiliza las herramientas de diagnóstico para identificar el origen del problema.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
