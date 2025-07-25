<?php
/**
 * Script para actualizar las zonas de los técnicos existentes a "Zona 1"
 */
require_once 'includes/init.php';

// No requerimos autenticación para facilitar la ejecución

// Get database connection
$db = Database::getInstance();

try {
    // Primero verificar si la columna 'zone' existe
    $columns = $db->query("SHOW COLUMNS FROM users LIKE 'zone'")->fetchAll();
    $zoneExists = count($columns) > 0;
    
    if (!$zoneExists) {
        // La columna no existe, agregarla
        $db->query("ALTER TABLE users ADD COLUMN zone VARCHAR(50) DEFAULT NULL");
        echo "<p>Se ha agregado la columna 'zone' a la tabla 'users'.</p>";
    }
    
    // Mostrar las zonas actuales antes de la actualización
    $beforeUpdate = $db->query("SELECT id, name, zone FROM users WHERE role = 'technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar todos los técnicos a Zona 1 - Usar consulta directa para asegurar la actualización
    $updateResult = $db->query("UPDATE users SET zone = 'Zona 1' WHERE role = 'technician'");
    
    // Verificar si la actualización fue exitosa
    $rowCount = $updateResult->rowCount();
    
    // Obtener los técnicos actualizados
    $afterUpdate = $db->query("SELECT id, name, zone FROM users WHERE role = 'technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Mostrar resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Actualización de Zonas</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h2>Actualización de Zonas Completada</h2>
                </div>
                <div class='card-body'>
                    <div class='alert alert-success'>
                        Se han actualizado <strong>{$rowCount}</strong> técnicos a <strong>Zona 1</strong>.
                    </div>
                    
                    <h4>Técnicos Actualizados:</h4>
                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Zona</th>
                                </tr>
                            </thead>
                            <tbody>";
    
    foreach ($afterUpdate as $tech) {
        echo "<tr>
                <td>{$tech['id']}</td>
                <td>{$tech['name']}</td>
                <td>{$tech['zone']}</td>
              </tr>";
    }
    
    echo "      </tbody>
                        </table>
                    </div>
                    
                    <h4 class='mt-4'>Nuevas Zonas Disponibles:</h4>
                    <ul class='list-group mb-4'>
                        <li class='list-group-item'>Zona 1</li>
                        <li class='list-group-item'>Zona 2</li>
                        <li class='list-group-item'>Zona 3</li>
                        <li class='list-group-item'>Zona 4</li>
                    </ul>
                    
                    <div class='text-center'>
                        <a href='admin/technicians.php' class='btn btn-primary'>Volver a Técnicos</a>
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
        <title>Error en Actualización</title>
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
                        Ocurrió un error al actualizar las zonas: " . $e->getMessage() . "
                    </div>
                    <div class='text-center'>
                        <a href='admin/technicians.php' class='btn btn-primary'>Volver a Técnicos</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
