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
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
    </li>
    
    <?php if ($auth->isAdmin()): ?>
    <!-- Admin Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('clients.php'); ?>" href="<?php echo BASE_URL; ?>admin/clients.php">
            <i class="bi bi-building"></i> <span>Clientes</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('technicians.php'); ?>" href="<?php echo BASE_URL; ?>admin/technicians.php">
            <i class="bi bi-person-gear"></i> <span>Técnicos</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('tickets.php'); ?>" href="<?php echo BASE_URL; ?>admin/tickets.php">
            <i class="bi bi-ticket-perforated"></i> <span>Tickets</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('visits.php'); ?>" href="<?php echo BASE_URL; ?>admin/visits.php">
            <i class="bi bi-clipboard-check"></i> <span>Visitas</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('import_clients_simple.php'); ?>" href="<?php echo BASE_URL; ?>admin/import_clients_simple.php">
            <i class="bi bi-file-earmark-csv"></i> <span>Importar Clientes (CSV)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('service_requests.php'); ?>" href="<?php echo BASE_URL; ?>admin/service_requests.php">
            <i class="bi bi-headset"></i> <span>Solicitudes de Servicio</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('administrators.php'); ?>" href="<?php echo BASE_URL; ?>admin/administrators.php">
            <i class="bi bi-person-lock"></i> <span>Administradores</span>
        </a>
    </li>
    <?php else: ?>
    <!-- Technician Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('my-tickets.php'); ?>" href="<?php echo BASE_URL; ?>technician/my-tickets.php">
            <i class="bi bi-ticket-perforated"></i> <span>Mis Tickets</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('completed-visits.php'); ?>" href="<?php echo BASE_URL; ?>technician/completed-visits.php">
            <i class="bi bi-clipboard-check"></i> <span>Mis Visitas</span>
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('profile.php'); ?>" href="<?php echo BASE_URL; ?>profile.php">
            <i class="bi bi-person"></i> <span>Mi Perfil</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
            <i class="bi bi-box-arrow-right"></i> <span>Cerrar Sesión</span>
        </a>
    </li>
</ul>
