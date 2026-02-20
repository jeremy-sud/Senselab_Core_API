<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración robusta de CORS para la API Ursol CAST
    | Basado en OWASP Top 10 - A01:2021 Broken Access Control
    |
    | Paths: Rutas donde se permite CORS
    | Methods: Métodos HTTP permitidos (GET, POST, PUT, DELETE, OPTIONS, PATCH)
    | Origins: Dominios desde los cuales se permite CORS (usar .env)
    | Headers: Headers personalizados permitidos en requests
    | Exposed Headers: Headers que el navegador puede leer desde la respuesta
    |
    | @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    | @see https://owasp.org/www-community/CORS_Misconfiguration
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Define qué rutas de la aplicación deben tener CORS habilitado.
    | Recomendación: Solo rutas API, no rutas web
    |
    */
    'paths' => explode(',', env('CORS_PATHS', 'api/*,sanctum/csrf-cookie')),

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Métodos HTTP permitidos en solicitudes CORS.
    | SEGURIDAD: Ser explícito en lugar de permitir '*'
    |
    */
    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,HEAD,PUT,PATCH,POST,DELETE,OPTIONS')),

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Dominios desde los cuales se permiten solicitudes CORS.
    |
    | CRITICAL: Nunca usar '*' en producción (permite todos los dominios)
    | Usar variable de entorno para cada ambiente
    |
    | Ejemplos:
    | - Desarrollo: http://localhost:3000, http://localhost:5173
    | - Staging: https://app-staging.example.com
    | - Producción: https://app.example.com
    |
    */
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://localhost:5173,http://localhost:8000'
    )),

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Para casos avanzados: permitir múltiples subdominios dinámicamente.
    | Usar expresiones regulares cuando sea necesario.
    |
    | Ejemplo: /^https:\/\/(.+\.)?example\.com$/
    |
    */
    'allowed_origins_patterns' => [
        // Subdominios del tenant (Multi-tenancy)
        env('CORS_SUBDOMAIN_PATTERN', '')
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Headers personalizados que los clientes pueden enviar.
    | Recomendación: Ser explícito según las necesidades de la API
    |
    */
    'allowed_headers' => explode(',', env(
        'CORS_ALLOWED_HEADERS',
        'Content-Type,Authorization,X-Requested-With,Accept,Origin,Access-Control-Request-Method,Access-Control-Request-Headers,X-API-Key,X-Tenant-ID'
    )),

    /*
    |--------------------------------------------------------------------------
    | CORS Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers que el navegador puede acceder en las respuestas CORS.
    | Por defecto, solo los headers "simples" están disponibles.
    |
    */
    'exposed_headers' => explode(',', env(
        'CORS_EXPOSED_HEADERS',
        'X-Total-Count,X-Page-Count,X-Current-Page,X-Per-Page,X-Request-ID,X-Response-Time'
    )),

    /*
    |--------------------------------------------------------------------------
    | CORS Max Age (en segundos)
    |--------------------------------------------------------------------------
    |
    | Tiempo que el navegador puede cachear la respuesta del CORS preflight.
    |
    | Valores recomendados:
    | - Desarrollo: 0 (sin caché)
    | - Producción: 86400 (24 horas) o 604800 (7 días)
    |
    */
    'max_age' => (int) env('CORS_MAX_AGE', env('APP_ENV') === 'production' ? 86400 : 0),

    /*
    |--------------------------------------------------------------------------
    | CORS Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Si se debe permitir cookies y headers de autenticación en requests CORS.
    |
    | SEGURIDAD CRÍTICA:
    | - Si es true: 'allowed_origins' NO puede ser '*'
    | - Las cookies será enviadas con credenciales del usuario
    | - SameSite cookie flag DEBE estar configurado
    |
    */
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
