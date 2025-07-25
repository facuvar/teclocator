/**
 * Custom map handling for scan.php
 * This script handles the map initialization and updates for the QR code scanning page
 */

// Variables
let mapInstance = null;
let currentPosition = null;
let clientPosition = null;
let distanceToClient = null;
let technicianMarker = null;
let clientMarker = null;
let locationValid = false;

// Initialize map
function initMap() {
    try {
        console.log('Initializing map...');
        
        // Get map container
        const mapContainer = document.getElementById('scan-map');
        
        // Check if map container exists
        if (!mapContainer) {
            console.error('Map container not found');
            return false;
        }
        
        // Clear the map container
        mapContainer.innerHTML = '';
        
        // Create the map
        mapInstance = L.map('scan-map', {
            center: [-34.603722, -58.381592],
            zoom: 13
        });
        
        // Add the tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapInstance);
        
        // Force map to recalculate size
        mapInstance.invalidateSize();
        
        console.log('Map initialized successfully');
        return true;
    } catch (e) {
        console.error('Error initializing map:', e);
        return false;
    }
}

// Get current location
function getCurrentLocation() {
    const locationStatus = document.getElementById('location-status');
    locationStatus.className = 'alert alert-warning';
    locationStatus.innerHTML = '<i class="bi bi-geo-alt"></i> Obteniendo su ubicación...';
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                console.log('Got current position:', position.coords);
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                updateMap();
                updateLocationStatus();
            },
            (error) => {
                console.error('Error getting location:', error);
                locationStatus.className = 'alert alert-danger';
                locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error al obtener su ubicación. Por favor, permita el acceso a su ubicación.';
            },
            { enableHighAccuracy: true }
        );
    } else {
        locationStatus.className = 'alert alert-danger';
        locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Su navegador no soporta geolocalización.';
    }
}

// Update map with current location
function updateMap() {
    if (!mapInstance) {
        console.error('Map not initialized');
        if (!initMap()) {
            return;
        }
    }
    
    console.log('Updating map...');
    console.log('Current position:', currentPosition);
    console.log('Client position:', clientPosition);
    
    // Force map to recalculate size
    mapInstance.invalidateSize();
    
    // Clear existing markers
    mapInstance.eachLayer(function(layer) {
        if (layer instanceof L.Marker || layer instanceof L.Polyline) {
            mapInstance.removeLayer(layer);
        }
    });
    
    // Re-add the base tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapInstance);
    
    // Add technician marker if available
    if (currentPosition) {
        technicianMarker = L.marker([currentPosition.lat, currentPosition.lng])
            .addTo(mapInstance)
            .bindPopup("Su ubicación actual");
        
        console.log('Added technician marker at:', currentPosition);
    }
    
    // Add client marker if available
    if (clientPosition) {
        clientMarker = L.marker([clientPosition.lat, clientPosition.lng])
            .addTo(mapInstance)
            .bindPopup("Ubicación del cliente");
        
        console.log('Added client marker at:', clientPosition);
    }
    
    // Draw line between technician and client
    if (currentPosition && clientPosition) {
        // Calculate distance
        distanceToClient = calculateDistance(
            currentPosition.lat, currentPosition.lng,
            clientPosition.lat, clientPosition.lng
        );
        
        console.log('Calculated distance:', distanceToClient, 'meters');
        
        // Update distance info
        const distanceInfo = document.getElementById('distance-info');
        const distanceValue = document.getElementById('distance-value');
        distanceInfo.classList.remove('d-none');
        distanceValue.textContent = Math.round(distanceToClient);
        
        // Add debug information to the page
        document.getElementById('distance-info').innerHTML = `
            <p>Distancia al cliente: <span id="distance-value">${Math.round(distanceToClient)}</span> metros</p>
            <div class="alert alert-info">
                <strong>Información de depuración:</strong><br>
                Tu posición: ${currentPosition.lat.toFixed(7)}, ${currentPosition.lng.toFixed(7)}<br>
                Posición del cliente: ${clientPosition.lat.toFixed(7)}, ${clientPosition.lng.toFixed(7)}
            </div>
        `;
        
        // Fit bounds to show both markers
        const bounds = L.latLngBounds(
            [currentPosition.lat, currentPosition.lng],
            [clientPosition.lat, clientPosition.lng]
        );
        mapInstance.fitBounds(bounds);
        
        // Draw line between points
        L.polyline([
            [currentPosition.lat, currentPosition.lng],
            [clientPosition.lat, clientPosition.lng]
        ], {color: 'blue', dashArray: '5, 10'}).addTo(mapInstance);
        
        console.log('Added route line between markers');
    } else if (currentPosition) {
        // Only technician position available
        mapInstance.setView([currentPosition.lat, currentPosition.lng], 15);
    } else if (clientPosition) {
        // Only client position available
        mapInstance.setView([clientPosition.lat, clientPosition.lng], 15);
    }
    
    // Force map to recalculate size again
    mapInstance.invalidateSize();
}

// Update location status based on distance to client
function updateLocationStatus() {
    const locationStatus = document.getElementById('location-status');
    
    if (!currentPosition || !clientPosition) {
        locationStatus.className = 'alert alert-warning';
        locationStatus.innerHTML = '<i class="bi bi-geo-alt"></i> Esperando a obtener su ubicación...';
        return;
    }
    
    // Debug information
    console.log('Posición actual:', currentPosition);
    console.log('Posición del cliente:', clientPosition);
    
    // Calculate distance
    distanceToClient = calculateDistance(
        currentPosition.lat, currentPosition.lng,
        clientPosition.lat, clientPosition.lng
    );
    
    console.log('Distancia calculada:', distanceToClient, 'metros');
    
    // Update distance info
    const distanceInfo = document.getElementById('distance-info');
    const distanceValue = document.getElementById('distance-value');
    distanceInfo.classList.remove('d-none');
    distanceValue.textContent = Math.round(distanceToClient);
    
    // Add debug information to the page
    document.getElementById('distance-info').innerHTML = `
        <p>Distancia al cliente: <span id="distance-value">${Math.round(distanceToClient)}</span> metros</p>
        <div class="alert alert-info">
            <strong>Información de depuración:</strong><br>
            Tu posición: ${currentPosition.lat.toFixed(7)}, ${currentPosition.lng.toFixed(7)}<br>
            Posición del cliente: ${clientPosition.lat.toFixed(7)}, ${clientPosition.lng.toFixed(7)}
        </div>
    `;
    
    // Update status based on distance
    if (distanceToClient <= 100) {  
        locationStatus.className = 'alert alert-success';
        locationStatus.innerHTML = '<i class="bi bi-check-circle"></i> Usted se encuentra dentro del rango permitido (100 metros)';
        locationValid = true;
    } else {
        locationStatus.className = 'alert alert-danger';
        locationStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Usted se encuentra fuera del rango permitido. Debe estar a menos de 100 metros del cliente.';
        locationValid = false;
    }
    
    // Update map
    updateMap();
}

// Calculate distance between two points in meters (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth radius in meters
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

// Set client position
function setClientPosition(lat, lng) {
    clientPosition = {
        lat: parseFloat(lat),
        lng: parseFloat(lng)
    };
    
    console.log('Client position set:', clientPosition);
    updateMap();
    updateLocationStatus();
}

// Initialize map with delay
window.addEventListener('load', function() {
    setTimeout(function() {
        initMap();
        getCurrentLocation();
    }, 1000);
});
