<?php
/**
 * Versión simplificada de importación de clientes que usa CSV en lugar de Excel
 * No requiere Composer ni PhpSpreadsheet
 */
require_once '../includes/init.php';

// Crear instancia de Auth y verificar si es administrador
$auth = new Auth();
$auth->requireAdmin();

// Título de la página
$pageTitle = "Importar Clientes (CSV)";

// Incluir el encabezado
include '../templates/header.php';

// Mensaje de estado
$message = '';
$messageType = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // Verificar si hay errores en la carga del archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Error al cargar el archivo. Código: ' . $file['error'];
        $messageType = 'danger';
    } else {
        // Verificar el tipo de archivo
        $allowedTypes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream'
        ];
        
        if (!in_array($file['type'], $allowedTypes) && 
            pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
            $message = 'Tipo de archivo no válido. Por favor, suba un archivo CSV (.csv)';
            $messageType = 'danger';
        } else {
            try {
                // Abrir el archivo CSV
                $handle = fopen($file['tmp_name'], 'r');
                
                if ($handle === false) {
                    throw new Exception('No se pudo abrir el archivo CSV.');
                }
                
                // Leer la primera línea como encabezados
                $headers = fgetcsv($handle, 0, ',');
                
                if ($headers === false) {
                    throw new Exception('El archivo CSV está vacío o tiene un formato incorrecto.');
                }
                
                // Inicializar contadores
                $rowIndex = 1; // Empezamos desde 1 porque ya leímos los encabezados
                $importedCount = 0;
                $errorCount = 0;
                $errors = [];
                
                // Procesar cada fila
                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $rowIndex++;
                    
                    // Verificar que la fila tenga suficientes columnas
                    if (count($row) < 11) {
                        $errors[] = "Fila $rowIndex: No tiene suficientes columnas.";
                        $errorCount++;
                        continue;
                    }
                    
                    // Extraer datos y asegurarse de que no sean null antes de aplicar trim()
                    $clientNumber = isset($row[0]) && $row[0] !== null ? trim((string)$row[0]) : '';
                    $businessName = isset($row[1]) && $row[1] !== null ? trim((string)$row[1]) : '';
                    $street = isset($row[2]) && $row[2] !== null ? trim((string)$row[2]) : '';
                    $number = isset($row[3]) && $row[3] !== null ? trim((string)$row[3]) : '';
                    $locality = isset($row[4]) && $row[4] !== null ? trim((string)$row[4]) : '';
                    $province = isset($row[5]) && $row[5] !== null ? trim((string)$row[5]) : '';
                    $country = isset($row[6]) && $row[6] !== null ? trim((string)$row[6]) : '';
                    
                    // Manejar coordenadas que pueden ser nulas
                    $latitude = isset($row[7]) && $row[7] !== null ? str_replace(',', '.', trim((string)$row[7])) : null;
                    $longitude = isset($row[8]) && $row[8] !== null ? str_replace(',', '.', trim((string)$row[8])) : null;
                    
                    $group = isset($row[9]) && $row[9] !== null ? trim((string)$row[9]) : '';
                    $phone = isset($row[10]) && $row[10] !== null ? trim((string)$row[10]) : '';
                    
                    // Construir dirección completa
                    $address = $street;
                    if (!empty($number)) $address .= " " . $number;
                    if (!empty($locality)) $address .= ", " . $locality;
                    if (!empty($province)) $address .= ", " . $province;
                    if (!empty($country)) $address .= ", " . $country;
                    
                    // Validar datos esenciales
                    if (empty($businessName) || empty($address)) {
                        $errors[] = "Fila $rowIndex: Faltan datos obligatorios (Razón Social o dirección).";
                        $errorCount++;
                        continue;
                    }
                    
                    // Usar el número de cliente como nombre si está vacío
                    $name = !empty($clientNumber) ? "Cliente " . $clientNumber : $businessName;
                    
                    // Si no hay coordenadas, establecerlas como null para actualizar después
                    if ($latitude === null || $longitude === null || !is_numeric($latitude) || !is_numeric($longitude)) {
                        $latitude = 0;
                        $longitude = 0;
                    }
                    
                    // Insertar en la base de datos
                    try {
                        $db = Database::getInstance();
                        $stmt = $db->query(
                            "INSERT INTO clients (name, business_name, client_number, address, latitude, longitude, phone, group_vendor) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            [$name, $businessName, $clientNumber, $address, $latitude, $longitude, $phone, $group]
                        );
                        $importedCount++;
                    } catch (PDOException $e) {
                        $errors[] = "Fila $rowIndex: Error al insertar en la base de datos: " . $e->getMessage();
                        $errorCount++;
                    }
                }
                
                // Cerrar el archivo
                fclose($handle);
                
                // Preparar mensaje de resultado
                $message = "Importación completada. Total de filas: " . ($rowIndex - 1) . ", Importadas: $importedCount, Errores: $errorCount";
                
                // Agregar nota sobre coordenadas
                if ($importedCount > 0) {
                    $message .= "<br><strong>Nota importante:</strong> Los clientes han sido importados con coordenadas temporales (0,0). ";
                    $message .= "Por favor, actualice las coordenadas desde la página de clientes para que aparezcan correctamente en el mapa.";
                }
                
                $messageType = ($errorCount > 0) ? 'warning' : 'success';
                
                // Agregar detalles de errores si los hay
                if ($errorCount > 0) {
                    $message .= "<br><strong>Detalles de errores:</strong><ul>";
                    foreach ($errors as $error) {
                        $message .= "<li>$error</li>";
                    }
                    $message .= "</ul>";
                }
            } catch (Exception $e) {
                $message = 'Error al procesar el archivo: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}
?>

<!-- Contenido principal -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Importar Clientes desde CSV</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>" role="alert">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Instrucciones</h5>
            </div>
            <div class="card-body">
                <p>Para importar clientes desde un archivo CSV, siga estos pasos:</p>
                <ol>
                    <li>Prepare un archivo CSV con los siguientes encabezados:
                        <ul>
                            <li>Nro. Cliente</li>
                            <li>Razón Social</li>
                            <li>Calle</li>
                            <li>Número</li>
                            <li>Localidad</li>
                            <li>Provincia</li>
                            <li>País</li>
                            <li>Latitud (puede estar vacía)</li>
                            <li>Longitud (puede estar vacía)</li>
                            <li>Grupo</li>
                            <li>Teléfono</li>
                        </ul>
                    </li>
                    <li>Asegúrese de que los datos estén correctamente formateados:
                        <ul>
                            <li>Las coordenadas deben ser números decimales (puede usar punto o coma como separador decimal)</li>
                            <li>Los campos obligatorios son: Razón Social y al menos uno de los campos de dirección</li>
                        </ul>
                    </li>
                    <li>Seleccione el archivo CSV usando el botón "Examinar" y haga clic en "Importar"</li>
                </ol>
                <p><strong>Nota:</strong> Si no proporciona coordenadas, los clientes se importarán con coordenadas temporales (0,0) y deberá actualizarlas manualmente.</p>
                
                <div class="alert alert-info">
                    <h6>¿Cómo crear un archivo CSV?</h6>
                    <p>Puede crear un archivo CSV desde Excel siguiendo estos pasos:</p>
                    <ol>
                        <li>Abra su archivo en Excel</li>
                        <li>Vaya a "Archivo" > "Guardar como"</li>
                        <li>Seleccione "CSV (delimitado por comas) (*.csv)" como tipo de archivo</li>
                        <li>Guarde el archivo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Subir Archivo CSV</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Seleccione un archivo CSV</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv,text/csv">
                    </div>
                    <button type="submit" class="btn btn-primary">Importar</button>
                    <a href="<?php echo BASE_URL; ?>/admin/clients.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Ejemplo de Formato CSV</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3">Nro. Cliente,Razón Social,Calle,Número,Localidad,Provincia,País,Latitud,Longitud,Grupo,Teléfono
123,Empresa Ejemplo,Av. Corrientes,1234,Buenos Aires,CABA,Argentina,-34.603722,-58.381592,Grupo A,1122334455
456,Otra Empresa,Calle Principal,789,Córdoba,Córdoba,Argentina,-31.420083,-64.188776,Grupo B,3512345678</pre>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
