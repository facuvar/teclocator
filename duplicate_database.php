<?php
/**
 * Script para duplicar la base de datos
 * Este script copia todos los datos de elevator_repair_system a teclocate_deploy
 */

// Configuración de la base de datos origen
$sourceConfig = [
    'host' => 'localhost',
    'dbname' => 'elevator_repair_system',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Configuración de la base de datos destino
$targetConfig = [
    'host' => 'localhost',
    'dbname' => 'teclocate_deploy',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $color = $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info');
    echo "<div style='padding: 10px; margin: 5px; background-color: " . ($color === 'danger' ? '#f8d7da' : ($color === 'success' ? '#d4edda' : '#d1ecf1')) . ";'>{$message}</div>";
}

// Conectar a la base de datos origen
try {
    $sourcePdo = new PDO(
        "mysql:host={$sourceConfig['host']};dbname={$sourceConfig['dbname']};charset={$sourceConfig['charset']}",
        $sourceConfig['username'],
        $sourceConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    showMessage("Conexión exitosa a la base de datos origen", "success");
} catch (PDOException $e) {
    showMessage("Error al conectar a la base de datos origen: " . $e->getMessage(), "error");
    exit;
}

// Conectar a la base de datos destino
try {
    $targetPdo = new PDO(
        "mysql:host={$targetConfig['host']};dbname={$targetConfig['dbname']};charset={$targetConfig['charset']}",
        $targetConfig['username'],
        $targetConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    showMessage("Conexión exitosa a la base de datos destino", "success");
} catch (PDOException $e) {
    showMessage("Error al conectar a la base de datos destino: " . $e->getMessage(), "error");
    exit;
}

// Obtener todas las tablas de la base de datos origen
try {
    $stmt = $sourcePdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    showMessage("Se encontraron " . count($tables) . " tablas en la base de datos origen", "info");
} catch (PDOException $e) {
    showMessage("Error al obtener las tablas: " . $e->getMessage(), "error");
    exit;
}

// Deshabilitar verificación de claves foráneas en la base de datos destino
try {
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    showMessage("Verificación de claves foráneas deshabilitada temporalmente", "info");
} catch (PDOException $e) {
    showMessage("Error al deshabilitar verificación de claves foráneas: " . $e->getMessage(), "error");
}

// Definir el orden correcto de las tablas (primero las tablas sin dependencias)
$orderedTables = ['users', 'clients', 'tickets', 'visits'];

// Crear las tablas en la base de datos destino en el orden correcto
foreach ($orderedTables as $table) {
    if (!in_array($table, $tables)) {
        showMessage("La tabla `{$table}` no existe en la base de datos origen", "error");
        continue;
    }
    
    try {
        // Obtener la estructura de la tabla
        $stmt = $sourcePdo->query("SHOW CREATE TABLE `{$table}`");
        $createTableSql = $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'];
        
        // Crear la tabla en la base de datos destino
        $targetPdo->exec("DROP TABLE IF EXISTS `{$table}`");
        $targetPdo->exec($createTableSql);
        showMessage("Tabla `{$table}` creada exitosamente", "success");
    } catch (PDOException $e) {
        showMessage("Error al crear la tabla `{$table}`: " . $e->getMessage(), "error");
        continue;
    }
    
    // Copiar los datos
    try {
        // Verificar si la tabla tiene datos
        $stmt = $sourcePdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = $stmt->fetchColumn();
        
        if ($rowCount > 0) {
            // Obtener los datos de la tabla origen
            $stmt = $sourcePdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Insertar los datos en la tabla destino
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $placeholders = array_fill(0, count($columns), '?');
                
                $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $targetPdo->prepare($sql);
                $stmt->execute(array_values($row));
            }
            
            showMessage("Se copiaron {$rowCount} filas a la tabla `{$table}`", "success");
        } else {
            showMessage("La tabla `{$table}` está vacía, no hay datos para copiar", "info");
        }
    } catch (PDOException $e) {
        showMessage("Error al copiar datos a la tabla `{$table}`: " . $e->getMessage(), "error");
    }
}

// Habilitar verificación de claves foráneas en la base de datos destino
try {
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    showMessage("Verificación de claves foráneas habilitada nuevamente", "success");
} catch (PDOException $e) {
    showMessage("Error al habilitar verificación de claves foráneas: " . $e->getMessage(), "error");
}

showMessage("Proceso de duplicación completado", "success");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicación de Base de Datos</title>
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
            <h1>Duplicación de Base de Datos</h1>
            <p>Este script duplica la base de datos de <strong>elevator_repair_system</strong> a <strong>teclocate_deploy</strong>.</p>
        </div>
        
        <div class="footer">
            <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
            <a href="adjust_distance_limits.php" class="btn btn-success">Ajustar Límites de Distancia</a>
        </div>
    </div>
</body>
</html>
