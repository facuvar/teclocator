<?php
/**
 * Scan QR Code Page (Standalone version without main.js dependencies)
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireLogin();
$auth->requireTechnician();

// Get user info
$userId = $_SESSION['user_id'];

// Page title
$pageTitle = 'Escanear QR';

// Custom styles
$customStyles = '
<style>
    .map-container {
        height: 300px;
        width: 100%;
        border-radius: 5px;
    }
    #reader {
        width: 100%;
        height: 300px;
        border-radius: 5px;
        overflow: hidden;
    }
    #scan-result {
        margin-top: 20px;
    }
    #processing {
        z-index: 9999;
    }
</style>
';

// Custom head content
$customHead = '
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4"><i class="bi bi-qr-code-scan"></i> Escanear Código QR</h1>
            
            <!-- Scanner Container -->
            <div id="scanner-container">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Escáner de Código QR</h5>
                    </div>
                    <div class="card-body">
                        <div id="reader"></div>
                        <div class="mt-3">
                            <button id="start-scan" class="btn btn-primary">
                                <i class="bi bi-camera"></i> Iniciar Escáner
                            </button>
                            <button id="stop-scan" class="btn btn-danger d-none">
                                <i class="bi bi-stop-circle"></i> Detener Escáner
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Scan Error -->
                <div id="scan-error" class="alert alert-danger mt-4 d-none">
                    <i class="bi bi-exclamation-triangle"></i> <span id="error-message">Error al escanear el código QR.</span>
                </div>
                
                <!-- Instructions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Instrucciones</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Haga clic en "Iniciar Escáner"</li>
                            <li>Permita el acceso a la cámara cuando se le solicite</li>
                            <li>Apunte la cámara al código QR que está físicamente pegado en el ascensor</li>
                            <li>Una vez escaneado, seleccione el ticket correspondiente a este cliente</li>
                            <li>Se verificará su ubicación</li>
                            <li>Si está dentro del rango permitido (200 metros), podrá iniciar o finalizar la visita</li>
                            <li>Al finalizar, indique si el problema quedó solucionado o no</li>
                        </ol>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Los códigos QR genéricos están físicamente pegados en los ascensores. Debe estar a menos de 200 metros de la ubicación registrada del cliente para iniciar o finalizar una visita.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Auto-processing message -->
            <div id="auto-process" class="card d-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status"></div>
                        <div>
                            <h5 class="mb-1">Obteniendo su ubicación...</h5>
                            <p class="mb-0">Por favor, permita el acceso a su ubicación cuando se le solicite.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Scan Result -->
            <div id="scan-result" class="d-none">
                <div id="ticket-info" class="card mb-4">
                    <!-- Ticket info will be inserted here -->
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title">Estado de Ubicación</h5>
                </div>
                <div class="card-body">
                    <div id="location-status" class="alert alert-warning">
                        <i class="bi bi-geo-alt"></i> Esperando a obtener su ubicación...
                    </div>
                    <div id="distance-info" class="d-none">
                        <p>Distancia al cliente: <span id="distance-value">--</span> metros</p>
                    </div>
                    <div id="scan-map" class="map-container mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Visit Start Form -->
    <div id="start-visit-form" class="card mt-4 d-none">
        <div class="card-header">
            <h5 class="card-title">Iniciar Visita</h5>
        </div>
        <div class="card-body">
            <form id="form-start-visit">
                <input type="hidden" id="ticket-id" name="ticket_id">
                <div class="mb-3">
                    <label for="start-notes" class="form-label">Notas Iniciales (Opcional)</label>
                    <textarea class="form-control" id="start-notes" name="start_notes" rows="3"></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-play-circle"></i> Iniciar Visita
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Visit End Form -->
    <div id="end-visit-form" class="card mt-4 d-none">
        <div class="card-header">
            <h5 class="card-title">Finalizar Visita</h5>
        </div>
        <div class="card-body">
            <form id="form-end-visit">
                <input type="hidden" id="visit-id" name="visit_id">
                
                <div class="mb-3">
                    <label class="form-label">¿Se completó la reparación?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="completion_status" id="status-success" value="success" checked>
                        <label class="form-check-label" for="status-success">
                            Sí, la reparación fue exitosa
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="completion_status" id="status-failure" value="failure">
                        <label class="form-check-label" for="status-failure">
                            No, no se pudo completar la reparación
                        </label>
                    </div>
                </div>
                
                <div id="success-fields">
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comentarios sobre la reparación</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3" required></textarea>
                    </div>
                </div>
                
                <div id="failure-fields" class="d-none">
                    <div class="mb-3">
                        <label for="failure-reason" class="form-label">Motivo de no finalización</label>
                        <textarea class="form-control" id="failure-reason" name="failure_reason" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Finalizar Visita
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Processing Indicator -->
    <div id="processing" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background-color: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="card p-4">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <h5 id="processing-message">Procesando...</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HTML5 QR Code Scanner Script -->
<script src="https://unpkg.com/html5-qrcode@2.2.1/html5-qrcode.min.js"></script>

<?php include_once 'scan_qr_footer.php'; ?>
