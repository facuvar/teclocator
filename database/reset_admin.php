<?php
/**
 * Reset admin credentials
 */
require_once '../includes/init.php';

// Get database connection
$db = Database::getInstance();

try {
    // Check if the users table exists
    $db->selectOne("SELECT 1 FROM users LIMIT 1");
    
    echo "<h1>Restablecimiento de credenciales de administrador</h1>";
    
    // Create or update admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Check if admin user exists
    $adminExists = $db->selectOne("SELECT id FROM users WHERE email = ? AND role = ?", ['admin@example.com', 'admin']);
    
    if ($adminExists) {
        // Update existing admin
        $db->update('users', 
            ['password' => $hashedPassword, 'name' => 'Administrator'],
            "id = ?", 
            [$adminExists['id']]
        );
        echo "<p>Usuario administrador actualizado con éxito.</p>";
    } else {
        // Create new admin
        $db->insert('users', [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => $hashedPassword,
            'role' => 'admin'
        ]);
        echo "<p>Usuario administrador creado con éxito.</p>";
    }
    
    // Create or update technician user
    $hashedPassword = password_hash('tech123', PASSWORD_DEFAULT);
    
    // Check if technician user exists
    $techExists = $db->selectOne("SELECT id FROM users WHERE email = ? AND role = ?", ['tech@example.com', 'technician']);
    
    if ($techExists) {
        // Update existing technician
        $db->update('users', 
            ['password' => $hashedPassword, 'name' => 'Technician', 'zone' => 'Norte'],
            "id = ?", 
            [$techExists['id']]
        );
        echo "<p>Usuario técnico actualizado con éxito.</p>";
    } else {
        // Create new technician
        $db->insert('users', [
            'name' => 'Technician',
            'email' => 'tech@example.com',
            'password' => $hashedPassword,
            'role' => 'technician',
            'zone' => 'Norte'
        ]);
        echo "<p>Usuario técnico creado con éxito.</p>";
    }
    
    echo "<h2>Credenciales de acceso</h2>";
    echo "<p><strong>Administrador:</strong> admin@example.com / admin123</p>";
    echo "<p><strong>Técnico:</strong> tech@example.com / tech123</p>";
    
    // Find login page
    $loginFiles = glob('../*.php');
    $loginFile = null;
    
    foreach ($loginFiles as $file) {
        $content = file_get_contents($file);
        if (strpos($content, 'login') !== false && strpos($content, 'password') !== false) {
            $loginFile = basename($file);
            break;
        }
    }
    
    if ($loginFile) {
        echo "<p>Vaya a la página de inicio de sesión: <a href='../$loginFile'>$loginFile</a></p>";
    } else {
        echo "<p>Vuelva a la aplicación: <a href='../index.php'>Inicio</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Se ha producido un error: " . $e->getMessage() . "</p>";
    
    // Check if database exists
    try {
        $pdo = new PDO('mysql:host=localhost', 'root', '');
        $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'elevator_repair_system'")->fetchColumn();
        
        if (!$result) {
            echo "<p>La base de datos 'elevator_repair_system' no existe. Por favor, ejecute el script de configuración primero.</p>";
            echo "<p><a href='setup_database.php'>Ejecutar script de configuración</a></p>";
        }
    } catch (PDOException $e) {
        echo "<p>No se pudo conectar a MySQL: " . $e->getMessage() . "</p>";
    }
}
?>
