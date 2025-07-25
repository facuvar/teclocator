<?php
/**
 * Script para agregar la columna 'zone' a la tabla 'users' si no existe
 */
require_once 'includes/init.php';

// Get database connection
$db = Database::getInstance();

try {
    // Verificar si la columna 'zone' existe en la tabla 'users'
    $columns = $db->query("SHOW COLUMNS FROM users LIKE 'zone'")->fetchAll();
    $zoneExists = count($columns) > 0;
    
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verificación de Columna Zone</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow'>";
    
    if (!$zoneExists) {
        // La columna no existe, agregarla
        echo "<div class='card-header bg-primary text-white'>
                <h2>Agregando columna 'zone'</h2>
              </div>
              <div class='card-body'>";
        
        $db->query("ALTER TABLE users ADD COLUMN zone VARCHAR(50) DEFAULT NULL");
        
        echo "<div class='alert alert-success'>
                La columna 'zone' ha sido agregada a la tabla 'users'.
              </div>";
    } else {
        // La columna ya existe
        echo "<div class='card-header bg-info text-white'>
                <h2>Verificación de columna</h2>
              </div>
              <div class='card-body'>
                <div class='alert alert-info'>
                  La columna 'zone' ya existe en la tabla 'users'.
                </div>";
    }
    
    // Mostrar la estructura de la tabla users
    echo "<h4 class='mt-4'>Estructura de la tabla 'users':</h4>
          <div class='table-responsive'>
            <table class='table table-striped'>
              <thead>
                <tr>
                  <th>Campo</th>
                  <th>Tipo</th>
                  <th>Nulo</th>
                  <th>Clave</th>
                  <th>Predeterminado</th>
                </tr>
              </thead>
              <tbody>";
    
    $structure = $db->query("DESCRIBE users")->fetchAll();
    foreach ($structure as $column) {
        echo "<tr>
                <td>{$column['Field']}</td>
                <td>{$column['Type']}</td>
                <td>{$column['Null']}</td>
                <td>{$column['Key']}</td>
                <td>{$column['Default']}</td>
              </tr>";
    }
    
    echo "    </tbody>
            </table>
          </div>";
    
    // Botones para continuar
    echo "<div class='mt-4'>
            <a href='update_zones.php' class='btn btn-primary'>Actualizar zonas de técnicos</a>
            <a href='admin/technicians.php' class='btn btn-secondary ms-2'>Ir a página de técnicos</a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow border-danger'>
                <div class='card-header bg-danger text-white'>
                    <h2>Error</h2>
                </div>
                <div class='card-body'>
                    <div class='alert alert-danger'>
                        Ocurrió un error: " . $e->getMessage() . "
                    </div>
                    <a href='admin/technicians.php' class='btn btn-primary'>Volver a técnicos</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
