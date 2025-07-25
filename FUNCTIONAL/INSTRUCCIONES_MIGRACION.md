# Instrucciones para la Migración a Producción

Este documento contiene las instrucciones detalladas para migrar el sistema TecLocator a https://teclocate.ascensorescompany.com.

## 1. Preparación del Servidor

1. **Requisitos del servidor:**
   - PHP 7.4 o superior
   - MySQL 5.7 o superior
   - Servidor web Apache o Nginx
   - Certificado SSL instalado y configurado
   - Módulos PHP requeridos: PDO, PDO_MySQL, GD, mbstring, xml, curl

2. **Configuración del dominio:**
   - Asegúrate de que el dominio `teclocate.ascensorescompany.com` apunte al directorio correcto en el servidor
   - Verifica que el certificado SSL esté correctamente instalado y configurado

## 2. Transferencia de Archivos

1. **Archivos a transferir:**
   - Transfiere todos los archivos del proyecto al servidor mediante FTP o SSH
   - Mantén la estructura de directorios intacta

2. **Archivos a excluir:**
   - Archivos de log (*.log)
   - Archivos de prueba (test_*.php)
   - Archivos de depuración (debug_*.php)
   - Archivos temporales o de desarrollo

## 3. Configuración de la Base de Datos

1. **Creación de la base de datos:**
   ```sql
   CREATE DATABASE teclocate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Creación del usuario:**
   ```sql
   CREATE USER 'teclocate_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
   GRANT ALL PRIVILEGES ON teclocate_db.* TO 'teclocate_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Importación de la estructura y datos:**
   ```bash
   mysql -u teclocate_user -p teclocate_db < /ruta/al/archivo/database/schema.sql
   ```

## 4. Configuración de Archivos

1. **Configuración de la base de datos (`config/database.php`):**
   ```php
   <?php
   return [
       'host' => 'localhost',
       'dbname' => 'teclocate_db',
       'username' => 'teclocate_user',
       'password' => 'contraseña_segura', // Reemplazar con la contraseña real
       'charset' => 'utf8mb4',
       'options' => [
           PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES => false,
       ]
   ];
   ```

2. **Configuración de la URL base (`includes/init.php`):**
   ```php
   define('BASE_URL', 'https://teclocate.ascensorescompany.com/');
   ```

3. **Configuración de WhatsApp (`config/whatsapp.php`):**
   - Verifica que las credenciales de la API de WhatsApp Business sean correctas

## 5. Ajustes de Seguridad

1. **Permisos de archivos y directorios:**
   ```bash
   # Establecer permisos adecuados
   find /ruta/al/directorio/raiz -type f -exec chmod 644 {} \;
   find /ruta/al/directorio/raiz -type d -exec chmod 755 {} \;
   
   # Dar permisos de escritura a directorios que lo requieran
   chmod -R 775 /ruta/al/directorio/raiz/logs
   ```

2. **Protección de directorios sensibles:**
   - Crea archivos `.htaccess` para proteger directorios sensibles:
   ```
   # Para directorios de configuración
   <Files ~ "\.php$">
       Order allow,deny
       Deny from all
   </Files>
   ```

3. **Cambio de contraseñas por defecto:**
   - Cambia las contraseñas de los usuarios por defecto:
     - admin@example.com
     - tecnico@example.com

## 6. Ajustes Específicos para Producción

1. **Revertir el límite de distancia de 2km a 100m:**

   a. En `technician/scan_qr.js` (línea 242):
   ```javascript
   const distanceLimit = 100; // 100 metros en producción
   ```

   b. En `api/start_visit.php` (línea 107):
   ```php
   $maxDistance = 100; // 100 metros en producción
   ```

   c. En `api/end_visit.php` (línea 117):
   ```php
   $maxDistance = 100; // 100 metros en producción
   ```

2. **Desactivar el modo de depuración:**
   - En `includes/init.php`, modifica la configuración de errores:
   ```php
   // Para producción
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   ```

## 7. Verificación Final

1. **Pruebas de funcionalidad:**
   - Iniciar sesión como administrador y técnico
   - Crear y editar clientes
   - Crear y asignar tickets
   - Escanear códigos QR y validar ubicación
   - Verificar notificaciones por WhatsApp

2. **Pruebas de seguridad:**
   - Verificar que no se puedan acceder a archivos sensibles
   - Comprobar que las redirecciones funcionen correctamente
   - Verificar que la autenticación funcione correctamente

3. **Pruebas de rendimiento:**
   - Verificar tiempos de carga de páginas
   - Comprobar el rendimiento de consultas a la base de datos

## 8. Mantenimiento

1. **Copias de seguridad:**
   - Configurar copias de seguridad automáticas de la base de datos
   - Establecer un plan de respaldo de archivos

2. **Monitoreo:**
   - Configurar monitoreo de disponibilidad del servidor
   - Establecer alertas para errores críticos

## Contacto para Soporte

Si encuentras algún problema durante la migración, contacta al equipo de desarrollo:
- Email: soporte@ascensorescompany.com
