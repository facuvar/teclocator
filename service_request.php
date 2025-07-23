<?php
/**
 * Formulario público para solicitudes de servicio
 */
require_once 'includes/init.php';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database connection
    $db = Database::getInstance();
    
    // Validar y sanitizar datos
    $requestType = $_POST['request_type'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validación básica
    $errors = [];
    
    if (empty($requestType)) {
        $errors[] = "Por favor seleccione el tipo de solicitud";
    }
    
    if (empty($name)) {
        $errors[] = "Por favor ingrese su nombre";
    }
    
    if (empty($phone)) {
        $errors[] = "Por favor ingrese su número de teléfono";
    }
    
    if (empty($address)) {
        $errors[] = "Por favor ingrese su dirección";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errors)) {
        $data = [
            'request_type' => $requestType,
            'name' => $name,
            'phone' => $phone,
            'address' => $address
        ];
        
        try {
            $db->insert('service_requests', $data);
            $success = true;
        } catch (Exception $e) {
            $errors[] = "Error al guardar la solicitud: " . $e->getMessage();
            $success = false;
        }
    } else {
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Servicio Técnico - TecLocator</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/img/favicon.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        :root {
            --dark-bg: #121212;
            --dark-surface: #1e1e1e;
            --dark-surface-2: #2d2d2d;
            --dark-text: #ffffff;
            --dark-text-secondary: #cccccc;
            --dark-border: #333333;
            --dark-primary: #4f5d75;
            --dark-secondary: #2d3142;
            --dark-danger: #f44336;
            --dark-warning: #ff9800;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }
        
        .service-request-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: var(--dark-surface);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--dark-border);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-height: 80px;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 20px;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .info-text {
            text-align: center;
            margin-bottom: 25px;
            color: var(--dark-text-secondary);
        }
        
        .form-control, .form-select {
            background-color: var(--dark-surface-2);
            color: var(--dark-text);
            border-color: var(--dark-border);
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--dark-surface-2);
            color: var(--dark-text);
            border-color: var(--dark-primary);
            box-shadow: 0 0 0 0.25rem rgba(79, 93, 117, 0.25);
        }
        
        .form-label {
            color: var(--dark-text);
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: var(--dark-primary);
            border-color: var(--dark-primary);
        }
        
        .btn-primary:hover {
            background-color: #3d4a5f;
            border-color: #3d4a5f;
        }
        
        .emergency-info {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid var(--dark-danger);
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            color: var(--dark-text);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="service-request-container">
            <div class="logo-container">
                <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TecLocator Logo" class="img-fluid">
            </div>
            
            <h2 class="form-title">Solicitud de visita técnica</h2>
            <p class="info-text">Nuestro horario de atención es de Lunes a Viernes de 07 a 18 HS</p>
            
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> Su solicitud ha sido enviada correctamente. Un representante se pondrá en contacto con usted a la brevedad.
                </div>
            <?php elseif (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="request_type" class="form-label">Tipo de solicitud</label>
                    <select class="form-select" id="request_type" name="request_type" required>
                        <option value="" selected disabled>Seleccione una opción</option>
                        <option value="encerrada">Persona encerrada</option>
                        <option value="fuera_servicio">Ascensor fuera de servicio</option>
                        <option value="fallas">Ascensor con fallas</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="address" name="address" required placeholder="Calle, número, piso, departamento">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Enviar Solicitud</button>
                </div>
            </form>
            
            <div class="emergency-info mt-4">
                <p class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Para emergencias comuniquese a traves de estos numeros:</strong></p>
                <p class="mb-0 mt-2"><strong>4599-1106 CABA / 4599-1107 GBA</strong></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
