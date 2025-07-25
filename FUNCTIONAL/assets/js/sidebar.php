<div class="sidebar-header">
    <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="TecLocator Logo" class="img-fluid" style="max-height: 50px;">
</div>
<div class="sidebar-user">
    <div class="user-info">
        <i class="bi bi-person-circle"></i>
        <span><?php echo escape($_SESSION['user_name']); ?></span>
        <small><?php echo $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Técnico'; ?></small>
    </div>
</div>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
    
    <?php if ($auth->isAdmin()): ?>
    <!-- Admin Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('clients.php'); ?>" href="<?php echo BASE_URL; ?>admin/clients.php">
            <i class="bi bi-building"></i> Clientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('technicians.php'); ?>" href="<?php echo BASE_URL; ?>admin/technicians.php">
            <i class="bi bi-person-gear"></i> Técnicos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('tickets.php'); ?>" href="<?php echo BASE_URL; ?>admin/tickets.php">
            <i class="bi bi-ticket-perforated"></i> Tickets
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('visits.php'); ?>" href="<?php echo BASE_URL; ?>admin/visits.php">
            <i class="bi bi-clipboard-check"></i> Visitas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('import_clients_simple.php'); ?>" href="<?php echo BASE_URL; ?>admin/import_clients_simple.php">
            <i class="bi bi-file-earmark-csv"></i> Importar Clientes (CSV)
        </a>
    </li>
    <?php else: ?>
    <!-- Technician Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('my-tickets.php'); ?>" href="<?php echo BASE_URL; ?>technician/my-tickets.php">
            <i class="bi bi-ticket-perforated"></i> Mis Tickets
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('completed-visits.php'); ?>" href="<?php echo BASE_URL; ?>technician/completed-visits.php">
            <i class="bi bi-clipboard-check"></i> Mis Visitas
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('profile.php'); ?>" href="<?php echo BASE_URL; ?>profile.php">
            <i class="bi bi-person"></i> Mi Perfil
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </a>
    </li>
</ul>
