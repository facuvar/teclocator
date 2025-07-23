<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Tickets para Ascensores</title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.png" type="image/png">
    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Map Fixes CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/map-fixes.css">
</head>
<body class="dark-mode">
    <div class="container-fluid">
        <div class="row">
            <?php if ($auth->isLoggedIn()): ?>
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar">
                    <?php include TEMPLATE_PATH . '/sidebar.php'; ?>
                </div>
                <!-- Main content -->
                <div class="col-md-9 col-lg-10 ms-auto main-content">
            <?php else: ?>
                <!-- Full width for login/register pages -->
                <div class="col-12 main-content">
            <?php endif; ?>
                
                <!-- Flash messages -->
                <?php $flash = getFlash(); ?>
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mt-3" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
