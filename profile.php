<?php
/**
 * User profile page
 */
require_once 'includes/init.php';

// Require authentication
$auth->requireLogin();

// Get database connection
$db = Database::getInstance();

// Get current user data
$userId = $_SESSION['user_id'];
$user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $userData = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];
        
        // Validate required fields
        if (empty($userData['name']) || empty($userData['email'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Check if email already exists (for email changes)
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ? AND id != ?", 
                [$userData['email'], $userId]
            );
            
            if ($existingUser) {
                flash('El correo electrónico ya está en uso.', 'danger');
            } else {
                // Update user data
                $db->update('users', $userData, 'id = ?', [$userId]);
                flash('Perfil actualizado correctamente.', 'success');
                
                // Update session data
                $_SESSION['user_name'] = $userData['name'];
                
                // Reload user data
                $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('Por favor, complete todos los campos de contraseña.', 'danger');
        } elseif ($newPassword !== $confirmPassword) {
            flash('Las contraseñas nuevas no coinciden.', 'danger');
        } elseif (!password_verify($currentPassword, $user['password'])) {
            flash('La contraseña actual es incorrecta.', 'danger');
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            flash('Contraseña actualizada correctamente.', 'success');
        }
    }
}

// Set page title
$pageTitle = 'Mi Perfil';

// Include header
include_once 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h1><?php echo $pageTitle; ?></h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Información Personal</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo BASE_URL; ?>profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo escape($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        
                        <?php if ($user['role'] === 'technician'): ?>
                        <div class="mb-3">
                            <label for="zone" class="form-label">Zona Asignada</label>
                            <input type="text" class="form-control" id="zone" value="<?php echo escape($user['zone'] ?? ''); ?>" readonly>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_profile" class="btn btn-primary">Actualizar Perfil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Cambiar Contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo BASE_URL; ?>profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña Actual *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="change_password" class="btn btn-warning">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
