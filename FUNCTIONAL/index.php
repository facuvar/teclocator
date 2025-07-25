<?php
// Última prueba de diagnóstico. Forzando a la aplicación a hablar.

// Configurar el registro de errores de inmediato.
$log_file = __DIR__ . '/logs/diagnostic.log';
ini_set('log_errors', 1);
ini_set('error_log', $log_file);
error_reporting(E_ALL);

// Si este mensaje aparece en el log, sabemos que el script ha comenzado.
error_log("--- NUEVA SOLICITUD --- index.php comenzó a ejecutarse.");

// Probar la conexión a la base de datos, que es el sospechoso número 1.
try {
    error_log("Intentando cargar la configuración de la base de datos...");
    $config = require __DIR__ . '/config/database.php';
    error_log("Configuración de la base de datos cargada con éxito.");

    $host = $config['host'];
    $dbname = $config['dbname'];
    $username = $config['username'];
    $password = $config['password'];
    $port = $config['port'];
    
    error_log("Intentando conectar a MySQL en el host: " . $host . " en el puerto: " . $port);
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, $config['options']);
    error_log("ÉXITO: Conexión a la base de datos establecida.");

} catch (Throwable $e) {
    // Registrar cualquier excepción, incluidos los errores de conexión.
    error_log("ERROR FATAL durante la conexión a la base de datos: " . $e->getMessage());
    // También mostrar en el navegador si es posible.
    echo "FALLO LA CONEXIÓN A LA BASE DE DATOS. Revisa logs/diagnostic.log para más detalles.";
    exit;
}

// Si llegamos hasta aquí, la base de datos está bien. Intentemos cargar el resto.
error_log("La conexión a la base de datos fue exitosa. Intentando cargar init.php...");
require_once __DIR__ . '/includes/init.php';
error_log("ÉXITO: init.php cargado.");

echo "Si ves este mensaje, la aplicación se ha inicializado correctamente.";
exit;

?>
