<?php
require_once '../includes/init.php';

// Cargar el autoloader de Composer explícitamente
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

// Crear instancia de Auth y verificar si es administrador
$auth = new Auth();
$auth->requireAdmin();

// Título de la página
$pageTitle = "Importar Clientes";

// Incluir el encabezado
include '../templates/header.php';

// Mensaje de estado
$message = '';
$messageType = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    // Verificar si se ha instalado PHPSpreadsheet
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $message = 'Error: La biblioteca PHPSpreadsheet no está instalada correctamente. Ejecute "composer require phpoffice/phpspreadsheet" en el directorio raíz.';
        $messageType = 'danger';
    } else {
        $file = $_FILES['excel_file'];
        
        // Verificar si hay errores en la carga del archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Error al cargar el archivo. Código: ' . $file['error'];
            $messageType = 'danger';
        } else {
            // Verificar el tipo de archivo
            $allowedTypes = [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/octet-stream'
            ];
            
            if (!in_array($file['type'], $allowedTypes) && 
                !(pathinfo($file['name'], PATHINFO_EXTENSION) === 'xls' || 
                  pathinfo($file['name'], PATHINFO_EXTENSION) === 'xlsx')) {
                $message = 'Tipo de archivo no válido. Por favor, suba un archivo Excel (.xls o .xlsx)';
                $messageType = 'danger';
            } else {
                try {
                    // Cargar el archivo Excel
                    $inputFileName = $file['tmp_name'];
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
                    $worksheet = $spreadsheet->getActiveSheet();
                    
                    // Obtener los datos como un array
                    $data = $worksheet->toArray();
                    
                    // Verificar que hay datos
                    if (count($data) <= 1) {
                        $message = 'El archivo no contiene datos suficientes.';
                        $messageType = 'warning';
                    } else {
                        // Eliminar la fila de encabezados
                        $headers = array_shift($data);
                        
                        // Inicializar contadores
                        $totalRows = count($data);
                        $importedCount = 0;
                        $errorCount = 0;
                        $errors = [];
                        
                        // Procesar cada fila
                        foreach ($data as $rowIndex => $row) {
                            // Verificar que la fila tenga suficientes columnas
                            if (count($row) < 11) {
                                $errors[] = "Fila " . ($rowIndex + 2) . ": No tiene suficientes columnas.";
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
                                $errors[] = "Fila " . ($rowIndex + 2) . ": Faltan datos obligatorios (Razón Social o dirección).";
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
                                $errors[] = "Fila " . ($rowIndex + 2) . ": Error al insertar en la base de datos: " . $e->getMessage();
                                $errorCount++;
                            }
                        }
                        
                        // Preparar mensaje de resultado
                        $message = "Importación completada. Total de filas: $totalRows, Importadas: $importedCount, Errores: $errorCount";
                        
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
                    }
                } catch (Exception $e) {
                    $message = 'Error al procesar el archivo: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}
?>

<!-- Contenido principal -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Importar Clientes desde Excel</h1>
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
                <p>Para importar clientes desde un archivo Excel, siga estos pasos:</p>
                <ol>
                    <li>Prepare un archivo Excel (.xls o .xlsx) con los siguientes encabezados:
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
                            <li>Grupo/Vendedor</li>
                            <li>Teléfono</li>
                        </ul>
                    </li>
                    <li>Asegúrese de que la primera fila contenga estos encabezados exactamente como se muestran.</li>
                    <li>Complete los datos de los clientes en las filas siguientes.</li>
                    <li>Guarde el archivo y súbalo utilizando el formulario de abajo.</li>
                </ol>
                <a href="<?php echo BASE_URL; ?>/admin/generate_template.php" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Plantilla
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Cargar Archivo</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Archivo Excel</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx" required>
                        <div class="form-text">Seleccione un archivo Excel (.xls o .xlsx)</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Importar Clientes</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Plantilla de Ejemplo</h5>
            </div>
            <div class="card-body">
                <p>Descargue una plantilla de ejemplo para importar clientes:</p>
                <a href="../templates/generate_client_template.php" class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Descargar Plantilla
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
