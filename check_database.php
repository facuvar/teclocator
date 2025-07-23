<?php
/**
 * Script para verificar la estructura de la base de datos
 */
require_once 'includes/init.php';

// Obtener la conexión a la base de datos
$db = Database::getInstance();
$conn = $db->getConnection();

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $color = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info');
    echo "<div style='padding: 10px; margin: 5px; background-color: " . ($color === 'danger' ? '#f8d7da' : ($color === 'success' ? '#d4edda' : '#d1ecf1')) . ";'>{$message}</div>";
}

// Verificar tablas existentes
$tables = ['users', 'clients', 'tickets', 'visits'];
$results = [];

foreach ($tables as $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->rowCount() > 0;
        $results[$table] = $exists;
        
        if ($exists) {
            // Verificar estructura de la tabla
            $columns = [];
            $stmt = $conn->query("DESCRIBE {$table}");
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }
            $results["{$table}_columns"] = $columns;
        }
    } catch (PDOException $e) {
        showMessage("Error al verificar la tabla {$table}: " . $e->getMessage(), 'error');
    }
}

// Mostrar resultados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>Verificación de Base de Datos</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Configuración de la Base de Datos</h5>
            </div>
            <div class="card-body">
                <?php
                $config = require_once 'config/database.php';
                echo "<p><strong>Host:</strong> {$config['host']}</p>";
                echo "<p><strong>Base de datos:</strong> {$config['dbname']}</p>";
                echo "<p><strong>Usuario:</strong> {$config['username']}</p>";
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Tablas de la Base de Datos</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Existe</th>
                            <th>Columnas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><?php echo $table; ?></td>
                                <td>
                                    <?php if ($results[$table]): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($results[$table]): ?>
                                        <?php echo implode(', ', $results["{$table}_columns"]); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
