<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración robusta de CORS para la API Senselab Core
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
    | Incluye rutas de API, Sanctum CSRF, y rutas de autenticación SSO.
    |
    */
    'paths' => explode(',', env('CORS_PATHS', 'api/*,sanctum/csrf-cookie,login,logout')),

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
    | Producción:
    | - https://scisenselab.com          (portal principal)
    | - https://app.scisenselab.com      (frontend SPA)
    | - https://portal.scisenselab.com   (portal de inquilinos)
    |
    | Desarrollo:
    | - http://localhost:3000, http://localhost:5173, http://localhost:8000
    |
    */
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'https://scisenselab.com,https://app.scisenselab.com,https://portal.scisenselab.com,http://localhost:3000,http://localhost:5173,http://localhost:8000'
    )),

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Soporte para subdominios multi-tenant B2B de forma dinámica (Regex).
    | Permite *.scisenselab.com para cualquier subdominio de inquilino.
    |
    | Ejemplo: empresa1.scisenselab.com, cliente-abc.scisenselab.com
    |
    */
    'allowed_origins_patterns' => array_filter([
        // Subdominios del tenant (Multi-tenancy) - *.scisenselab.com
        env('CORS_SUBDOMAIN_PATTERN', '#^https://[a-zA-Z0-9\-]+\.scisenselab\.com$#'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Headers personalizados que los clientes pueden enviar.
    |
    | 🚨 CLAVE: Incluye cabeceras del ecosistema Senselab:
    | - X-Senselab-Tenant-Id: Identificador del inquilino activo (multi-tenant)
    | - X-Tenant: Cabecera simplificada de inquilino
    | - X-API-Key: Autenticación por API key
    | - X-Tenant-ID: Identificador de inquilino (legacy)
    |
    */
    'allowed_headers' => explode(',', env(
        'CORS_ALLOWED_HEADERS',
        'Content-Type,Authorization,X-Requested-With,Accept,Origin,Access-Control-Request-Method,Access-Control-Request-Headers,X-API-Key,X-Tenant-ID,X-Senselab-Tenant-Id,X-Tenant'
    )),

    /*
    |--------------------------------------------------------------------------
    | CORS Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers que el navegador puede acceder en las respuestas CORS.
    | Por defecto, solo los headers "simples" están disponibles.
    |
    | 🚨 CLAVE: Exponer headers de respuesta para que Axios/Fetch los lea:
    | - Deprecation: Utilizada por el Sunset Middleware (RFC 8594)
    | - Sunset: Alerta de fecha límite de apagado de endpoints (RFC 8594)
    |
    */
    'exposed_headers' => explode(',', env(
        'CORS_EXPOSED_HEADERS',
        'X-Total-Count,X-Page-Count,X-Current-Page,X-Per-Page,X-Request-ID,X-Response-Time,Deprecation,Sunset'
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
    | - Producción: 600 (10 minutos) — balance entre UX y seguridad
    |
    */
    'max_age' => (int) env('CORS_MAX_AGE', env('APP_ENV') === 'production' ? 600 : 0),

    /*
    |--------------------------------------------------------------------------
    | CORS Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Si se debe permitir cookies y headers de autenticación en requests CORS.
    |
    | 🚨 CRÍTICO para SSO multi-subdomain:
    | - Si es true: 'allowed_origins' NO puede ser '*'
    | - Las cookies de sesión serán compartidas entre subdominios
    | - Permite autenticación multitab en *.scisenselab.com
    | - SameSite cookie flag DEBE estar configurado en session.php
    |
    */
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
