<?php
/**
 * Login page for both administrators and technicians
 */
require_once 'includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese su correo electrónico y contraseña.';
    } else {
        // Attempt login
        if ($auth->login($email, $password, $role)) {
            // Redirect based on role
            if ($role === 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('technician/dashboard.php');
            }
        } else {
            $error = 'Credenciales inválidas. Por favor, intente nuevamente.';
        }
    }
}

// Set active role tab
$activeRole = $_GET['role'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Gestión de Visitas para Ascensores</title>
    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dark-mode">
    <div class="container">
        <div class="auth-container">
            <div class="auth-logo">
                <img src="assets/img/logo.png" alt="TecLocator Logo" class="img-fluid" style="max-height: 80px;">
                <p>Sistema de Gestión de Visitas para Ascensores</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <!-- Role tabs -->
                    <ul class="nav nav-tabs auth-tabs" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeRole === 'admin' ? 'active' : ''; ?>" 
                                    id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-login" 
                                    type="button" role="tab" aria-controls="admin-login" aria-selected="<?php echo $activeRole === 'admin' ? 'true' : 'false'; ?>">
                                Administrador
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeRole === 'technician' ? 'active' : ''; ?>" 
                                    id="technician-tab" data-bs-toggle="tab" data-bs-target="#technician-login" 
                                    type="button" role="tab" aria-controls="technician-login" aria-selected="<?php echo $activeRole === 'technician' ? 'true' : 'false'; ?>">
                                Técnico
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content" id="authTabsContent">
                        <!-- Admin login form -->
                        <div class="tab-pane fade <?php echo $activeRole === 'admin' ? 'show active' : ''; ?>" 
                             id="admin-login" role="tabpanel" aria-labelledby="admin-tab">
                            <form method="post" action="login.php" class="mt-4">
                                <input type="hidden" name="role" value="admin">
                                
                                <div class="mb-3">
                                    <label for="admin-email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="admin-email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin-password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="admin-password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Technician login form -->
                        <div class="tab-pane fade <?php echo $activeRole === 'technician' ? 'show active' : ''; ?>" 
                             id="technician-login" role="tabpanel" aria-labelledby="technician-tab">
                            <form method="post" action="login.php" class="mt-4">
                                <input type="hidden" name="role" value="technician">
                                
                                <div class="mb-3">
                                    <label for="technician-email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="technician-email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="technician-password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="technician-password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
