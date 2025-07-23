# Sistema de Gestión de Tickets para Reparación de Ascensores

Sistema web en PHP y MySQL que permite a administradores y técnicos gestionar tickets de reparación de ascensores con validación de ubicación mediante geolocalización y escaneo de códigos QR.

## Características principales

- Autenticación segura para administradores y técnicos
- Gestión de clientes, técnicos y tickets
- Validación de ubicación mediante geolocalización
- Escaneo de códigos QR para iniciar y finalizar visitas
- Visualización de ubicaciones en mapa (OpenStreetMap/Leaflet)
- Diseño oscuro, minimalista y profesional
- Responsive para dispositivos móviles
- Notificaciones por WhatsApp para técnicos

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Navegador con soporte para geolocalización y cámara

## Instalación Local

1. Clonar o descargar el repositorio
2. Importar la base de datos desde `database/schema.sql`
3. Configurar los parámetros de conexión en `config/database.php`
4. Acceder mediante el navegador a la ruta del proyecto

## Migración a Producción (https://teclocate.ascensorescompany.com)

1. **Preparación del servidor:**
   - Asegurarse de que el servidor cumple con los requisitos mínimos
   - Configurar el dominio para que apunte al directorio correcto
   - Verificar que el certificado SSL esté correctamente instalado

2. **Transferencia de archivos:**
   - Transferir todos los archivos al servidor mediante FTP o SSH
   - Asegurarse de mantener la estructura de directorios intacta
   - Excluir archivos de desarrollo y depuración (logs, archivos de prueba, etc.)

3. **Configuración de la base de datos:**
   - Crear una base de datos en el servidor de producción
   - Importar la estructura y datos desde el archivo `database/schema.sql`
   - Crear un usuario con permisos limitados para la aplicación

4. **Actualización de configuraciones:**
   - Actualizar `config/database.php` con las credenciales de producción
   - Verificar que `includes/init.php` tenga la URL base correcta: `https://teclocate.ascensorescompany.com/`
   - Configurar correctamente los parámetros de WhatsApp en `config/whatsapp.php`

5. **Ajustes de seguridad:**
   - Establecer permisos adecuados en archivos y directorios
   - Proteger directorios sensibles con .htaccess
   - Eliminar o proteger archivos de depuración y pruebas
   - Cambiar las contraseñas por defecto de los usuarios

6. **Verificación:**
   - Comprobar que todas las funcionalidades principales funcionan correctamente
   - Verificar que los mapas se cargan correctamente
   - Probar el escaneo de códigos QR y la validación de ubicación
   - Confirmar que las notificaciones por WhatsApp funcionan correctamente

7. **Ajustes finales:**
   - Revertir el límite de distancia permitida de 2km a 100m en:
     - La interfaz de usuario (scan_qr.js)
     - El API de inicio de visita (start_visit.php)
     - El API de finalización de visita (end_visit.php)

## Usuarios por defecto

### Administrador
- Usuario: admin@example.com
- Contraseña: admin123

### Técnico
- Usuario: tecnico@example.com
- Contraseña: tecnico123

## Notas importantes para producción

- Cambiar todas las contraseñas por defecto antes de poner en producción
- Configurar correctamente las credenciales de la API de WhatsApp Business
- Verificar que la zona horaria esté configurada para Argentina (UTC-3)
- Realizar copias de seguridad regulares de la base de datos
