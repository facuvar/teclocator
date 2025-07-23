<?php
/**
 * Check users in database
 */
require_once '../includes/init.php';

// Get database connection
$db = Database::getInstance();

// Get all users
$users = $db->select("SELECT id, name, email, role FROM users");

echo "<h1>Usuarios en la base de datos</h1>";

if (count($users) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay usuarios en la base de datos.</p>";
}

// Check if database setup script was run
echo "<h2>Verificación de la base de datos</h2>";

// Check tables
$tables = ['users', 'clients', 'tickets', 'visits'];
$allTablesExist = true;

foreach ($tables as $table) {
    try {
        $count = $db->selectOne("SELECT COUNT(*) as count FROM $table")['count'];
        echo "<p>Tabla '$table': Existe (contiene $count registros)</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Tabla '$table': No existe</p>";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<p style='color:red'><strong>La base de datos no está configurada correctamente. Por favor, ejecute el script de configuración.</strong></p>";
    echo "<p><a href='setup_database.php'>Ejecutar script de configuración</a></p>";
}

// Provide login information
echo "<h2>Información de inicio de sesión</h2>";
echo "<p>Si la base de datos está configurada correctamente, debería poder iniciar sesión con las siguientes credenciales:</p>";
echo "<ul>";
echo "<li><strong>Administrador:</strong> admin@example.com / admin123</li>";
echo "<li><strong>Técnico:</strong> tech@example.com / tech123</li>";
echo "</ul>";

// Check login page
echo "<h2>Página de inicio de sesión</h2>";
echo "<p>Asegúrese de que está utilizando la página de inicio de sesión correcta:</p>";

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
    echo "<p>La página de inicio de sesión parece ser: <a href='../$loginFile'>$loginFile</a></p>";
} else {
    echo "<p style='color:red'>No se pudo encontrar la página de inicio de sesión.</p>";
}

// Link back to application
echo "<p><a href='../index.php'>Volver a la aplicación</a></p>";
?>
