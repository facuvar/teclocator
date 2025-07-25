/**
 * Main JavaScript file for the Elevator Repair Ticket System
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize map if map container exists
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        initMap(mapContainer);
    }
    
    // Initialize QR scanner if scanner container exists
    const qrScannerContainer = document.getElementById('qr-scanner');
    if (qrScannerContainer) {
        initQRScanner(qrScannerContainer);
    }
    
    // Initialize geolocation check if needed
    const geoCheckButton = document.getElementById('check-location');
    if (geoCheckButton) {
        geoCheckButton.addEventListener('click', checkGeolocation);
    }
});

/**
 * Initialize Leaflet map
 */
function initMap(container, centerLat = null, centerLng = null, markers = []) {
    // Default to a central location if no coordinates provided
    const lat = centerLat || -34.603722;
    const lng = centerLng || -58.381592;
    
    // Create map
    const map = L.map(container).setView([lat, lng], 13);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add markers if provided
    if (markers.length > 0) {
        markers.forEach(marker => {
            L.marker([marker.lat, marker.lng])
                .addTo(map)
                .bindPopup(marker.popup || '');
        });
    } else if (centerLat && centerLng) {
        // Add a single marker at the center if coordinates were provided
        L.marker([centerLat, centerLng]).addTo(map);
    }
    
    // Make the map refresh when it becomes visible (for tabs/modals)
    map.invalidateSize();
    
    return map;
}

/**
 * Initialize QR code scanner
 */
function initQRScanner(container) {
    const html5QrCode = new Html5Qrcode("qr-scanner");
    const qrResultElement = document.getElementById('qr-result');
    const startScanButton = document.getElementById('start-scan');
    const stopScanButton = document.getElementById('stop-scan');
    
    if (startScanButton) {
        startScanButton.addEventListener('click', function() {
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            // Start scanning
            html5QrCode.start(
                { facingMode: "environment" }, 
                config, 
                onScanSuccess
            ).catch(error => {
                console.error("QR Scanner error:", error);
                alert("Error al iniciar el escáner: " + error);
            });
            
            // Show stop button, hide start button
            startScanButton.classList.add('d-none');
            stopScanButton.classList.remove('d-none');
        });
    }
    
    if (stopScanButton) {
        stopScanButton.addEventListener('click', function() {
            html5QrCode.stop().then(() => {
                // Show start button, hide stop button
                startScanButton.classList.remove('d-none');
                stopScanButton.classList.add('d-none');
            }).catch(error => {
                console.error("Error stopping QR scanner:", error);
            });
        });
    }
    
    // QR code scan success handler
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning
        html5QrCode.stop();
        
        // Show start button, hide stop button
        if (startScanButton && stopScanButton) {
            startScanButton.classList.remove('d-none');
            stopScanButton.classList.add('d-none');
        }
        
        // Display result
        if (qrResultElement) {
            qrResultElement.value = decodedText;
            
            // Trigger the qrScanned event
            const event = new CustomEvent('qrScanned', { 
                detail: { code: decodedText } 
            });
            document.dispatchEvent(event);
        }
        
        // If there's a form to submit after scanning, submit it
        const qrForm = document.getElementById('qr-form');
        if (qrForm) {
            qrForm.submit();
        }
    }
}

/**
 * Check geolocation and compare with target coordinates
 */
function checkGeolocation() {
    const targetLat = parseFloat(document.getElementById('target-lat').value);
    const targetLng = parseFloat(document.getElementById('target-lng').value);
    const maxDistance = 50; // Maximum distance in meters
    const resultElement = document.getElementById('geo-result');
    const visitButton = document.getElementById('start-visit-btn');
    
    if (!navigator.geolocation) {
        updateGeoResult(resultElement, 'Tu navegador no soporta geolocalización', 'danger');
        return;
    }
    
    updateGeoResult(resultElement, 'Obteniendo ubicación...', 'info');
    
    navigator.geolocation.getCurrentPosition(
        // Success callback
        function(position) {
            const currentLat = position.coords.latitude;
            const currentLng = position.coords.longitude;
            
            // Calculate distance between current position and target
            const distance = calculateDistance(
                currentLat, currentLng,
                targetLat, targetLng
            );
            
            // Update map with current position if map exists
            const mapContainer = document.getElementById('location-map');
            if (mapContainer) {
                const map = initMap(mapContainer, currentLat, currentLng, [
                    { lat: currentLat, lng: currentLng, popup: 'Tu ubicación actual' },
                    { lat: targetLat, lng: targetLng, popup: 'Ubicación del cliente' }
                ]);
                
                // Draw a line between the two points
                const polyline = L.polyline([
                    [currentLat, currentLng],
                    [targetLat, targetLng]
                ], { color: 'blue' }).addTo(map);
                
                // Fit the map to show both markers
                map.fitBounds(polyline.getBounds());
            }
            
            // Check if within range
            if (distance <= maxDistance) {
                updateGeoResult(
                    resultElement, 
                    `¡Ubicación validada! Estás a ${Math.round(distance)} metros del cliente.`, 
                    'success'
                );
                
                // Enable the start visit button if it exists
                if (visitButton) {
                    visitButton.disabled = false;
                }
                
                // Store location data in hidden fields for form submission
                document.getElementById('current-lat').value = currentLat;
                document.getElementById('current-lng').value = currentLng;
                document.getElementById('distance').value = distance;
            } else {
                updateGeoResult(
                    resultElement, 
                    `Estás demasiado lejos. Distancia: ${Math.round(distance)} metros (máximo permitido: ${maxDistance} metros)`, 
                    'danger'
                );
                
                // Disable the start visit button
                if (visitButton) {
                    visitButton.disabled = true;
                }
            }
        },
        // Error callback
        function(error) {
            let errorMessage;
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = "Usuario denegó la solicitud de geolocalización.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = "Información de ubicación no disponible.";
                    break;
                case error.TIMEOUT:
                    errorMessage = "Tiempo de espera agotado para obtener la ubicación.";
                    break;
                case error.UNKNOWN_ERROR:
                    errorMessage = "Error desconocido al obtener la ubicación.";
                    break;
            }
            updateGeoResult(resultElement, errorMessage, 'danger');
        },
        // Options
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

/**
 * Calculate distance between two coordinates in meters using the Haversine formula
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distance in meters
}

/**
 * Update geolocation result element
 */
function updateGeoResult(element, message, type) {
    if (!element) return;
    
    element.textContent = message;
    element.className = `alert alert-${type}`;
}
