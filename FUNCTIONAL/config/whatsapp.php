<?php
/**
 * WhatsApp API configuration
 */
return [
    // URL base de la API de WhatsApp Business
    'api_url' => 'https://graph.facebook.com/v17.0/',
    
    // Token de acceso para la API de WhatsApp Business
    'token' => 'EAAajWSmtP1UBO0oyVHgwg2SHB2kgrBwLNn2Hxke5HZA4ZCocqFLvSeZA1FaYilrcS1vGdQa594z6TOhrcS4lUnB8QL2fy68pAWvAu8r4JhxzZBoiTKIACCHwpplRzjnvHO2RAU4uKoP2WKUvSVRqs6dctkipqUNhqXZBs2l8P2iHTZCcTv9kS2owsqyp2IGiskoQZDZD',
    
    // ID del número de teléfono de WhatsApp Business
    'phone_number_id' => '345032388695817',
    
    // WhatsApp Business Account ID
    'business_account_id' => '247414851788542',
    
    // Business Account ID
    'account_id' => '935803630729512',
    
    // Código de país para formatear números (Argentina = 54)
    'country_code' => '54',
    
    // URL base para enlaces en los mensajes (ajustar según tu dominio)
    'base_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/company-teclocator-v2/',
    
    // Configuración de plantillas
    'default_template' => 'nuevo_ticket', // Nombre de la plantilla por defecto para notificaciones de tickets
    'default_language' => 'es_AR'         // Código de idioma por defecto para las plantillas
];
