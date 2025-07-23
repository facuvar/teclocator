<?php
/**
 * WhatsApp Notifier Class
 * 
 * Esta clase maneja el envío de notificaciones por WhatsApp a los técnicos
 * utilizando la API de WhatsApp Business.
 */
class WhatsAppNotifier {
    // Configuración de la API
    private $apiUrl;
    private $token;
    private $phoneNumberId;
    private $countryCode;
    private $baseUrl;
    private $defaultTemplate;
    private $defaultLanguage;
    private $debugMode;
    
    /**
     * Constructor
     */
    public function __construct($debugMode = false) {
        // Cargar configuración
        $configFile = __DIR__ . '/../config/whatsapp.php';
        
        if (!file_exists($configFile)) {
            throw new Exception("Archivo de configuración de WhatsApp no encontrado: {$configFile}");
        }
        
        $config = require $configFile;
        
        if (!is_array($config)) {
            throw new Exception("El archivo de configuración de WhatsApp no devolvió un array válido");
        }
        
        $this->apiUrl = $config['api_url'] ?? 'https://graph.facebook.com/v17.0/';
        $this->token = $config['token'] ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? '';
        $this->countryCode = $config['country_code'] ?? '54';
        $this->baseUrl = $config['base_url'] ?? 'http://localhost/company-teclocator-v2/';
        $this->defaultTemplate = $config['default_template'] ?? 'nuevo_ticket';
        $this->defaultLanguage = $config['default_language'] ?? 'es_AR';
        $this->debugMode = $debugMode;
        
        // Verificar configuración mínima
        if (empty($this->token) || empty($this->phoneNumberId)) {
            $this->logError("Configuración de WhatsApp incompleta: token o phone_number_id vacíos");
        }
    }
    
    /**
     * Envía una notificación de nuevo ticket a un técnico
     * 
     * @param array $technician Datos del técnico
     * @param array $ticket Datos del ticket
     * @param array $client Datos del cliente
     * @param string $templateName Nombre de la plantilla a usar (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public function sendTicketNotification($technician, $ticket, $client, $templateName = null) {
        // Verificar que el técnico tenga número de teléfono
        if (empty($technician['phone'])) {
            $this->logError("No se puede enviar notificación: el técnico {$technician['name']} (ID: {$technician['id']}) no tiene número de teléfono");
            return false;
        }
        
        // Formatear el número de teléfono (eliminar espacios, guiones, etc.)
        $phone = $this->formatPhoneNumber($technician['phone']);
        $this->logInfo("Enviando notificación a técnico: {$technician['name']} (ID: {$technician['id']}) - Teléfono formateado: {$phone}");
        
        // Usar plantilla por defecto si no se especifica otra
        $templateName = $templateName ?? $this->defaultTemplate;
        $this->logInfo("Usando plantilla: {$templateName}");
        
        // Enviar la plantilla sin parámetros
        return $this->sendTemplateMessage($phone, $templateName);
    }
    
    /**
     * Formatea un número de teléfono para WhatsApp
     * 
     * @param string $phone Número de teléfono
     * @return string Número formateado
     */
    private function formatPhoneNumber($phone) {
        // Registrar el número original para diagnóstico
        $this->logInfo("Formateando número de teléfono: '{$phone}'");
        
        // Eliminar caracteres no numéricos (excepto el signo +)
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $this->logInfo("Después de eliminar caracteres no numéricos: '{$phone}'");
        
        // Si el número comienza con +, eliminarlo
        if (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
            $this->logInfo("Después de eliminar + inicial: '{$phone}'");
        }
        
        // Si el número comienza con 0, eliminarlo
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
            $this->logInfo("Después de eliminar 0 inicial: '{$phone}'");
        }
        
        // Si el número comienza con 15 y tiene el código de país, manejar formato argentino
        if (substr($phone, 0, 2) === '15' && (substr($phone, 0, 4) !== '5415' && substr($phone, 0, 6) !== '549115')) {
            $phone = substr($phone, 2);
            $this->logInfo("Después de eliminar '15' inicial: '{$phone}'");
        }
        
        // Asegurarse de que tenga el código de país completo (549 para Argentina)
        if (substr($phone, 0, 3) !== '549') {
            // Si ya tiene el 54 pero no el 9
            if (substr($phone, 0, 2) === '54') {
                if (substr($phone, 2, 1) !== '9') {
                    $phone = '549' . substr($phone, 2);
                }
            } else {
                $phone = '549' . $phone;
            }
            $this->logInfo("Después de añadir código de país completo: '{$phone}'");
        }
        
        // Asegurarse de que tenga el formato correcto para WhatsApp en Argentina (549 + código de área sin 0 + número)
        $this->logInfo("Número final formateado: '{$phone}'");
        return $phone;
    }
    
    /**
     * Envía un mensaje usando una plantilla de WhatsApp con parámetros
     * 
     * @param string $to Número de teléfono del destinatario
     * @param string $templateName Nombre de la plantilla
     * @param array $params Parámetros para la plantilla (opcional)
     * @param string $languageCode Código de idioma (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public function sendTemplateMessageWithParams($to, $templateName, $params = [], $languageCode = null) {
        $url = $this->apiUrl . $this->phoneNumberId . '/messages';
        
        // Usar idioma por defecto si no se especifica otro
        $languageCode = $languageCode ?? $this->defaultLanguage;
        
        // Datos para la plantilla
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];
        
        // Agregar parámetros si existen
        if (!empty($params)) {
            $data['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => $params
                ]
            ];
        }
        
        $this->logInfo("Datos de la solicitud: " . json_encode($data, JSON_PRETTY_PRINT));
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        if (!$success) {
            $this->logError("Error al enviar plantilla de WhatsApp (HTTP {$httpCode}): " . $response);
            if (!empty($curlError)) {
                $this->logError("Error de cURL: " . $curlError);
            }
        } else {
            $this->logInfo("Mensaje de WhatsApp con plantilla enviado correctamente a: " . $to);
            $this->logInfo("Respuesta: " . $response);
        }
        
        return $success;
    }
    
    /**
     * Envía un mensaje usando una plantilla de WhatsApp sin parámetros
     * 
     * @param string $to Número de teléfono del destinatario
     * @param string $templateName Nombre de la plantilla
     * @param string $languageCode Código de idioma (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public function sendTemplateMessage($to, $templateName, $languageCode = null) {
        // Crear archivo de log para esta operación
        $logFile = __DIR__ . '/../whatsapp_log_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Iniciando envío de plantilla WhatsApp\n");
        file_put_contents($logFile, "Destinatario: {$to}\n", FILE_APPEND);
        file_put_contents($logFile, "Plantilla: {$templateName}\n", FILE_APPEND);
        file_put_contents($logFile, "Idioma: " . ($languageCode ?? $this->defaultLanguage) . "\n", FILE_APPEND);
        
        try {
            $result = $this->sendTemplateMessageWithParams($to, $templateName, [], $languageCode);
            file_put_contents($logFile, "Resultado: " . ($result ? "ÉXITO" : "ERROR") . "\n", FILE_APPEND);
            return $result;
        } catch (Exception $e) {
            file_put_contents($logFile, "EXCEPCIÓN: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            return false;
        }
    }
    
    /**
     * Envía un mensaje de bienvenida a un nuevo técnico
     * 
     * @param array $technician Datos del técnico
     * @return bool Éxito o fracaso del envío
     */
    public function sendWelcomeMessage($technician) {
        // Verificar que el técnico tenga número de teléfono
        if (empty($technician['phone'])) {
            $this->logError("No se puede enviar mensaje de bienvenida: el técnico {$technician['name']} (ID: {$technician['id']}) no tiene número de teléfono");
            return false;
        }
        
        // Formatear el número de teléfono
        $phone = $this->formatPhoneNumber($technician['phone']);
        $this->logInfo("Enviando mensaje de bienvenida a técnico: {$technician['name']} (ID: {$technician['id']}) - Teléfono formateado: {$phone}");
        
        // Datos para el mensaje
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => "¡Hola {$technician['name']}! Bienvenido/a al sistema de gestión de tickets. 👋\n\n" .
                          "Este es el número oficial para notificaciones de tickets. Por favor, *responde a este mensaje* con un OK para activar las notificaciones.\n\n" .
                          "Recibirás alertas en este número cuando se te asigne un nuevo ticket. 🔔\n\n" .
                          "Gracias por tu colaboración."
            ]
        ];
        
        $this->logInfo("Datos de la solicitud: " . json_encode($data, JSON_PRETTY_PRINT));
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];
        
        $url = $this->apiUrl . $this->phoneNumberId . '/messages';
        $this->logInfo("URL de la API: {$url}");
        
        // Configurar opciones de cURL con mayor detalle
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL para pruebas
        
        // Capturar información detallada de cURL
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        // Obtener información detallada de cURL
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        // Registrar información detallada
        $this->logInfo("Información detallada de cURL:\n{$verboseLog}");
        
        curl_close($ch);
        
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        if (!$success) {
            $this->logError("Error al enviar mensaje de bienvenida (HTTP {$httpCode}): " . $response);
            if (!empty($curlError)) {
                $this->logError("Error de cURL: " . $curlError);
            }
        } else {
            $this->logInfo("Mensaje de bienvenida enviado correctamente a: " . $phone);
            $this->logInfo("Respuesta: " . $response);
        }
        
        return $success;
    }
    
    /**
     * Registra un mensaje de error
     * 
     * @param string $message Mensaje de error
     */
    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}";
        error_log($logMessage);
        
        // Guardar en archivo de log específico para WhatsApp
        $logFile = dirname(__DIR__) . '/logs/whatsapp_' . date('Y-m-d') . '.log';
        $this->writeToLogFile($logFile, $logMessage);
        
        if ($this->debugMode) {
            echo $logMessage . "<br>";
        }
    }
    
    /**
     * Registra un mensaje informativo
     * 
     * @param string $message Mensaje informativo
     */
    private function logInfo($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] INFO: {$message}";
        error_log($logMessage);
        
        // Guardar en archivo de log específico para WhatsApp
        $logFile = dirname(__DIR__) . '/logs/whatsapp_' . date('Y-m-d') . '.log';
        $this->writeToLogFile($logFile, $logMessage);
        
        if ($this->debugMode) {
            echo $logMessage . "<br>";
        }
    }
    
    /**
     * Escribe un mensaje en un archivo de log
     * 
     * @param string $logFile Ruta del archivo de log
     * @param string $message Mensaje a escribir
     */
    private function writeToLogFile($logFile, $message) {
        // Crear directorio de logs si no existe
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        // Añadir nueva línea al final del mensaje
        if (substr($message, -1) !== "\n") {
            $message .= "\n";
        }
        
        // Escribir en el archivo
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    /**
     * Obtiene el token de la API
     * 
     * @return string Token de la API
     */
    public function getToken() {
        return $this->token;
    }
    
    /**
     * Obtiene la URL base de la API
     * 
     * @return string URL base de la API
     */
    public function getApiUrl() {
        return $this->apiUrl;
    }
    
    /**
     * Obtiene el ID del número de teléfono
     * 
     * @return string ID del número de teléfono
     */
    public function getPhoneNumberId() {
        return $this->phoneNumberId;
    }
}
