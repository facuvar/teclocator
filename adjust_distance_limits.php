<?php
/**
 * Script para ajustar los límites de distancia en la versión de producción
 * Cambia el límite de 2km a 200m en los archivos relevantes
 */

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $color = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info');
    echo "<div style='padding: 10px; margin: 5px; background-color: " . ($color === 'danger' ? '#f8d7da' : ($color === 'success' ? '#d4edda' : '#d1ecf1')) . ";'>{$message}</div>";
}

// Archivos a modificar
$files = [
    [
        'path' => __DIR__ . '/technician/scan_qr.js',
        'search' => 'const distanceLimit = 100; // 100 metros en producción',
        'replace' => 'const distanceLimit = 200; // 200 metros en producción'
    ],
    [
        'path' => __DIR__ . '/api/start_visit.php',
        'search' => '$maxDistance = 100; // 100 metros en producción',
        'replace' => '$maxDistance = 200; // 200 metros en producción'
    ],
    [
        'path' => __DIR__ . '/api/end_visit.php',
        'search' => '$maxDistance = 100; // 100 metros en producción',
        'replace' => '$maxDistance = 200; // 200 metros en producción'
    ]
];

// Procesar cada archivo
foreach ($files as $file) {
    if (file_exists($file['path'])) {
        try {
            // Leer el contenido del archivo
            $content = file_get_contents($file['path']);
            
            // Verificar si el patrón existe
            if (strpos($content, $file['search']) !== false) {
                // Reemplazar el patrón
                $content = str_replace($file['search'], $file['replace'], $content);
                
                // Guardar el archivo modificado
                file_put_contents($file['path'], $content);
                showMessage("Archivo modificado exitosamente: " . basename($file['path']), "success");
            } else {
                showMessage("No se encontró el patrón en el archivo: " . basename($file['path']), "error");
            }
        } catch (Exception $e) {
            showMessage("Error al procesar el archivo " . basename($file['path']) . ": " . $e->getMessage(), "error");
        }
    } else {
        showMessage("El archivo no existe: " . $file['path'], "error");
    }
}

// Mensaje final
showMessage("Proceso de ajuste de límites de distancia completado", "success");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuste de Límites de Distancia</title>
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
            <h1>Ajuste de Límites de Distancia</h1>
            <p>Este script ajusta los límites de distancia de 100m a 200m para el entorno de producción.</p>
        </div>
        
        <div class="footer">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
