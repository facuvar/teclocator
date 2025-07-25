<?php
/**
 * Script para crear la base de datos teclocate_deploy
 */

// Configuración de conexión a MySQL
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Conectar a MySQL sin especificar una base de datos
    $pdo = new PDO(
        "mysql:host={$host}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Crear la base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS teclocate_deploy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "<div style='padding: 10px; margin: 5px; background-color: #d4edda;'>Base de datos 'teclocate_deploy' creada exitosamente</div>";
    
    // Redirigir al script de duplicación después de 2 segundos
    echo "<div style='padding: 10px; margin: 5px; background-color: #d1ecf1;'>Redirigiendo al script de duplicación en 2 segundos...</div>";
    echo "<script>setTimeout(function() { window.location.href = 'duplicate_database.php'; }, 2000);</script>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 10px; margin: 5px; background-color: #f8d7da;'>Error al crear la base de datos: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .header {
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Crear Base de Datos</h1>
            <p>Este script crea la base de datos <strong>teclocate_deploy</strong> para la versión de despliegue.</p>
        </div>
        
        <div class="footer">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
