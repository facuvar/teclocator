<?php
/**
 * QR Code scanning page for technicians
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireTechnician();

// Get database connection
$db = Database::getInstance();

// Get current user (technician) information
$technicianId = $_SESSION['user_id'];
$technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);

// Page title
$pageTitle = 'Escanear Código QR';

// Check if we have action parameters from select_ticket.php
$action = $_GET['action'] ?? null;
$ticketId = $_GET['ticket_id'] ?? null;
$visitId = $_GET['visit_id'] ?? null;
$latitude = $_GET['lat'] ?? null;
$longitude = $_GET['lng'] ?? null;

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
    
    <?php if ($action && ($ticketId || $visitId)): ?>
    <!-- Auto-processing from direct links -->
    <div id="auto-process" class="card mb-4 <?php echo ($latitude && $longitude) ? '' : 'd-none'; ?>">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Procesando Automáticamente</h5>
        </div>
        <div class="card-body">
            <div class="text-center">
                <div class="spinner-border mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="lead" id="auto-process-message">
                    <?php if ($action == 'start'): ?>
                        Iniciando visita para el ticket #<?php echo $ticketId; ?>...
                    <?php else: ?>
                        Finalizando visita #<?php echo $visitId; ?>...
                    <?php endif; ?>
                </p>
                <p>Por favor espere mientras verificamos su ubicación...</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card" id="scanner-container">
                <div class="card-header">
                    <h5 class="card-title">Escáner de Código QR</h5>
                </div>
                <div class="card-body">
                    <div id="reader-container">
                        <div id="reader"></div>
                        <div class="text-center mt-3">
                            <button id="start-scan" class="btn btn-primary">
                                <i class="bi bi-camera"></i> Iniciar Escáner
                            </button>
                            <button id="stop-scan" class="btn btn-danger d-none">
                                <i class="bi bi-stop-circle"></i> Detener Escáner
                            </button>
                        </div>
                    </div>
                    
                    <div id="scan-result" class="mt-3 d-none">
                        <div class="alert alert-success">
                            <h5>Código QR escaneado correctamente</h5>
                            <p id="ticket-info">Cargando información del ticket...</p>
                        </div>
                    </div>
                    
                    <div id="scan-error" class="mt-3 d-none">
                        <div class="alert alert-danger">
                            <h5>Error al escanear</h5>
                            <p id="error-message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Instrucciones</h5>
                </div>
                <div class="card-body">
                    <p>Para iniciar o finalizar una visita, siga estos pasos:</p>
                    <ol>
                        <li>Haga clic en "Iniciar Escáner"</li>
                        <li>Permita el acceso a la cámara cuando se le solicite</li>
                        <li>Apunte la cámara al código QR que está físicamente pegado en el ascensor</li>
                        <li>Una vez escaneado, se verificará su ubicación</li>
                        <li>Si está dentro del rango permitido (100 metros), podrá iniciar o finalizar la visita</li>
                        <li>Al finalizar, indique si el problema quedó solucionado o no</li>
                    </ol>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Los códigos QR están físicamente pegados en los ascensores. Debe estar a menos de 100 metros de la ubicación registrada del cliente para iniciar o finalizar una visita.
                    </div>
                </div>
            </div>
            
            <!-- Location Status -->
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

<!-- Custom Map Script -->
<script src="scan_map.js"></script>

<script>
// Disable main.js map initialization
window.initMap = function() { 
    console.log('Map initialization from main.js prevented');
    return null; 
};

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    // Variables
    let html5QrCode;
    let visitInProgress = false;
    let currentVisitId = null;
    
    // Elements
    const startScanBtn = document.getElementById('start-scan');
    const stopScanBtn = document.getElementById('stop-scan');
    const scanResult = document.getElementById('scan-result');
    const scanError = document.getElementById('scan-error');
    const errorMessage = document.getElementById('error-message');
    const ticketInfo = document.getElementById('ticket-info');
    const startVisitForm = document.getElementById('start-visit-form');
    const endVisitForm = document.getElementById('end-visit-form');
    const ticketIdInput = document.getElementById('ticket-id');
    const visitIdInput = document.getElementById('visit-id');
    const processingOverlay = document.getElementById('processing');
    const processingMessage = document.getElementById('processing-message');
    
    // Initialize QR Code scanner
    function initScanner() {
        html5QrCode = new Html5Qrcode("reader");
        
        startScanBtn.addEventListener('click', startScanner);
        stopScanBtn.addEventListener('click', stopScanner);
        
        // Check if there's an active visit for this technician
        checkActiveVisit();
        
        // Initialize form event listeners
        document.getElementById('form-start-visit').addEventListener('submit', startVisit);
        document.getElementById('form-end-visit').addEventListener('submit', endVisit);
        
        // Toggle between success and failure fields
        document.querySelectorAll('input[name="completion_status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'success') {
                    document.getElementById('success-fields').classList.remove('d-none');
                    document.getElementById('failure-fields').classList.add('d-none');
                    document.getElementById('failure-reason').removeAttribute('required');
                    document.getElementById('comments').setAttribute('required', 'required');
                } else {
                    document.getElementById('success-fields').classList.add('d-none');
                    document.getElementById('failure-fields').classList.remove('d-none');
                    document.getElementById('comments').removeAttribute('required');
                    document.getElementById('failure-reason').setAttribute('required', 'required');
                }
            });
        });
        
        // Auto-process from select_ticket.php or direct links
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        const ticketId = urlParams.get('ticket_id');
        const visitId = urlParams.get('visit_id');
        const lat = urlParams.get('lat');
        const lng = urlParams.get('lng');
        
        // If we have action and ticket_id/visit_id but no location, get the location first
        if (action && (ticketId || visitId) && (!lat || !lng)) {
            // Show a message to the user
            document.getElementById('scanner-container').classList.add('d-none');
            document.getElementById('auto-process').classList.remove('d-none');
            
            // Get current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const newLat = position.coords.latitude;
                        const newLng = position.coords.longitude;
                        
                        // Update URL with location parameters
                        let newUrl = window.location.href;
                        if (newUrl.includes('?')) {
                            newUrl += `&lat=${newLat}&lng=${newLng}`;
                        } else {
                            newUrl += `?lat=${newLat}&lng=${newLng}`;
                        }
                        
                        // Redirect to the same page with location parameters
                        window.location.href = newUrl;
                    },
                    function(error) {
                        // Show error message
                        document.getElementById('scanner-container').classList.remove('d-none');
                        document.getElementById('auto-process').classList.add('d-none');
                        errorMessage.textContent = 'Error al obtener la ubicación. Por favor, permita el acceso a su ubicación e intente nuevamente.';
                        scanError.classList.remove('d-none');
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                // Show error message
                document.getElementById('scanner-container').classList.remove('d-none');
                document.getElementById('auto-process').classList.add('d-none');
                errorMessage.textContent = 'Su navegador no soporta geolocalización. Por favor, utilice un navegador compatible.';
                scanError.classList.remove('d-none');
            }
        }
        // If we have action and ticket_id/visit_id and location, process automatically
        else if (action && (ticketId || visitId) && lat && lng) {
            console.log('Auto-processing with parameters');
            
            // Set current position from URL parameters
            currentPosition = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };
            
            console.log('Current position set from URL:', currentPosition);
            
            // Process based on action
            setTimeout(() => {
                if (action === 'start' && ticketId) {
                    console.log('Processing start action with ticket ID:', ticketId);
                    
                    // Get ticket information directly
                    fetch(`../api/get_ticket.php?id=${ticketId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const ticket = data.ticket;
                                
                                // Set client position
                                setClientPosition(
                                    parseFloat(ticket.client_latitude),
                                    parseFloat(ticket.client_longitude)
                                );
                                
                                // Show start visit form
                                document.getElementById('ticket-id').value = ticketId;
                                showStartVisitForm();
                            } else {
                                errorMessage.textContent = data.message || 'Error al obtener información del ticket';
                                scanError.classList.remove('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            errorMessage.textContent = 'Error de conexión al obtener información del ticket';
                            scanError.classList.remove('d-none');
                        });
                } else if (action === 'end' && visitId) {
                    console.log('Processing end action with visit ID:', visitId);
                    
                    // Get visit information
                    fetch(`../api/get_visit.php?id=${visitId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Set client position from visit data
                                setClientPosition(
                                    parseFloat(data.visit.latitude),
                                    parseFloat(data.visit.longitude)
                                );
                                
                                // Show end visit form
                                document.getElementById('visit-id').value = visitId;
                                showEndVisitForm();
                            } else {
                                errorMessage.textContent = data.message || 'Error al obtener información de la visita';
                                scanError.classList.remove('d-none');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            errorMessage.textContent = 'Error de conexión al obtener información de la visita';
                            scanError.classList.remove('d-none');
                        });
                }
            }, 1000);
        }
        // If we have action and ticket_id/visit_id but no location, get the location first
        else {
            // Initialize scanner normally
            startScanBtn.classList.remove('d-none');
        }
    }
    
    // Start QR Code scanner
    function startScanner() {
        startScanBtn.classList.add('d-none');
        stopScanBtn.classList.remove('d-none');
        
        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.error('Error starting scanner:', err);
            errorMessage.textContent = 'Error al iniciar el escáner. Por favor, permita el acceso a la cámara e intente nuevamente.';
            scanError.classList.remove('d-none');
            startScanBtn.classList.remove('d-none');
            stopScanBtn.classList.add('d-none');
        });
    }
    
    // Stop QR Code scanner
    function stopScanner() {
        html5QrCode.stop().then(() => {
            startScanBtn.classList.remove('d-none');
            stopScanBtn.classList.add('d-none');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
    
    // Handle successful QR code scan
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanner
        stopScanner();
        
        // Hide error message
        scanError.classList.add('d-none');
        
        // Process QR code
        try {
            // Check if it's a ticket QR code
            if (decodedText.startsWith('TICKET:')) {
                const ticketId = decodedText.replace('TICKET:', '');
                processTicketQR(ticketId);
            } else {
                errorMessage.textContent = 'Código QR no válido. Por favor, escanee un código QR de ticket.';
                scanError.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error processing QR code:', error);
            errorMessage.textContent = 'Error al procesar el código QR. Por favor, intente nuevamente.';
            scanError.classList.remove('d-none');
        }
    }
    
    // Handle QR code scan failure
    function onScanFailure(error) {
        // Do nothing on failure
    }
    
    // Process ticket QR code
    function processTicketQR(ticketId) {
        showProcessing(`Procesando ticket #${ticketId}...`);
        
        // Get ticket information
        fetch(`../api/get_ticket.php?id=${ticketId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ticket = data.ticket;
                    
                    // Set client position
                    setClientPosition(
                        parseFloat(ticket.client_latitude),
                        parseFloat(ticket.client_longitude)
                    );
                    
                    // Show appropriate form based on active visit
                    if (data.active_visit) {
                        // Show end visit form
                        document.getElementById('visit-id').value = data.active_visit.id;
                        showEndVisitForm();
                    } else {
                        // Show start visit form
                        document.getElementById('ticket-id').value = ticketId;
                        showStartVisitForm();
                    }
                    
                    hideProcessing();
                } else {
                    errorMessage.textContent = data.message || 'Error al obtener información del ticket';
                    scanError.classList.remove('d-none');
                    hideProcessing();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'Error de conexión al obtener información del ticket';
                scanError.classList.remove('d-none');
                hideProcessing();
            });
    }
    
    // Check if there's an active visit for this technician
    function checkActiveVisit() {
        fetch('../api/check_active_visit.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.active_visit) {
                    visitInProgress = true;
                    currentVisitId = data.active_visit.id;
                    
                    // Show alert about active visit
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-info alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="bi bi-info-circle"></i> Tiene una visita activa en curso. 
                        <a href="active_visit.php?id=${data.active_visit.id}" class="alert-link">Ver detalles</a> o 
                        <a href="scan.php?action=end&visit_id=${data.active_visit.id}" class="alert-link">Finalizar visita</a>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.container-fluid').prepend(alertDiv);
                }
            })
            .catch(error => {
                console.error('Error checking active visit:', error);
            });
    }
    
    // Start a new visit
    function startVisit(e) {
        e.preventDefault();
        showProcessing('Iniciando visita...');
        
        const formData = new FormData(e.target);
        
        // Add current location to form data
        if (currentPosition) {
            formData.append('latitude', currentPosition.lat);
            formData.append('longitude', currentPosition.lng);
        } else {
            hideProcessing();
            alert('No se pudo obtener su ubicación actual. Por favor, permita el acceso a su ubicación e intente nuevamente.');
            return;
        }
        
        // Add client position to form data
        if (clientPosition) {
            formData.append('client_latitude', clientPosition.lat);
            formData.append('client_longitude', clientPosition.lng);
            formData.append('client_distance', distanceToClient);
        }
        
        // Debug information
        console.log('Enviando datos:', {
            ticket_id: formData.get('ticket_id'),
            latitude: formData.get('latitude'),
            longitude: formData.get('longitude'),
            client_latitude: formData.get('client_latitude'),
            client_longitude: formData.get('client_longitude'),
            client_distance: formData.get('client_distance'),
            start_notes: formData.get('start_notes')
        });
        
        // Send request to start visit
        fetch('../api/start_visit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Respuesta del servidor:', response);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    console.log('Texto recibido:', text);
                    throw new Error('Respuesta del servidor inválida');
                }
            });
        })
        .then(data => {
            hideProcessing();
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                // Show success message
                alert('Visita iniciada correctamente');
                
                // Redirect to active visit page
                window.location.href = '../technician/active_visit.php?id=' + data.visit_id;
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            hideProcessing();
            console.error('Error:', error);
            alert('Error de conexión al servidor. Verifique la consola para más detalles.');
        });
    }
    
    // End a visit
    function endVisit(e) {
        e.preventDefault();
        showProcessing('Finalizando visita...');
        
        const formData = new FormData(e.target);
        
        // Add current location to form data
        if (currentPosition) {
            formData.append('latitude', currentPosition.lat);
            formData.append('longitude', currentPosition.lng);
        } else {
            hideProcessing();
            alert('No se pudo obtener su ubicación actual. Por favor, permita el acceso a su ubicación e intente nuevamente.');
            return;
        }
        
        // Send request to end visit
        fetch('../api/end_visit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProcessing();
            
            if (data.success) {
                // Show success message
                alert('Visita finalizada correctamente');
                
                // Redirect to completed visits page
                window.location.href = '../technician/completed-visits.php';
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            hideProcessing();
            console.error('Error:', error);
            alert('Error de conexión al servidor');
        });
    }
    
    // Show processing overlay
    function showProcessing(message) {
        processingMessage.textContent = message || 'Procesando...';
        processingOverlay.classList.remove('d-none');
    }
    
    // Hide processing overlay
    function hideProcessing() {
        processingOverlay.classList.add('d-none');
    }
    
    // Show start visit form
    function showStartVisitForm() {
        document.getElementById('start-visit-form').classList.remove('d-none');
        document.getElementById('end-visit-form').classList.add('d-none');
        document.getElementById('scan-result').classList.remove('d-none');
    }
    
    // Show end visit form
    function showEndVisitForm() {
        document.getElementById('end-visit-form').classList.remove('d-none');
        document.getElementById('start-visit-form').classList.add('d-none');
        document.getElementById('scan-result').classList.remove('d-none');
    }
    
    // Hide start visit form
    function hideStartVisitForm() {
        document.getElementById('start-visit-form').classList.add('d-none');
    }
    
    // Hide end visit form
    function hideEndVisitForm() {
        document.getElementById('end-visit-form').classList.add('d-none');
    }
    
    // Initialize scanner
    initScanner();
});
</script>

<?php include_once '../templates/footer.php'; ?>
