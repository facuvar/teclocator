<?php
/**
 * Script para verificar la estructura de la tabla de usuarios y mostrar técnicos
 */
require_once 'includes/init.php';

// Get database connection
$db = Database::getInstance();

try {
    // Obtener la estructura de la tabla users
    echo "<h2>Estructura de la tabla 'users'</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    $structure = $db->query("DESCRIBE users");
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Obtener técnicos con sus zonas
    echo "<h2>Técnicos y sus zonas</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Zona</th></tr>";
    
    $technicians = $db->query("SELECT id, name, email, phone, zone FROM users WHERE role = 'technician' ORDER BY name");
    while ($tech = $technicians->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $tech['id'] . "</td>";
        echo "<td>" . $tech['name'] . "</td>";
        echo "<td>" . $tech['email'] . "</td>";
        echo "<td>" . $tech['phone'] . "</td>";
        echo "<td>" . ($tech['zone'] ?? 'No asignada') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Actualizar zonas si es necesario
    echo "<h2>Actualizar zonas</h2>";
    echo "<p>Para actualizar todas las zonas de técnicos a 'Zona 1', <a href='update_zones.php'>haga clic aquí</a>.</p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Ocurrió un error: " . $e->getMessage() . "</p>";
}
