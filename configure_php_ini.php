<?php
$phpIniPath = 'C:\\xampp\\php\\php.ini';
$caCertPath = 'C:\\xampp\\php\\cacert.pem';

// 1. Hacer una copia de seguridad del php.ini original
if (!copy($phpIniPath, $phpIniPath . '.bak')) {
    die("Error: No se pudo crear la copia de seguridad de php.ini.\n");
}

// 2. Leer el contenido del php.ini
$phpIniContent = file_get_contents($phpIniPath);
if ($phpIniContent === false) {
    die("Error: No se pudo leer el archivo php.ini.\n");
}

$caCertLine = 'openssl.cafile="' . $caCertPath . '"';
$curlCaInfoLine = 'curl.cainfo="' . $caCertPath . '"';

// 3. Buscar y reemplazar o agregar las directivas necesarias
$phpIniContent = preg_replace('/^;?openssl.cafile\s*=.*/m', $caCertLine, $phpIniContent, -1, $count1);
if ($count1 === 0) {
    $phpIniContent .= "\n" . $caCertLine;
}

$phpIniContent = preg_replace('/^;?curl.cainfo\s*=.*/m', $curlCaInfoLine, $phpIniContent, -1, $count2);
if ($count2 === 0) {
    $phpIniContent .= "\n" . $curlCaInfoLine;
}

// 4. Escribir los cambios en el php.ini
if (file_put_contents($phpIniPath, $phpIniContent) === false) {
    // Si falla, restaurar desde la copia de seguridad
    copy($phpIniPath . '.bak', $phpIniPath);
    die("Error: No se pudo escribir en el archivo php.ini. Se ha restaurado la copia de seguridad.\n");
}

echo "El archivo php.ini ha sido configurado exitosamente.\n";
?> 