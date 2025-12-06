<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Google Gemini AI
    | API GRATUITA - Sin costos de uso
    |
    | Obtener API Key: https://aistudio.google.com/
    |
    */

    // API Key de Google Gemini (obtener gratis en aistudio.google.com)
    'api_key' => env('GEMINI_API_KEY', ''),

    // URL base de la API
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    /*
    |--------------------------------------------------------------------------
    | Modelos Disponibles (Gratuitos)
    |--------------------------------------------------------------------------
    */
    'models' => [
        // Modelo principal para chat - Rápido y gratuito
        'chat' => env('GEMINI_CHAT_MODEL', 'gemini-2.0-flash'),
        
        // Modelo con capacidad de visión para OCR
        'vision' => env('GEMINI_VISION_MODEL', 'gemini-1.5-flash'),
        
        // Modelo para tareas complejas (límite más bajo en tier gratis)
        'pro' => env('GEMINI_PRO_MODEL', 'gemini-1.5-pro'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (Tier Gratuito)
    |--------------------------------------------------------------------------
    | Gemini Free Tier:
    | - gemini-2.0-flash: 15 RPM (requests per minute)
    | - gemini-1.5-flash: 15 RPM
    | - gemini-1.5-pro: 2 RPM
    |
    */
    'rate_limits' => [
        'gemini-2.0-flash' => [
            'rpm' => 15,           // Requests per minute
            'tpm' => 1000000,      // Tokens per minute
            'rpd' => 1500,         // Requests per day
        ],
        'gemini-1.5-flash' => [
            'rpm' => 15,
            'tpm' => 1000000,
            'rpd' => 1500,
        ],
        'gemini-1.5-pro' => [
            'rpm' => 2,
            'tpm' => 32000,
            'rpd' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Generación
    |--------------------------------------------------------------------------
    */
    'generation' => [
        'temperature' => 0.7,
        'top_p' => 0.95,
        'top_k' => 40,
        'max_output_tokens' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Settings
    |--------------------------------------------------------------------------
    | Configuración de seguridad para filtrar contenido
    */
    'safety_settings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_ONLY_HIGH',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración OCR
    |--------------------------------------------------------------------------
    */
    'ocr' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'supported_formats' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        'model' => 'gemini-1.5-flash', // Modelo con visión
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Chatbot
    |--------------------------------------------------------------------------
    */
    'chatbot' => [
        'model' => 'gemini-2.0-flash',
        'context_messages' => 5,
        'max_tokens' => 2048,
        'temperature' => 0.7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
        'prefix' => 'gemini_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('GEMINI_LOG_ENABLED', true),
        'channel' => env('GEMINI_LOG_CHANNEL', 'daily'),
    ],
];

