<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Desactivar el límite de tiempo de ejecución para scripts largos
set_time_limit(0);

echo "<h1>Importador de Base de Datos</h1>";

try {
    // 1. Conectar a la base de datos
    // Incluimos el init.php para tener acceso a la conexión de la BD
    require_once 'includes/init.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<p>✅ Conexión a la base de datos establecida.</p>";

    // 2. Leer el archivo SQL
    $sqlFile = 'database/railway_schema_ultra_safe.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("El archivo SQL no se encuentra en la ruta: $sqlFile");
    }
    $sql = file_get_contents($sqlFile);
    echo "<p>✅ Archivo SQL leído correctamente: <code>$sqlFile</code></p>";

    // 3. Ejecutar el script SQL
    // PDO::exec() es capaz de manejar múltiples sentencias
    $pdo->exec($sql);
    echo "<h2>✅ ¡Éxito! La base de datos ha sido importada correctamente.</h2>";
    echo "<p style='color:red; font-weight:bold;'>Por favor, elimina este archivo (import_db.php) ahora por seguridad.</p>";

} catch (PDOException $e) {
    echo "<h2>❌ Error de Base de Datos</h2>";
    echo "<p>Ha ocurrido un error durante la importación:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Ha ocurrido un error:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?> 