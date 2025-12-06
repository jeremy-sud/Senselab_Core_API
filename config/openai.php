<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para integración con OpenAI API (GPT-4, Vision, Embeddings)
    | Desarrollado por Sistemas Ursol S.A.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Modelos por Defecto
    |--------------------------------------------------------------------------
    */

    'models' => [
        // Modelo principal para chat/completions
        'chat' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),

        // Modelo para análisis de imágenes (OCR facturas)
        'vision' => env('OPENAI_VISION_MODEL', 'gpt-4o'),

        // Modelo para embeddings (búsqueda semántica)
        'embeddings' => env('OPENAI_EMBEDDINGS_MODEL', 'text-embedding-3-small'),

        // Modelo económico para tareas simples
        'mini' => env('OPENAI_MINI_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Requests
    |--------------------------------------------------------------------------
    */

    'request' => [
        // Timeout en segundos
        'timeout' => env('OPENAI_TIMEOUT', 60),

        // Reintentos automáticos
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),

        // Delay entre reintentos (segundos)
        'retry_delay' => env('OPENAI_RETRY_DELAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración por Feature
    |--------------------------------------------------------------------------
    */

    'features' => [
        // OCR para facturas de proveedores
        'ocr' => [
            'enabled' => env('OPENAI_OCR_ENABLED', true),
            'max_file_size_mb' => env('OPENAI_OCR_MAX_SIZE', 20),
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            'detail' => env('OPENAI_OCR_DETAIL', 'high'), // 'low', 'high', 'auto'
        ],

        // Chatbot asistente ERP
        'chatbot' => [
            'enabled' => env('OPENAI_CHATBOT_ENABLED', true),
            'max_tokens' => env('OPENAI_CHATBOT_MAX_TOKENS', 2048),
            'temperature' => env('OPENAI_CHATBOT_TEMPERATURE', 0.7),
            'system_prompt' => env('OPENAI_CHATBOT_SYSTEM_PROMPT'),
        ],

        // Predicciones (demanda, inventario)
        'predictions' => [
            'enabled' => env('OPENAI_PREDICTIONS_ENABLED', true),
            'cache_ttl' => env('OPENAI_PREDICTIONS_CACHE_TTL', 3600), // 1 hora
        ],

        // Generación de contenido (emails, recordatorios)
        'content' => [
            'enabled' => env('OPENAI_CONTENT_ENABLED', true),
            'max_tokens' => env('OPENAI_CONTENT_MAX_TOKENS', 1024),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('OPENAI_RATE_LIMIT_ENABLED', true),
        'requests_per_minute' => env('OPENAI_RPM', 60),
        'tokens_per_minute' => env('OPENAI_TPM', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('OPENAI_LOGGING_ENABLED', true),
        'channel' => env('OPENAI_LOG_CHANNEL', 'stack'),
        'log_prompts' => env('OPENAI_LOG_PROMPTS', false), // Cuidado con datos sensibles
        'log_responses' => env('OPENAI_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Costos Estimados (para tracking)
    |--------------------------------------------------------------------------
    */

    'costs' => [
        'gpt-4o' => [
            'input' => 0.005,  // por 1K tokens
            'output' => 0.015, // por 1K tokens
        ],
        'gpt-4o-mini' => [
            'input' => 0.00015,
            'output' => 0.0006,
        ],
        'text-embedding-3-small' => [
            'input' => 0.00002,
        ],
    ],

];

