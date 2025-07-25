<?php
/**
 * Script para corregir las zonas de los técnicos usando SQL directo
 */
require_once 'includes/init.php';

// Obtener la conexión directa a la base de datos
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Verificar si la columna 'zone' existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'zone'");
    $zoneExists = $checkColumn->rowCount() > 0;
    
    if (!$zoneExists) {
        // La columna no existe, agregarla
        $pdo->exec("ALTER TABLE users ADD COLUMN zone VARCHAR(50) DEFAULT NULL");
        echo "<p>Se ha agregado la columna 'zone' a la tabla 'users'.</p>";
    }
    
    // Verificar si hay técnicos en el sistema
    $checkTechnicians = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'technician'");
    $techCount = $checkTechnicians->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Si no hay técnicos, crear uno automáticamente
    if ($techCount == 0) {
        $timestamp = time();
        $testTechnicianData = [
            'name' => 'Técnico de Prueba',
            'email' => "tecnico{$timestamp}@ejemplo.com",
            'phone' => '123456789',
            'role' => 'technician',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        
        // Insertar el técnico de prueba
        $columns = implode(', ', array_keys($testTechnicianData));
        $placeholders = implode(', ', array_fill(0, count($testTechnicianData), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($testTechnicianData));
        
        echo "<div class='alert alert-info'>Se ha creado un técnico de prueba automáticamente porque no había técnicos en el sistema.</div>";
    }
    
    // Actualizar directamente todos los técnicos a Zona 1
    $stmt = $pdo->prepare("UPDATE users SET zone = 'Zona 1' WHERE role = 'technician'");
    $stmt->execute();
    $rowCount = $stmt->rowCount();
    
    // Obtener los técnicos para mostrarlos
    $technicians = $pdo->query("SELECT id, name, email, phone, zone FROM users WHERE role = 'technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Mostrar resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Corrección de Zonas</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h2>Corrección de Zonas Completada</h2>
                </div>
                <div class='card-body'>
                    <div class='alert alert-success'>
                        Se han actualizado <strong>{$rowCount}</strong> técnicos a <strong>Zona 1</strong>.
                    </div>
                    
                    <h4>Técnicos en el sistema:</h4>
                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Zona</th>
                                </tr>
                            </thead>
                            <tbody>";
    
    if (count($technicians) > 0) {
        foreach ($technicians as $tech) {
            $zone = isset($tech['zone']) && !empty($tech['zone']) ? $tech['zone'] : 'No asignada';
            echo "<tr>
                    <td>{$tech['id']}</td>
                    <td>{$tech['name']}</td>
                    <td>{$tech['email']}</td>
                    <td>{$tech['phone']}</td>
                    <td>{$zone}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='text-center'>No hay técnicos en el sistema</td></tr>";
    }
    
    echo "      </tbody>
                        </table>
                    </div>
                    
                    <h4 class='mt-4'>Opciones:</h4>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='card mb-3'>
                                <div class='card-header'>Crear un técnico de prueba</div>
                                <div class='card-body'>
                                    <form method='post' action='fix_zones.php'>
                                        <input type='hidden' name='action' value='create_test_technician'>
                                        <button type='submit' class='btn btn-success'>Crear Técnico de Prueba</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='card mb-3'>
                                <div class='card-header'>Ir a la página de técnicos</div>
                                <div class='card-body'>
                                    <a href='admin/technicians.php' class='btn btn-primary'>Volver a Técnicos</a>
                                </div>
                            </div>
                        </div>
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
                    <a href='admin/technicians.php' class='btn btn-primary'>Volver a Técnicos</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

// Crear un técnico de prueba si se solicita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_test_technician') {
    try {
        // Generar un email único
        $timestamp = time();
        $testTechnicianData = [
            'name' => 'Técnico de Prueba',
            'email' => "tecnico{$timestamp}@ejemplo.com",
            'phone' => '123456789',
            'zone' => 'Zona 1',
            'role' => 'technician',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        
        // Insertar el técnico de prueba
        $columns = implode(', ', array_keys($testTechnicianData));
        $placeholders = implode(', ', array_fill(0, count($testTechnicianData), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($testTechnicianData));
        
        // Redirigir a la misma página para mostrar el resultado
        header('Location: fix_zones.php');
        exit;
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error al crear técnico de prueba: " . $e->getMessage() . "</div>";
    }
}
