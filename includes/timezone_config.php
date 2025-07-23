<?php
/**
 * Configuración de zona horaria para Argentina
 * Este archivo debe ser incluido al inicio de la aplicación
 */

// Establecer la zona horaria predeterminada para Argentina (Buenos Aires)
date_default_timezone_set('America/Argentina/Buenos_Aires');

/**
 * Función para formatear fechas y horas en formato argentino
 * 
 * @param string $dateTime Fecha y hora en formato MySQL (Y-m-d H:i:s)
 * @param string $format Formato de salida (por defecto: d/m/Y H:i)
 * @return string Fecha formateada
 */
function formatDateTime($dateTime, $format = 'd/m/Y H:i') {
    if (empty($dateTime)) {
        return '-';
    }
    
    $timestamp = strtotime($dateTime);
    return date($format, $timestamp);
}

/**
 * Función para obtener la fecha y hora actual en formato MySQL
 * 
 * @return string Fecha y hora actual en formato Y-m-d H:i:s
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}
