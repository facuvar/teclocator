<?php
/**
 * Clients management page for administrators
 */
require_once '../includes/init.php';

// Require admin authentication
$auth = new Auth();
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$clientId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update client
    if (isset($_POST['save_client'])) {
        // Construir la dirección completa a partir de los componentes
        $street = $_POST['street'] ?? '';
        $number = $_POST['number'] ?? '';
        $locality = $_POST['locality'] ?? '';
        $province = $_POST['province'] ?? '';
        $country = $_POST['country'] ?? '';
        
        // Construir dirección completa
        $address = $street;
        if (!empty($number)) $address .= " " . $number;
        if (!empty($locality)) $address .= ", " . $locality;
        if (!empty($province)) $address .= ", " . $province;
        if (!empty($country)) $address .= ", " . $country;
        
        $clientData = [
            'name' => $_POST['name'] ?? '',
            'business_name' => $_POST['business_name'] ?? '',
            'latitude' => $_POST['latitude'] ?? 0,
            'longitude' => $_POST['longitude'] ?? 0,
            'address' => $address,
            'phone' => $_POST['phone'] ?? '',
            'client_number' => $_POST['client_number'] ?? '',
            'group_vendor' => $_POST['group_vendor'] ?? ''
        ];
        
        // Validar datos obligatorios
        if (empty($clientData['name']) || empty($clientData['business_name'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Si las coordenadas están vacías, usar 0
            if (empty($clientData['latitude'])) $clientData['latitude'] = 0;
            if (empty($clientData['longitude'])) $clientData['longitude'] = 0;
            
            // Create or update
            if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                // Update existing client
                $db->update('clients', $clientData, 'id = ?', [$_POST['client_id']]);
                flash('Cliente actualizado correctamente.', 'success');
            } else {
                // Create new client
                $db->insert('clients', $clientData);
                flash('Cliente creado correctamente.', 'success');
            }
            
            // Redirect to list view
            redirect(BASE_URL . '/admin/clients.php');
        }
    }
    
    // Delete client
    if (isset($_POST['delete_client'])) {
        $clientId = $_POST['client_id'] ?? null;
        
        if ($clientId) {
            // Check if client has associated tickets
            $ticketsCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM tickets WHERE client_id = ?", 
                [$clientId]
            )['count'];
            
            if ($ticketsCount > 0) {
                flash('No se puede eliminar el cliente porque tiene tickets asociados.', 'danger');
            } else {
                $db->delete('clients', 'id = ?', [$clientId]);
                flash('Cliente eliminado correctamente.', 'success');
            }
        }
        
        // Redirect to list view
        redirect(BASE_URL . '/admin/clients.php');
    }
}

// Get client data for edit
$client = null;
if (($action === 'edit' || $action === 'view') && $clientId) {
    $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$clientId]);
    if (!$client) {
        flash('Cliente no encontrado.', 'danger');
        redirect(BASE_URL . '/admin/clients.php');
    }
    
    // Si estamos editando, extraer los componentes de la dirección
    if ($action === 'edit') {
        // Intentar extraer los componentes de la dirección
        $addressParts = explode(',', $client['address']);
        
        // Extraer calle y número (primera parte)
        $streetAndNumber = trim($addressParts[0] ?? '');
        $streetNumberParts = explode(' ', $streetAndNumber);
        $client['number'] = trim(end($streetNumberParts));
        $client['street'] = trim(implode(' ', array_slice($streetNumberParts, 0, -1)));
        
        // Si el número no es numérico, probablemente es parte de la calle
        if (!is_numeric($client['number'])) {
            $client['street'] = $streetAndNumber;
            $client['number'] = '';
        }
        
        // Extraer localidad, provincia y país
        $client['locality'] = trim($addressParts[1] ?? '');
        $client['province'] = trim($addressParts[2] ?? '');
        $client['country'] = trim($addressParts[3] ?? '');
    }
}

// Get all clients for list view
$clients = [];
$totalClients = 0;
$itemsPerPage = 20; // Número de clientes por página
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

if ($action === 'list') {
    // Verificar si hay un término de búsqueda
    $search = $_GET['search'] ?? '';
    $field = $_GET['field'] ?? 'all';
    
    if (!empty($search)) {
        // Buscar clientes que coincidan con el término de búsqueda
        $searchTerm = "%$search%";
        
        // Obtener el total de clientes que coinciden con la búsqueda
        if ($field === 'all') {
            $totalClients = $db->selectOne(
                "SELECT COUNT(*) as total FROM clients 
                 WHERE name LIKE ? 
                 OR business_name LIKE ? 
                 OR address LIKE ? 
                 OR client_number LIKE ? 
                 OR group_vendor LIKE ?",
                [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]
            )['total'];
        } else {
            $totalClients = $db->selectOne(
                "SELECT COUNT(*) as total FROM clients 
                 WHERE $field LIKE ?",
                [$searchTerm]
            )['total'];
        }
        
        // Obtener los clientes para la página actual
        if ($field === 'all') {
            $clients = $db->select(
                "SELECT * FROM clients 
                 WHERE name LIKE ? 
                 OR business_name LIKE ? 
                 OR address LIKE ? 
                 OR client_number LIKE ? 
                 OR group_vendor LIKE ?
                 ORDER BY name
                 LIMIT $itemsPerPage OFFSET $offset",
                [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]
            );
        } else {
            $clients = $db->select(
                "SELECT * FROM clients 
                 WHERE $field LIKE ?
                 ORDER BY name
                 LIMIT $itemsPerPage OFFSET $offset",
                [$searchTerm]
            );
        }
    } else {
        // Si no hay término de búsqueda, obtener el total de clientes
        $totalClients = $db->selectOne("SELECT COUNT(*) as total FROM clients")['total'];
        
        // Obtener los clientes para la página actual
        $clients = $db->select("SELECT * FROM clients ORDER BY name LIMIT $itemsPerPage OFFSET $offset");
    }
    
    // Calcular el número total de páginas
    $totalPages = ceil($totalClients / $itemsPerPage);
}

// Page title
$pageTitle = 'Gestión de Clientes'; // Valor por defecto
switch($action) {
    case 'create':
        $pageTitle = 'Crear Cliente';
        break;
    case 'edit':
        $pageTitle = 'Editar Cliente';
        break;
    case 'view':
        $pageTitle = 'Ver Cliente';
        break;
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Cliente
            </a>
        <?php else: ?>
            <a href="clients.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Clients List -->
        <div class="card">
            <div class="card-body">
                <!-- Buscador de clientes -->
                <div class="row mb-4">
                    <div class="col-md-8 mx-auto">
                        <form action="<?php echo BASE_URL; ?>/admin/clients.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <select name="field" class="form-select" style="min-width: 220px;">
                                    <option value="all" <?php echo (!isset($_GET['field']) || $_GET['field'] === 'all') ? 'selected' : ''; ?>>Todos los campos</option>
                                    <option value="name" <?php echo (isset($_GET['field']) && $_GET['field'] === 'name') ? 'selected' : ''; ?>>Nombre</option>
                                    <option value="business_name" <?php echo (isset($_GET['field']) && $_GET['field'] === 'business_name') ? 'selected' : ''; ?>>Razón Social</option>
                                    <option value="client_number" <?php echo (isset($_GET['field']) && $_GET['field'] === 'client_number') ? 'selected' : ''; ?>>Nro. Cliente</option>
                                    <option value="address" <?php echo (isset($_GET['field']) && $_GET['field'] === 'address') ? 'selected' : ''; ?>>Dirección</option>
                                    <option value="group_vendor" <?php echo (isset($_GET['field']) && $_GET['field'] === 'group_vendor') ? 'selected' : ''; ?>>Grupo/Vendedor</option>
                                </select>
                                <input type="text" name="search" class="form-control" placeholder="Buscar clientes..." value="<?php echo isset($_GET['search']) ? escape($_GET['search']) : ''; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/admin/clients.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Limpiar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Mostrar número de resultados si hay búsqueda -->
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <div class="alert alert-info">
                        Se encontraron <?php echo count($clients); ?> resultados para la búsqueda: <strong><?php echo escape($_GET['search']); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if (count($clients) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr class="table-dark">
                                    <th>ID</th>
                                    <th>Nro. Cliente</th>
                                    <th>Nombre</th>
                                    <th>Razón Social</th>
                                    <th>Dirección</th>
                                    <th>Grupo/Vendedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo $client['id']; ?></td>
                                        <td><?php echo escape($client['client_number']); ?></td>
                                        <td><?php echo escape($client['name']); ?></td>
                                        <td><?php echo escape($client['business_name']); ?></td>
                                        <td><?php echo escape($client['address']); ?></td>
                                        <td><?php echo escape($client['group_vendor']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=view&id=<?php echo $client['id']; ?>" class="btn btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $client['id']; ?>" class="btn btn-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" title="Eliminar" 
                                                        onclick="confirmDelete(<?php echo $client['id']; ?>, '<?php echo escape($client['name']); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <nav aria-label="Page navigation example">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-dark" href="?page=<?php echo $currentPage - 1; ?><?php if (isset($_GET['search'])) echo '&search=' . $_GET['search']; ?><?php if (isset($_GET['field'])) echo '&field=' . $_GET['field']; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php if ($i == $currentPage) echo 'active'; ?>">
                                    <a class="page-link pagination-dark" href="?page=<?php echo $i; ?><?php if (isset($_GET['search'])) echo '&search=' . $_GET['search']; ?><?php if (isset($_GET['field'])) echo '&field=' . $_GET['field']; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-dark" href="?page=<?php echo $currentPage + 1; ?><?php if (isset($_GET['search'])) echo '&search=' . $_GET['search']; ?><?php if (isset($_GET['field'])) echo '&field=' . $_GET['field']; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <!-- Estilos personalizados para la paginación -->
                    <style>
                        .pagination-dark {
                            background-color: #000;
                            color: #CCC;
                            border-color: #333;
                        }
                        .pagination-dark:hover {
                            background-color: #333;
                            color: #FFF;
                            border-color: #444;
                        }
                        .page-item.active .pagination-dark {
                            background-color: #333;
                            color: #FFF;
                            border-color: #444;
                        }
                    </style>
                <?php else: ?>
                    <p class="text-center">No hay clientes registrados</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Client Form -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>/admin/clients.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo $action === 'edit' ? escape($client['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="business_name" class="form-label">Razón Social *</label>
                            <input type="text" class="form-control" id="business_name" name="business_name" 
                                   value="<?php echo $action === 'edit' ? escape($client['business_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_number" class="form-label">Número de Cliente</label>
                            <input type="text" class="form-control" id="client_number" name="client_number" 
                                   value="<?php echo $action === 'edit' ? escape($client['client_number']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="group_vendor" class="form-label">Grupo/Vendedor</label>
                            <input type="text" class="form-control" id="group_vendor" name="group_vendor" 
                                   value="<?php echo $action === 'edit' ? escape($client['group_vendor']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="street" class="form-label">Calle</label>
                            <input type="text" class="form-control" id="street" name="street" 
                                   value="<?php echo $action === 'edit' ? escape($client['street']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="number" name="number" 
                                   value="<?php echo $action === 'edit' ? escape($client['number']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="locality" class="form-label">Localidad</label>
                            <input type="text" class="form-control" id="locality" name="locality" 
                                   value="<?php echo $action === 'edit' ? escape($client['locality']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="province" class="form-label">Provincia</label>
                            <input type="text" class="form-control" id="province" name="province" 
                                   value="<?php echo $action === 'edit' ? escape($client['province']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="country" class="form-label">País</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?php echo $action === 'edit' ? escape($client['country']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo $action === 'edit' ? escape($client['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitud</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" 
                                   value="<?php echo $action === 'edit' ? $client['latitude'] : ''; ?>">
                            <small class="form-text text-muted">Formato: -34.603722 (usar punto como separador decimal)</small>
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitud</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" 
                                   value="<?php echo $action === 'edit' ? $client['longitude'] : ''; ?>">
                            <small class="form-text text-muted">Formato: -58.381592 (usar punto como separador decimal)</small>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="d-grid gap-2 d-md-flex">
                                <a href="https://www.google.com/maps" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-geo-alt"></i> Abrir Google Maps
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="copyInstructions">
                                    <i class="bi bi-clipboard"></i> Instrucciones
                                </button>
                            </div>
                            <div class="alert alert-info mt-2 d-none" id="instructionsBox">
                                <h6>Cómo obtener coordenadas de Google Maps:</h6>
                                <ol class="mb-0">
                                    <li>Haz clic en "Abrir Google Maps"</li>
                                    <li>Busca la ubicación deseada</li>
                                    <li>Haz clic derecho en el punto exacto</li>
                                    <li>Selecciona "¿Qué hay aquí?"</li>
                                    <li>En la tarjeta que aparece abajo, verás las coordenadas (ej: -34.603722, -58.381592)</li>
                                    <li>Copia la latitud (primer número) y longitud (segundo número)</li>
                                    <li>Pega cada valor en su campo correspondiente</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Map for selecting location -->
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Ubicación en el Mapa</label>
                        <div id="map" class="map-container"></div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>/admin/clients.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" name="save_client" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- JavaScript for map interaction -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get input elements
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                
                // Normalize coordinate values (replace comma with dot)
                function normalizeCoordinate(value) {
                    if (!value) return null;
                    // Convert to string, replace comma with dot, and parse as float
                    return parseFloat(value.toString().replace(',', '.'));
                }
                
                // Get initial coordinates (with fallback to Buenos Aires)
                let initialLat = normalizeCoordinate(latInput.value);
                let initialLng = normalizeCoordinate(lngInput.value);
                
                // Use default coordinates if invalid
                if (isNaN(initialLat) || initialLat === null) initialLat = -34.603722;
                if (isNaN(initialLng) || initialLng === null) initialLng = -58.381592;
                
                console.log("Initial coordinates:", initialLat, initialLng);
                
                // Initialize map
                const map = L.map('map').setView([initialLat, initialLng], 13);
                
                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Add marker
                let marker = L.marker([initialLat, initialLng], {
                    draggable: true
                }).addTo(map);
                
                // Update input fields when marker is dragged
                marker.on('dragend', function() {
                    const position = marker.getLatLng();
                    latInput.value = position.lat.toFixed(8);
                    lngInput.value = position.lng.toFixed(8);
                    console.log("Marker dragged to:", position.lat, position.lng);
                });
                
                // Update marker when input fields change
                function updateMap() {
                    const lat = normalizeCoordinate(latInput.value);
                    const lng = normalizeCoordinate(lngInput.value);
                    
                    if (!isNaN(lat) && !isNaN(lng)) {
                        console.log("Updating map to:", lat, lng);
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], 13);
                    } else {
                        console.log("Invalid coordinates:", latInput.value, lngInput.value);
                    }
                }
                
                // Add event listeners
                latInput.addEventListener('input', updateMap);
                lngInput.addEventListener('input', updateMap);
                
                // Force map refresh when it becomes visible
                setTimeout(function() {
                    map.invalidateSize();
                    updateMap();
                }, 500);
            });
        </script>
        
    <?php elseif ($action === 'view' && $client): ?>
        <!-- View Client Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Información del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo escape($client['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Razón Social:</th>
                                <td><?php echo escape($client['business_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Número de Cliente:</th>
                                <td><?php echo escape($client['client_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Grupo/Vendedor:</th>
                                <td><?php echo escape($client['group_vendor']); ?></td>
                            </tr>
                            <tr>
                                <th>Dirección:</th>
                                <td><?php echo escape($client['address']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo escape($client['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Coordenadas:</th>
                                <td>
                                    Latitud: <?php echo $client['latitude']; ?><br>
                                    Longitud: <?php echo $client['longitude']; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <a href="<?php echo BASE_URL; ?>/admin/clients.php?action=edit&id=<?php echo $client['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Client Tickets -->
                <?php
                $clientTickets = $db->select("
                    SELECT t.id, t.description, t.status, t.created_at, u.name as technician_name
                    FROM tickets t
                    JOIN users u ON t.technician_id = u.id
                    WHERE t.client_id = ?
                    ORDER BY t.created_at DESC
                ", [$client['id']]);
                ?>
                
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Tickets del Cliente</h5>
                        <a href="<?php echo BASE_URL; ?>/admin/tickets.php?action=create&client_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Nuevo Ticket
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($clientTickets) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Descripción</th>
                                            <th>Técnico</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientTickets as $ticket): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>/admin/tickets.php?action=view&id=<?php echo $ticket['id']; ?>" class="text-info fw-bold">
                                                        <?php echo $ticket['id']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo escape(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo escape($ticket['technician_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    
                                                    switch ($ticket['status']) {
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            $statusText = 'Pendiente';
                                                            break;
                                                        case 'in_progress':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'En Progreso';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'bg-success';
                                                            $statusText = 'Completado';
                                                            break;
                                                        case 'not_completed':
                                                            $statusClass = 'bg-danger';
                                                            $statusText = 'No Completado';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No hay tickets registrados para este cliente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Map -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Ubicación</h5>
                    </div>
                    <div class="card-body">
                        <div id="map" class="map-container"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript for map display -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const clientLat = <?php echo $client['latitude']; ?>;
                const clientLng = <?php echo $client['longitude']; ?>;
                
                const map = L.map('map').setView([clientLat, clientLng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Add marker at client location
                L.marker([clientLat, clientLng])
                    .addTo(map)
                    .bindPopup("<?php echo escape($client['name']); ?><br><?php echo escape($client['address']); ?>");
                
                // Make map refresh when it becomes visible
                map.invalidateSize();
            });
        </script>
    <?php endif; ?>
</div>

<!-- Formulario oculto para eliminar clientes -->
<form id="deleteForm" method="post" action="<?php echo BASE_URL; ?>/admin/clients.php" style="display: none;">
    <input type="hidden" name="client_id" id="deleteClientId">
    <input type="hidden" name="delete_client" value="1">
</form>

<!-- Modal de confirmación para eliminar cliente -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar el cliente <strong id="deleteClientName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para confirmar la eliminación de un cliente
    function confirmDelete(clientId, clientName) {
        // Actualizar el modal con los datos del cliente
        document.getElementById('deleteClientName').textContent = clientName;
        document.getElementById('deleteClientId').value = clientId;
        
        // Mostrar el modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
        
        // Configurar el botón de confirmación
        document.getElementById('confirmDeleteBtn').onclick = function() {
            document.getElementById('deleteForm').submit();
        };
    }
    
    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php include_once '../templates/footer.php'; ?>
