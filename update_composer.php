<?php
/**
 * Script para actualizar las dependencias de Composer
 */

// Mostrar todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $color = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info');
    echo "<div style='padding: 10px; margin: 5px; background-color: " . ($color === 'danger' ? '#f8d7da' : ($color === 'success' ? '#d4edda' : '#d1ecf1')) . ";'>{$message}</div>";
}

// Verificar si Composer está instalado
if (!file_exists(__DIR__ . '/composer.phar')) {
    showMessage("Composer no está instalado. Descargando...", "info");
    
    // Descargar Composer
    $composerInstaller = file_get_contents('https://getcomposer.org/installer');
    if ($composerInstaller === false) {
        showMessage("Error al descargar el instalador de Composer", "error");
        exit;
    }
    
    // Guardar el instalador
    file_put_contents(__DIR__ . '/composer-setup.php', $composerInstaller);
    
    // Ejecutar el instalador
    exec('php ' . __DIR__ . '/composer-setup.php 2>&1', $output, $returnCode);
    
    // Verificar si la instalación fue exitosa
    if ($returnCode !== 0) {
        showMessage("Error al instalar Composer: " . implode("<br>", $output), "error");
        exit;
    }
    
    showMessage("Composer instalado correctamente", "success");
    
    // Eliminar el instalador
    unlink(__DIR__ . '/composer-setup.php');
}

// Ejecutar composer update
showMessage("Actualizando dependencias...", "info");
exec('php ' . __DIR__ . '/composer.phar update --no-dev 2>&1', $output, $returnCode);

// Verificar si la actualización fue exitosa
if ($returnCode !== 0) {
    showMessage("Error al actualizar dependencias: " . implode("<br>", $output), "error");
} else {
    showMessage("Dependencias actualizadas correctamente", "success");
}

// Mostrar la salida de composer update
echo "<div style='padding: 10px; margin: 5px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;'>";
echo "<h4>Salida de Composer:</h4>";
echo "<pre>" . implode("\n", $output) . "</pre>";
echo "</div>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Dependencias de Composer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>Actualizar Dependencias de Composer</h1>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
