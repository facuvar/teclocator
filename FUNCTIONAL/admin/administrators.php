<?php
/**
 * Administrators management page for administrators
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$adminId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create or update administrator
    if (isset($_POST['save_admin'])) {
        $adminData = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => 'admin'
        ];
        
        // Add password for new administrators or if password is being changed
        if ((!isset($_POST['admin_id']) || empty($_POST['admin_id'])) || 
            (isset($_POST['password']) && !empty($_POST['password']))) {
            $adminData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        // Validate required fields
        if (empty($adminData['name']) || empty($adminData['email'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Check if email already exists (for new administrators or email changes)
            $existingUser = null;
            if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
                $existingUser = $db->selectOne(
                    "SELECT * FROM users WHERE email = ? AND id != ?", 
                    [$adminData['email'], $_POST['admin_id']]
                );
            } else {
                $existingUser = $db->selectOne(
                    "SELECT * FROM users WHERE email = ?", 
                    [$adminData['email']]
                );
            }
            
            if ($existingUser) {
                flash('El correo electrónico ya está en uso.', 'danger');
            } else {
                // Create or update
                if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
                    // Remove password from data if not provided
                    if (!isset($adminData['password'])) {
                        unset($adminData['password']);
                    }
                    
                    // Update existing administrator
                    $db->update('users', $adminData, 'id = ?', [$_POST['admin_id']]);
                    flash('Administrador actualizado correctamente.', 'success');
                } else {
                    // Create new administrator
                    $adminId = $db->insert('users', $adminData);
                    flash('Administrador creado correctamente.', 'success');
                }
                
                // Redirect to list view
                redirect('admin/administrators.php');
            }
        }
    }
    
    // Delete administrator
    if (isset($_POST['delete_admin'])) {
        $adminId = $_POST['admin_id'] ?? null;
        
        if ($adminId) {
            // No permitir eliminar el propio usuario
            if ($adminId == $_SESSION['user_id']) {
                flash('No puedes eliminar tu propio usuario.', 'danger');
                redirect('admin/administrators.php');
                exit;
            }
            
            // Verificar que no sea el último administrador
            $adminCount = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
            if ($adminCount <= 1) {
                flash('No puedes eliminar el único administrador del sistema.', 'danger');
                redirect('admin/administrators.php');
                exit;
            }
            
            // Delete administrator
            $db->delete('users', 'id = ?', [$adminId]);
            flash('Administrador eliminado correctamente.', 'success');
        }
        
        // Redirect to list view
        redirect('admin/administrators.php');
    }
}

// Get administrator data for edit
$admin = null;
if (($action === 'edit' || $action === 'view') && $adminId) {
    $admin = $db->selectOne("SELECT * FROM users WHERE id = ? AND role = 'admin'", [$adminId]);
    if (!$admin) {
        flash('Administrador no encontrado.', 'danger');
        redirect('admin/administrators.php');
    }
}

// Get all administrators for list view
$administrators = [];
if ($action === 'list') {
    $administrators = $db->select("SELECT * FROM users WHERE role = 'admin' ORDER BY name");
}

// Page title
$pageTitle = 'Gestión de Administradores';
switch($action) {
    case 'create':
        $pageTitle = 'Crear Administrador';
        break;
    case 'edit':
        $pageTitle = 'Editar Administrador';
        break;
    case 'view':
        $pageTitle = 'Ver Administrador';
        break;
}

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>admin/administrators.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Administrador
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/administrators.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a la Lista
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Administrators List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($administrators) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Fecha de Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($administrators as $admin): ?>
                                    <tr>
                                        <td><?php echo $admin['id']; ?></td>
                                        <td><?php echo escape($admin['name']); ?></td>
                                        <td><?php echo escape($admin['email']); ?></td>
                                        <td><?php echo !empty($admin['phone']) ? escape($admin['phone']) : '-'; ?></td>
                                        <td><?php echo formatDateTime($admin['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo BASE_URL; ?>admin/administrators.php?action=view&id=<?php echo $admin['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>admin/administrators.php?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $admin['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Delete Modal -->
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                                <div class="modal fade" id="deleteModal<?php echo $admin['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $admin['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $admin['id']; ?>">Confirmar Eliminación</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                ¿Está seguro de que desea eliminar al administrador <strong><?php echo escape($admin['name']); ?></strong>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <button type="submit" name="delete_admin" class="btn btn-danger">Eliminar</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay administradores registrados</p>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Create/Edit Administrator Form -->
        <div class="card">
            <div class="card-body">
                <form method="post" action="">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $action === 'edit' ? escape($admin['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' ? escape($admin['email']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $action === 'edit' ? escape($admin['phone']) : ''; ?>">
                            <div class="form-text">Formato recomendado: +549XXXXXXXXXX (sin espacios ni guiones)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label"><?php echo $action === 'create' ? 'Contraseña *' : 'Nueva Contraseña (dejar en blanco para mantener la actual)'; ?></label>
                            <input type="password" class="form-control" id="password" name="password" <?php echo $action === 'create' ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/administrators.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" name="save_admin" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($action === 'view' && $admin): ?>
        <!-- View Administrator Details -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Información del Administrador</h5>
                        <table class="table">
                            <tr>
                                <th>ID:</th>
                                <td><?php echo $admin['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo escape($admin['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo escape($admin['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td><?php echo !empty($admin['phone']) ? escape($admin['phone']) : 'No especificado'; ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Creación:</th>
                                <td><?php echo formatDateTime($admin['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo formatDateTime($admin['updated_at']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?php echo BASE_URL; ?>admin/administrators.php?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                        <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                        
                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        ¿Está seguro de que desea eliminar al administrador <strong><?php echo escape($admin['name']); ?></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <form method="post" action="<?php echo BASE_URL; ?>admin/administrators.php">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" name="delete_admin" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>
