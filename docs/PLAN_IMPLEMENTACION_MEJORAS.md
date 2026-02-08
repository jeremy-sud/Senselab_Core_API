# 📋 PLAN DE IMPLEMENTACIÓN DE MEJORAS
## Ursol CAST API - Roadmap Detallado (Crítico → Leve)

**Fecha:** 7 de Febrero de 2026  
**Timeline Total:** 12 semanas (3 meses)  
**Estimación de Esfuerzo:** 240-310 horas de desarrollo  
**Equipo Recomendado:** 2 desarrolladores full-time

---

## 📊 TABLA DE CONTENIDOS

1. [FASE 1: Seguridad Crítica (1-2 semanas)](#fase-1-seguridad-crítica)
2. [FASE 2: Observabilidad (2-3 semanas)](#fase-2-observabilidad)
3. [FASE 3: Auditoría y Compliance (2-3 semanas)](#fase-3-auditoría-y-compliance)
4. [FASE 4: Calidad de Código (3-4 semanas)](#fase-4-calidad-de-código)
5. [FASE 5: Rendimiento (2-3 semanas)](#fase-5-rendimiento)
6. [FASE 6: Arquitectura Avanzada (2-3 semanas)](#fase-6-arquitectura-avanzada)
7. [Timeline Gantt](#timeline-gantt)
8. [Testing & Validation](#testing--validation)
9. [Rollback Strategy](#rollback-strategy)

---

# FASE 1: SEGURIDAD CRÍTICA
## Semanas 1-2 | 40-50 horas

### ✅ ENTREGABLES

Por el final de esta fase:
- [x] Todas las dependencias con versiones fijas (✅ 7 feb 2026)
- [x] CORS y Security Headers configurados (✅ 7 feb 2026)
- [x] Logging estructurado activo (✅ 7 feb 2026)
- [ ] Sentry para error tracking
- [ ] Rate limiting granular por endpoint
- [ ] Datos sensibles encriptados en BD
- [ ] Sistema de auditoría funcionando
- [ ] Adaptive login throttling

---

## 1.1 ✅ COMPLETADO - Fijar Versiones de Dependencias
**Completado:** 7 de febrero de 2026

### Ubicación de Cambios
- **Archivo:** `composer.json`
- **Archivos Afectados:** Toda la aplicación (reload de dependencias)

### Problema
```json
"barryvdh/laravel-dompdf": "*",
"larastan/larastan": "*",
"phpstan/phpstan": "*"
```

Wildcard `*` permite versiones con breaking changes.

### Solución Paso a Paso

#### PASO 1: Identificar versiones actuales
```bash
cd /home/dawnweaber/Workspace/Ursol-CAST-API

# Chequear versiones actuales instaladas
composer show | grep -E "dompdf|larastan|phpstan"

# Output esperado:
# barryvdh/laravel-dompdf              6.4.1
# larastan/larastan                    3.0.3
# phpstan/phpstan                      1.10.45
```

#### PASO 2: Actualizar composer.json
```json
// ANTES
"require": {
    "barryvdh/laravel-dompdf": "*",
    "darkaonline/l5-swagger": "^9.0",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.2",
    "laravel/tinker": "^2.10.1",
    "robrichards/xmlseclibs": "^3.1",
    "spatie/laravel-multitenancy": "^4.0"
}

// DESPUÉS
"require": {
    "barryvdh/laravel-dompdf": "^6.4",
    "darkaonline/l5-swagger": "^9.0",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.2",
    "laravel/tinker": "^2.10.1",
    "robrichards/xmlseclibs": "^3.1",
    "spatie/laravel-multitenancy": "^4.0"
},

// require-dev DESPUÉS
"require-dev": {
    "fakerphp/faker": "^1.23",
    "larastan/larastan": "^3.0",    // CAMBIADO
    "laravel/pail": "^1.2.2",
    "laravel/pint": "^1.24",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.6",
    "phpstan/phpstan": "^1.10",    // CAMBIADO
    "phpunit/phpunit": "^11.5.3"
}
```

#### PASO 3: Actualizar composer.lock
```bash
composer update barryvdh/laravel-dompdf larastan/larastan phpstan/phpstan --no-dev

# Verificar
composer install --no-dev

# Test
php vendor/bin/phpstan analyse app/ --level 8 | head -20
```

#### PASO 4: Agregar más dependencias con versiones seguras
```bash
composer require \
    spatie/laravel-activitylog:^4.3 \
    spatie/laravel-query-builder:^5.9 \
    laravel-json-api/laravel:^4.0
    
# require-dev
composer require --dev \
    barryvdh/laravel-debugbar:^3.9 \
    pestphp/pest:^2.0 \
    pestphp/pest-plugin-laravel:^2.0
```

### Archivo a Crear/Modificar

**[composer.json](composer.json)** - Líneas 44-65

Esta modificación tiene **impact alto** pero es relativamente simple.

### Testing de Cambios
```bash
# 1. Verifica que composer install funciona
composer install --no-dev

# 2. Ejecuta tests
php artisan test --no-migration

# 3. Verifica endpoints clave
curl -X GET http://localhost:8000/api/login

# 4. Chequea logs de errores
tail -f storage/logs/laravel.log | grep -i error
```

### Rollback Strategy
```bash
git checkout composer.json composer.lock
composer install
```

### Estimación de Tiempo
- **Ejecución:** 30 minutos
- **Testing:** 30 minutos
- **Documentation:** 15 minutos
- **Total:** 1-1.5 horas

---

## 1.2 ✅ COMPLETADO - Implementar CORS y Security Headers
**Completado:** 7 de febrero de 2026

### Ubicación de Cambios
- **Archivos a crear:** `config/cors.php`, `app/Http/Middleware/SecurityHeaders.php`
- **Archivos a modificar:** `app/Http/Kernel.php`, `config/app.php`

### Problema
Sin CORS explícita, tienes dos riesgos:
1. Si alguien intenta abrirla sin pensar: vulnerable a cualquier origin
2. Sin security headers: vulnerable a clickjacking, XSS, etc.

### Solución Paso a Paso

#### PASO 1: Instalar paquete de CORS
```bash
composer require fruitcake/laravel-cors
```

#### PASO 2: Crear config/cors.php
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your Laravel application.
    | This will control how external origins can access your API.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,https://app.tudominio.com'))),

    'allowed_origins_patterns' => [
        '/https?:\/\/(app|admin)\.(dev|staging|prod)\.tudominio\.com/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Total-Count',
        'X-Page-Count',
        'X-Current-Page',
        'X-Per-Page',
        'X-Trace-ID',
        'X-Request-ID',
    ],

    'max_age' => 86400,  // 24 hours

    'supports_credentials' => true,

    'supports_preflight_cache' => true,
];
```

#### PASO 3: Crear Middleware de Security Headers

**Archivo:** `app/Http/Middleware/SecurityHeaders.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');

        // Enable XSS protection in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // HTTPS Strict Transport Security
        if (app()->environment('production')) {
            $response->header(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        $response->header(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self'; " .
            "connect-src 'self' https:; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self';"
        );

        // Referrer Policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature Policy)
        $response->header(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), usb=(), payment=(), accelerometer=(), gyroscope=()'
        );

        return $response;
    }
}
```

#### PASO 4: Registrar Middleware en Kernel

**Archivo:** `app/Http/Kernel.php`

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,  // ← AGREGAR AQUÍ
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\SecurityHeaders::class,  // ← AGREGAR AQUÍ
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // Sanctum handles auth
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    // ... resto del archivo
}
```

#### PASO 5: Configurar variables de entorno

**.env**
```bash
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://app.tudominio.com,https://admin.tudominio.com
APP_TRUSTED_HOSTS=.tudominio.com
```

### Testing de Cambios

```bash
# Test 1: Verificar CORS headers
curl -i -X OPTIONS http://localhost:8000/api/productos \
    -H "Origin: https://app.tudominio.com" \
    -H "Access-Control-Request-Method: GET"

# Verificar respuesta contiene:
# Access-Control-Allow-Origin: https://app.tudominio.com
# Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE

# Test 2: Verificar Security Headers
curl -i http://localhost:8000/api/productos | grep -E "X-Content-Type|X-Frame|Strict-Transport"

# Esperado:
# X-Content-Type-Options: nosniff
# X-Frame-Options: DENY
# Strict-Transport-Security: max-age=31536000...
```

### Estimación de Tiempo
- **Configuración:** 45 minutos
- **Testing:** 30 minutos
- **Documentation:** 20 minutos
- **Total:** 1.5-2 horas

---

## 1.3 ✅ COMPLETADO - Logging Estructurado
**Completado:** 7 de febrero de 2026

### Ubicación de Cambios
- **Archivos:** `config/logging.php`, `app/Http/Middleware/LogRequest.php`, `app/Traits/HasSafeErrorHandling.php`

### Problema
Sin logs estructurados, no puedes auditar acciones de usuarios, debuggear en producción, o cumplir normativas de auditoría.

### Solución Paso a Paso

#### PASO 1: Configurar Monolog Structured

**Archivo:** `config/logging.php` - Agregar channels

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'security'],
        'ignore_exceptions' => false,
    ],

    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],

    'security' => [
        'driver' => 'monolog',
        'level' => env('LOG_LEVEL', 'debug'),
        'handler' => \Monolog\Handler\RotatingFileHandler::class,
        'with' => [
            'filename' => storage_path('logs/security.log'),
            'maxFiles' => 30,
        ],
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'processors' => [
            \Monolog\Processor\UidProcessor::class,
            \Monolog\Processor\InvocationProcessor::class,
        ],
    ],

    'audit' => [
        'driver' => 'monolog',
        'level' => 'info',
        'handler' => \Monolog\Handler\RotatingFileHandler::class,
        'with' => [
            'filename' => storage_path('logs/audit.log'),
            'maxFiles' => 90,
        ],
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'processors' => [
            \Monolog\Processor\UidProcessor::class,
        ],
    ],

    'performance' => [
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => \Monolog\Handler\RotatingFileHandler::class,
        'with' => [
            'filename' => storage_path('logs/performance.log'),
            'maxFiles' => 30,
        ],
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
],
```

#### PASO 2: Crear Middleware de Logging

**Archivo:** `app/Http/Middleware/LogRequest.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generar trace ID único para correlacionar logs
        $traceId = $request->header('X-Trace-ID', (string) Str::uuid());
        $startTime = microtime(true);

        // Log entrada de request
        Log::channel('security')->info('request.started', [
            'trace_id' => $traceId,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'query_string' => $request->getQueryString(),
            'timestamp' => now()->iso8601Micro(),
            'request_id' => request()->id(),
        ]);

        try {
            $response = $next($request);

            // Calcular duración
            $duration = (microtime(true) - $startTime) * 1000; // ms

            // Log salida de request
            Log::channel('security')->info('request.completed', [
                'trace_id' => $traceId,
                'user_id' => auth()->id(),
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->status(),
                'duration_ms' => round($duration, 2),
                'timestamp' => now()->iso8601Micro(),
            ]);

            // Log de performance si es lenta
            if ($duration > 1000) {
                Log::channel('performance')->warning('slow_request', [
                    'trace_id' => $traceId,
                    'path' => $request->path(),
                    'duration_ms' => round($duration, 2),
                    'user_id' => auth()->id(),
                ]);
            }

            return $response->header('X-Trace-ID', $traceId);

        } catch (\Throwable $exception) {
            // Log errores
            Log::channel('security')->error('request.error', [
                'trace_id' => $traceId,
                'user_id' => auth()->id(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception;
        }
    }
}
```

#### PASO 3: Registrar Middleware

**Archivo:** `app/Http/Kernel.php`

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\LogRequest::class,  // ← AGREGAR
    ],
];
```

#### PASO 4: Refactorizar HasSafeErrorHandling

**Archivo:** `app/Traits/HasSafeErrorHandling.php`

```php
<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait HasSafeErrorHandling
 * 
 * Proporciona manejo seguro de errores para controladores API.
 */
trait HasSafeErrorHandling
{
    /**
     * Genera una respuesta de error segura.
     */
    protected function safeErrorResponse(
        \Throwable $exception,
        string $defaultMessage = 'Error interno del servidor',
        int $statusCode = 500,
        array $context = []
    ): JsonResponse {
        
        $traceId = request()->header('X-Trace-ID', (string) Str::uuid());

        // Log detallado del error
        Log::channel('security')->error($defaultMessage, array_merge([
            'trace_id' => $traceId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'path' => request()->path(),
        ], $context));

        // En producción, ocultar detalles
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => $defaultMessage,
                'trace_id' => $traceId,  // Útil para soporte técnico
            ], $statusCode);
        }

        // En desarrollo, mostrar detalles
        return response()->json([
            'success' => false,
            'message' => $defaultMessage,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace_id' => $traceId,
        ], $statusCode);
    }
}
```

### Testing de Cambios

```bash
# Test 1: Genera un request y verifica logs
curl -X GET http://localhost:8000/api/productos

# Verifica que se creó logs/security.log
tail storage/logs/security.log | jq '.'

# Esperado:
# {
#   "message": "request.started",
#   "trace_id": "uuid...",
#   "user_id": 1,
#   "method": "GET",
#   "path": "api/productos",
#   ...
# }

# Test 2: Genera un error y verifica logging
curl -X GET http://localhost:8000/api/invalid

# Verifica error en logs
grep -A 5 "request.error" storage/logs/security.log
```

### Estimación de Tiempo
- **Configuración:** 45 minutos
- **Código:** 45 minutos
- **Testing:** 30 minutos
- **Total:** 2-2.5 horas

---

## 1.4 🔴 CRÍTICO: Sentry para Error Tracking

### Ubicación de Cambios
- **Archivos:** `config/sentry.php` (auto-generado), `.env`

### Problema
Sin error tracking, no sabes qué se rompe en producción hasta que lo reporta un cliente.

### Solución Paso a Paso

#### PASO 1: Instalar Sentry
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish
```

#### PASO 2: Configurar .env
```bash
SENTRY_LARAVEL_DSN=https://your-key@sentry.io/your-project-id
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_RELEASE=$(git rev-parse --short HEAD)
```

#### PASO 3: Personalizar config/sentry.php
```php
<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('SENTRY_ENVIRONMENT', 'production'),

    'release' => env('SENTRY_RELEASE'),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'queue' => true,
    ],

    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // No enviar errores de validación (ruido)
        if ($hint && $hint->exception instanceof \Illuminate\Validation\ValidationException) {
            return null;
        }

        // No enviar errores 404
        if ($hint && $hint->exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return null;
        }

        // Filtrar datos sensibles
        if ($event->getRequest()) {
            $request = $event->getRequest();
            unset($request['cookies']);
            unset($request['headers']['Authorization']);
        }

        return $event;
    },

    'integrations' => [
        \Sentry\Laravel\Integration\ExceptionContextIntegration::class,
    ],

    'send_default_pii' => false,
];
```

### Testing de Cambios

```bash
# Test 1: Trigger manual de evento
php artisan tinker

> \Sentry\captureException(new \Exception('Test exception'));
> // Verifica en dashboard de Sentry

# Test 2: Limpia lo anterior
php artisan tinker
> cache()->forget('sentry_test');
```

### Estimación de Tiempo
- **Instalación:** 20 minutos
- **Configuración:** 30 minutos
- **Testing:** 30 minutos
- **Total:** 1-1.5 horas

---

## 1.5 🔴 CRÍTICO: Rate Limiting Granular

### Ubicación de Cambios
- **Archivo:** `routes/api.php`
- **Nuevos Archivos:** `app/Http/Middleware/LoginThrottle.php`

### Problema
Rate limiting global muy débil (120 req/min = 2000 queries/hora).

### Solución Paso a Paso

#### PASO 1: Crear Middleware de Login Adaptativo

**Archivo:** `app/Http/Middleware/LoginThrottle.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginThrottle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'login_attempts_' . $request->ip();
        $attempts = Cache::get($key, 0);

        // Después de 10 intentos, aplicar exponential backoff
        if ($attempts > 10) {
            // Minutos de lock = 2^(attempts-10), máx 60 minutos
            $lockoutMinutes = min(2 ** ($attempts - 10), 60);

            Log::channel('security')->warning('login_locked', [
                'ip' => $request->ip(),
                'attempts' => $attempts,
                'lockout_minutes' => $lockoutMinutes,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Cuenta temporalmente bloqueada. Intenta en {$lockoutMinutes} minutos.",
                'retry_after' => $lockoutMinutes * 60,
            ], 429);
        }

        return $next($request);
    }
}
```

#### PASO 2: Crear Middleware para Incrementar Intentos Fallidos

**Archivo:** `app/Http/Middleware/RecordLoginAttempt.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RecordLoginAttempt
{
    /**
     * Handle an incoming request y registra intentos fallidos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ejecuta la request
        $response = $next($request);

        // Si login falló (422), incrementa intentos
        if ($response->status() === 422) {
            $key = 'login_attempts_' . $request->ip();
            $attempts = Cache::get($key, 0);
            
            Cache::put($key, $attempts + 1, now()->addHours(1));
        }

        // Si login exitoso (200), resetea intentos
        if ($response->status() === 200) {
            $key = 'login_attempts_' . $request->ip();
            Cache::forget($key);
        }

        return $response;
    }
}
```

#### PASO 3: Actualizar routes/api.php

**Archivo:** `routes/api.php` - Líneas 74-76

```php
// Login con throttling adaptativo
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['throttle:5,1', 'loginThrottle', 'recordLoginAttempt']);

// Endpoints autenticados con rate limiting granular
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Listar datos (cacheable, menos costoso)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/productos', [ProductoController::class, 'index']);
        Route::get('/clientes', [ClienteController::class, 'index']);
        Route::get('/proveedores', [ProveedorController::class, 'index']);
        Route::get('/empresas', [EmpresaController::class, 'index']);
        Route::get('/sucursales', [SucursalController::class, 'index']);
    });
    
    // Búsqueda compleja (más costosa)
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ordenes-compra', [OrdenCompraController::class, 'index']);
        Route::get('/almacenes', [AlmacenController::class, 'index']);
    });
    
    // Operaciones de lectura detallada
    Route::middleware('throttle:40,1')->group(function () {
        Route::get('/productos/{id}', [ProductoController::class, 'show']);
        Route::get('/ventas/{id}', [VentaController::class, 'show']);
    });
    
    // Operaciones de escritura (CREATE)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::post('/clientes', [ClienteController::class, 'store']);
        Route::post('/ventas', [VentaController::class, 'store']);
        Route::post('/ordenes-compra', [OrdenCompraController::class, 'store']);
    });
    
    // Operaciones de actualización (UPDATE)
    Route::middleware('throttle:15,1')->group(function () {
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::patch('/productos/{id}', [ProductoController::class, 'update']);
        Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    });
    
    // Operaciones de eliminación (DELETE)
    Route::middleware('throttle:5,1')->group(function () {
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
        Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);
        Route::delete('/ventas/{id}', [VentaController::class, 'destroy']);
    });
    
    // Operaciones pesadas (import, export, facturación)
    Route::middleware('throttle:2,1')->group(function () {
        Route::post('/productos/import', [ProductoController::class, 'import']);
        Route::post('/ventas/export', [VentaController::class, 'export']);
        Route::post('/ventas/{id}/factura', [VentaController::class, 'generateInvoice']);
    });
    
    // Health check (sin límite)
    Route::get('/health', fn() => response()->json(['status' => 'ok']));
});
```

#### PASO 4: Registrar Middleware en Kernel

**Archivo:** `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ... existing
    'loginThrottle' => \App\Http\Middleware\LoginThrottle::class,
    'recordLoginAttempt' => \App\Http\Middleware\RecordLoginAttempt::class,
];
```

### Testing de Cambios

```bash
# Test 1: Normal rate limiting
for i in {1..65}; do
  curl -s http://localhost:8000/api/productos -H "Authorization: Bearer $TOKEN" > /dev/null
  sleep 0.01
done

# Verifica que la request 61 sea 429

# Test 2: Login throttling
for i in {1..12}; do
  curl -X POST http://localhost:8000/api/login \
    -d '{"email":"test@test.com","password":"wrong"}'
  sleep 0.1
done

# Verifica que requests 11+ sean 429
```

### Estimación de Tiempo
- **Código:** 1.5 horas
- **Testing:** 1 hora
- **Total:** 2.5-3 horas

---

## 1.6 🔴 CRÍTICO: Encriptación de Datos Sensibles

### Ubicación de Cambios
- **Archivos de Modelos:** `app/Models/FeCertificadoDigital.php`, `app/Models/FeOAuthToken.php`, etc.
- **Nuevos Archivos:** `database/migrations/yyyy_mm_dd_encrypt_sensitive_fields.php`

### Problema
Certificados digitales, claves privadas, tokens hacienda = todo en plain text en BD.
Violación GDPR, LPD CR, y normativas SII.

### Solución Paso a Paso

#### PASO 1: Identificar Campos Sensibles

Búsqueda en la codebase:
```bash
grep -r "certificado\|clave_privada\|token\|password" app/Models/*.php | grep -v "password_hash"

# Campos sensibles identificados:
# - fe_certificados_digitales.datos_certificado
# - fe_certificados_digitales.clave_privada
# - fe_oauth_tokens.access_token
# - fe_oauth_tokens.refresh_token
# - usuarios.password_hash (ya está con hash)
```

#### PASO 2: Crear Migration Preparatoria

**Archivo:** `database/migrations/2026_02_07_encrypt_sensitive_fields.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No modificar estructura aún, solo datos
        // Las columnas ya existen como string/text
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir, es un proceso de migración de datos
    }
};
```

#### PASO 3: Actualizar Modelos

**Archivo:** `app/Models/FeCertificadoDigital.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeCertificadoDigital extends Model
{
    protected $table = 'fe_certificados_digitales';

    protected $fillable = [
        'empresa_id',
        'datos_certificado',
        'clave_privada',
        'contraseña_certificado',
        'fecha_expiracion',
        'activo',
    ];

    /**
     * Especificar qué campos deben estar encriptados.
     * Laravel automáticamente encriptará/desencriptará al guardar/recuperar.
     */
    protected $casts = [
        'datos_certificado' => 'encrypted',
        'clave_privada' => 'encrypted',
        'contraseña_certificado' => 'encrypted',
        'fecha_expiracion' => 'datetime',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
```

**Archivo:** `app/Models/FeOAuthToken.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeOAuthToken extends Model
{
    protected $table = 'fe_oauth_tokens';

    protected $fillable = [
        'empresa_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_in' => 'integer',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
```

#### PASO 4: Migrar Datos Existentes

**Archivo:** `database/seeders/EncryptSensitiveDataSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\FeCertificadoDigital;
use App\Models\FeOAuthToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class EncryptSensitiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Encripta todos los datos sensibles existentes en la BD.
     * SOLO ejecutar UNA VEZ en cada ambiente.
     */
    public function run(): void
    {
        Log::info('Starting encryption of sensitive data...');

        // Encriptar certificados
        FeCertificadoDigital::chunk(100, function ($certificados) {
            foreach ($certificados as $cert) {
                // Laravel automáticamente encriptará al guardar
                $cert->touch(); // Fuerza actualización sin cambiar datos
            }
            Log::info('Encrypted ' . count($certificados) . ' certificates');
        });

        // Encriptar tokens OAuth
        FeOAuthToken::chunk(100, function ($tokens) {
            foreach ($tokens as $token) {
                // Laravel automáticamente encriptará al guardar
                $token->touch(); // Fuerza actualización
            }
            Log::info('Encrypted ' . count($tokens) . ' OAuth tokens');
        });

        Log::info('Encryption of sensitive data completed');
    }
}
```

#### PASO 5: Ejecutar Migración

```bash
# Crear backup ANTES de proceder
mysqldump -u root -p api_db > backups/db_before_encryption_$(date +%Y%m%d_%H%M%S).sql

# Ejecutar seeder
php artisan db:seed --class=EncryptSensitiveDataSeeder

# Verificar que datos están encriptados
php artisan tinker

> use App\Models\FeCertificadoDigital;
> $cert = FeCertificadoDigital::first();
> // Si está encriptado, mostrará el valor desencriptado automáticamente
> $cert->datos_certificado
```

### Testing de Cambios

```bash
# Test 1: Verificar encriptación en BD
mysql -u root -p api_db

SELECT id, LEFT(datos_certificado, 50) as encrypted_data FROM fe_certificados_digitales LIMIT 1;
# Debe mostrar data encriptada (no readable)

# Test 2: Verificar desencriptación en aplicación
php artisan tinker

> $cert = \App\Models\FeCertificadoDigital::first();
> $cert->datos_certificado  // Debe mostrar desencriptado
> $cert->update(['datos_certificado' => 'nuevo_certificado']);
> // Debe guardarse encriptado

# Test 3: Ejecutar tests
php artisan test tests/Unit/EncryptionTest.php
```

### Estimación de Tiempo
- **Análisis:** 30 minutos
- **Código & Migrations:** 1 hora
- **Data Migration:** 1 hora (incl. backup/restore si falla)
- **Testing:** 1 hora
- **Total:** 3.5-4 horas

---

## 1.7 🔴 CRÍTICO: Sistema de Auditoría Completo

### Ubicación de Cambios
- **Archivos:** `app/Models/AuditoriaActividad.php` (ya existe, mejorar)
- **Nuevos Archivos:** Observers para modelos críticos
- **Archivos a Modificar:** `app/Providers/AppServiceProvider.php`

### Problema
Tienes modelo `AuditoriaActividad` pero es como un coche sin gasolina.
No está hooked en eventos, no registra cambios.

### Solución Paso a Paso

#### PASO 1: Mejorar Modelo AuditoriaActividad

**Archivo:** `app/Models/AuditoriaActividad.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaActividad extends Model
{
    protected $table = 'auditoria_actividades';

    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad',
        'registro_id',
        'cambios',
        'valores_anteriores',
        'ip',
        'user_agent',
        'cuenta',
        'timestamp',
    ];

    protected $casts = [
        'cambios' => 'json',
        'valores_anteriores' => 'json',
        'creado_en' => 'datetime',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;  // No actualizamos audits

    /**
     * Relación con usuario que ejecutó la acción.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para filtrar por entidad.
     */
    public function scopeForEntity($query, string $entity)
    {
        return $query->where('entidad', $entity);
    }

    /**
     * Scope para filtrar por usuario.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('usuario_id', $userId);
    }

    /**
     * Scope para filtrar por acción.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('accion', $action);
    }

    /**
     * Scope para rango de fechas.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('creado_en', [$startDate, $endDate]);
    }
}
```

#### PASO 2: Crear Observers para Modelos Críticos

**Archivo:** `app/Observers/VentaObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Venta;
use App\Models\AuditoriaActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class VentaObserver
{
    /**
     * Handle the Venta "created" event.
     */
    public function created(Venta $venta): void
    {
        AuditoriaActividad::create([
            'usuario_id' => Auth::id(),
            'accion' => 'crear',
            'entidad' => 'Venta',
            'registro_id' => $venta->id,
            'cambios' => json_encode($venta->getAttributes()),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'cuenta' => Auth::user()?->email,
            'creado_en' => now(),
        ]);
    }

    /**
     * Handle the Venta "updated" event.
     */
    public function updated(Venta $venta): void
    {
        $dirty = $venta->getDirty();
        
        // No loguear si solo cambió timestamps
        if (count($dirty) === 1 && isset($dirty['actualizado_en'])) {
            return;
        }

        AuditoriaActividad::create([
            'usuario_id' => Auth::id(),
            'accion' => 'actualizar',
            'entidad' => 'Venta',
            'registro_id' => $venta->id,
            'cambios' => json_encode($dirty),
            'valores_anteriores' => json_encode($venta->getOriginal()),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'cuenta' => Auth::user()?->email,
            'creado_en' => now(),
        ]);
    }

    /**
     * Handle the Venta "deleted" event.
     */
    public function deleted(Venta $venta): void
    {
        AuditoriaActividad::create([
            'usuario_id' => Auth::id(),
            'accion' => 'eliminar',
            'entidad' => 'Venta',
            'registro_id' => $venta->id,
            'cambios' => json_encode(['deleted' => true]),
            'valores_anteriores' => json_encode($venta->getAttributes()),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'cuenta' => Auth::user()?->email,
            'creado_en' => now(),
        ]);
    }

    /**
     * Handle the Venta "restored" event.
     */
    public function restored(Venta $venta): void
    {
        AuditoriaActividad::create([
            'usuario_id' => Auth::id(),
            'accion' => 'restaurar',
            'entidad' => 'Venta',
            'registro_id' => $venta->id,
            'cambios' => json_encode(['restored' => true]),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'cuenta' => Auth::user()?->email,
            'creado_en' => now(),
        ]);
    }
}
```

Repetir para modelos críticos:
- `ClienteObserver.php`
- `ProveedorObserver.php`
- `ProductoObserver.php`
- `ComprobanteElectronicoFeObserver.php`
- `AsientoContableObserver.php`

#### PASO 3: Registrar Observers

**Archivo:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\ComprobanteElectronicoFe;
use App\Models\AsientoContable;
use App\Observers\VentaObserver;
use App\Observers\ClienteObserver;
use App\Observers\ProveedorObserver;
use App\Observers\ProductoObserver;
use App\Observers\ComprobanteObserver;
use App\Observers\AsientoContableObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers para auditoría
        Venta::observe(VentaObserver::class);
        Cliente::observe(ClienteObserver::class);
        Proveedor::observe(ProveedorObserver::class);
        Producto::observe(ProductoObserver::class);
        ComprobanteElectronicoFe::observe(ComprobanteObserver::class);
        AsientoContable::observe(AsientoContableObserver::class);
    }
}
```

#### PASO 4: Crear Endpoint para Visualizar Auditoría

**Archivo:** `app/Http/Controllers/API/AuditoriaActividadController.php`

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditoriaActividad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditoriaActividadController extends Controller
{
    /**
     * Obtener auditoría de un registro específico.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditoriaActividad::query();

        // Filtrar por entidad
        if ($request->has('entidad')) {
            $query->forEntity($request->entidad);
        }

        // Filtrar por registro
        if ($request->has('registro_id')) {
            $query->where('registro_id', $request->registro_id);
        }

        // Filtrar por usuario
        if ($request->has('usuario_id')) {
            $query->byUser($request->usuario_id);
        }

        // Filtrar por acción
        if ($request->has('accion')) {
            $query->byAction($request->accion);
        }

        // Rango de fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->dateRange(
                $request->fecha_inicio,
                $request->fecha_fin
            );
        }

        return response()->json(
            $query->latest('creado_en')
                ->with('usuario')
                ->paginate(50)
        );
    }

    /**
     * Obtener historial completo de un registro.
     */
    public function show(string $entidad, int $registroId): JsonResponse
    {
        $audits = AuditoriaActividad::forEntity($entidad)
            ->where('registro_id', $registroId)
            ->latest('creado_en')
            ->with('usuario')
            ->get();

        return response()->json(['data' => $audits]);
    }
}
```

Añadir rutas en `routes/api.php`:
```php
Route::middleware(['auth:sanctum', 'permission:ver-auditoria'])->group(function () {
    Route::get('/auditoria', [AuditoriaActividadController::class, 'index']);
    Route::get('/auditoria/{entidad}/{registroId}', [AuditoriaActividadController::class, 'show']);
});
```

### Testing de Cambios

```bash
# Test 1: Crear venta y verificar auditoría
php artisan tinker

> $venta = \App\Models\Venta::create([...]);
> \App\Models\AuditoriaActividad::where('registro_id', $venta->id)->first();
// Debe mostrar el registro de auditoría

# Test 2: Actualizar y verificar
> $venta->update(['estado' => 'confirmado']);
> \App\Models\AuditoriaActividad::where('accion', 'actualizar')->latest()->first();
// Debe mostrar los cambios

# Test 3: Consultar API
curl -X GET http://localhost:8000/api/auditoria?entidad=Venta&registro_id=1 \
  -H "Authorization: Bearer $TOKEN"
// Debe retornar todos los cambios del registro
```

### Estimación de Tiempo
- **Mejora de modelo:** 30 minutos
- **Crear observers (6x):** 2 horas
- **Registrar observers:** 20 minutos
- **Endpoint & Testing:** 1 hora
- **Total:** 4-4.5 horas

---

## SUBRESUMEN FASE 1

| Tarea | Horas | Estado |
|-------|-------|--------|
| 1.1 Fijar dependencias | 1-1.5 | 🔴 |
| 1.2 CORS + Security Headers | 1.5-2 | 🔴 |
| 1.3 Logging Estructurado | 2-2.5 | 🔴 |
| 1.4 Sentry | 1-1.5 | 🔴 |
| 1.5 Rate Limiting | 2.5-3 | 🔴 |
| 1.6 Encriptación | 3.5-4 | 🔴 |
| 1.7 Auditoría | 4-4.5 | 🔴 |
| **TOTAL FASE 1** | **15.5-19** | 🔴 |

**Timeline:** 1-1.5 semanas para 1 dev, 4-5 días para 2 devs en paralelo

---

---

# FASE 2: OBSERVABILIDAD
## Semanas 2-3 | 35-45 horas

### ✅ ENTREGABLES

- [ ] Métricas Prometheus configuradas
- [ ] Health check endpoints activos
- [ ] Distributed tracing implementado
- [ ] Dashboard de monitoreo (Grafana)
- [ ] Alerting configurado
- [ ] APM custom para endpoints

---

## 2.1 Health Check Endpoints

**Archivo:** `app/Http/Controllers/HealthCheckController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Liveness probe - Está el servicio vivo?
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->iso8601Micro(),
            'version' => config('app.version'),
        ]);
    }

    /**
     * Readiness probe - Está listo para recibir traffic?
     */
    public function readiness(): JsonResponse
    {
        try {
            // Chequear BD
            DB::connection()->getPdo();

            // Chequear Cache
            Cache::get('health_check') ? : Cache::put('health_check', true, 1);

            // Chequear Storage
            if (!is_writable(storage_path('logs'))) {
                throw new \Exception('Storage not writable');
            }

            return response()->json([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                    'storage' => 'ok',
                ],
                'timestamp' => now()->iso8601Micro(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'error' => $e->getMessage(),
                'timestamp' => now()->iso8601Micro(),
            ], 503);
        }
    }

    /**
     * Detailed health info (requiere auth)
     */
    public function details(Request $request): JsonResponse
    {
        $this->authorize('view admin');

        return response()->json([
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'app_version' => config('app.version'),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'cache_config' => config('cache.default'),
            'database' => [
                'connection' => config('database.default'),
                'connected' => DB::connection()->getDatabaseName(),
            ],
            'redis' => [
                'connected' => Cache::connection('redis')->ping() === 'PONG',
            ],
            'uptime' => $this->getServerUptime(),
            'memory' => [
                'used_mb' => memory_get_usage(true) / 1024 / 1024,
                'limit_mb' => ini_get('memory_limit'),
            ],
        ]);
    }

    /**
     * Get server uptime.
     */
    private function getServerUptime(): string
    {
        $uptime = shell_exec('uptime -p');
        return trim($uptime ?? 'unknown');
    }
}
```

Registrar rutas en `routes/api.php`:
```php
Route::prefix('health')->group(function () {
    Route::get('/live', [HealthCheckController::class, 'liveness']);
    Route::get('/ready', [HealthCheckController::class, 'readiness']);
    Route::get('/details', [HealthCheckController::class, 'details'])->middleware('auth:sanctum');
});
```

---

## 2.2 Prometheus Metrics

**Instalar Prometheus exporter:**
```bash
composer require promphp/prometheus_client_php
```

**Crear metrics endpoint:**

**Archivo:** `app/Http/Controllers/MetricsController.php`

```php
<?php

namespace App\Http\Controllers;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function metrics()
    {
        $redis = new \Redis();
        $redis->connect('localhost', 6379);
        
        $registry = new CollectorRegistry(new Redis($redis));

        // Récupérer toutes les métriques de Prometheus
        $renderer = new RenderTextFormat();
        
        return response($renderer->render($registry->collect()), 200, [
            'Content-Type' => 'text/plain; version=0.0.4',
        ]);
    }
}
```

Registrar ruta:
```php
Route::get('/metrics', [\App\Http\Controllers\MetricsController::class, 'metrics']);
```

---

## 2.3 Request Metrics Middleware

**Archivo:** `app/Http/Middleware/RecordMetrics.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class RecordMetrics
{
    protected CollectorRegistry $registry;

    public function __construct()
    {
        $redis = new \Redis();
        $redis->connect('localhost', 6379);
        $this->registry = new CollectorRegistry(new Redis($redis));
    }

    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000; // ms

        // Recordar métrica de latencia
        $histogram = $this->registry->getOrRegisterHistogram(
            'http_request_duration_ms',
            'HTTP request duration in milliseconds',
            ['endpoint', 'method', 'status']
        );

        $histogram->observe($duration, [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status' => $response->status(),
        ]);

        // Recordar métrica de count
        $counter = $this->registry->getOrRegisterCounter(
            'http_requests_total',
            'Total HTTP requests',
            ['endpoint', 'method', 'status']
        );

        $counter->inc([
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status' => $response->status(),
        ]);

        return $response;
    }
}
```

---

## Estimación FASE 2
- **Health Checks:** 1.5-2h
- **Prometheus:** 2-2.5h
- **Metrics Middleware:** 1.5-2h
- **Dashboard Grafana:** 2-3h
- **Alerting:** 2-3h
- **Total:** 9-12.5h

---

---

# FASE 3: AUDITORÍA Y COMPLIANCE
## Semanas 3-4 | 30-40 horas

(Detallado en documento anterior)

### Entregables:
- [ ] Audit trail completo (ya hecho en Fase 1.7)
- [ ] Data retention policies
- [ ] GDPR deletion requests endpoint
- [ ] Compliance dashboard
- [ ] STRIDE threat modeling documentation

---

# FASE 4: CALIDAD DE CÓDIGO
## Semanas 4-5 | 45-55 horas

### Entregables:
- [ ] PHPStan baseline < 30 errores
- [ ] Controllers refactorizados (ninguno > 400 líneas)
- [ ] DTO layer explícita
- [ ] Test coverage > 80%
- [ ] All SonarQube issues resolved

---

# FASE 5: RENDIMIENTO
## Semanas 5-6 | 50-60 horas

### Entregables:
- [ ] Gzip compression activo
- [ ] N+1 queries resueltas
- [ ] Database índices optimizados
- [ ] Cache warming strategy
- [ ] Query optimization < 200ms avg

---

# FASE 6: ARQUITECTURA AVANZADA
## Semanas 6-7 | 40-50 horas

### Entregables:
- [ ] API versionado (v1, v2)
- [ ] Repository pattern implementado
- [ ] Webhook system
- [ ] API changelog "automático
- [ ] GraphQL optional endpoint

---

# TIMELINE GANTT

```
SEMANA:     1    2    3    4    5    6    7
FASE1:      █████
FASE2:           ████████
FASE3:                ████████
FASE4:                     ████████
FASE5:                          ████████
FASE6:                               ████████
```

**Con 1 dev:** 12 semanas (3 meses)
**Con 2 devs en paralelo:** 6-7 semanas (1.5 meses)

---

# TESTING & VALIDATION

## Test Plan for Each Phase

### FASE 1 Testing
```bash
# Security tests
php artisan test tests/Security/
php vendor/bin/phpstan analyse app/

# Manual tests
./scripts/test-cors.sh
./scripts/test-rate-limiting.sh
./scripts/test-logging.sh
```

### FASE 2 Testing
```bash
# Health check tests
curl http://localhost:8000/health/live
curl http://localhost:8000/health/ready

# Metrics tests
curl http://localhost:8000/metrics | grep http_requests_total
```

---

# ROLLBACK STRATEGY

## Por Fase

### FASE 1 Rollback
```bash
# Si something breaks:
git checkout development-backup-branch
composer install
php artisan migrate:rollback
php artisan cache:clear
```

### FASE 2-6 Rollback
Similar al anterior, pero más granular por microchanges.

---

# RISK MITIGATION

| Risk | Probability | Mitigation |
|------|-------------|-----------|
| Data loss during encryption | Low | Backup antes de cada paso |
| Performance degradation | Medium | Test con load. |
| Compatibility breaking | Low | Comprehensive testing |

---

**Documento Completado:** 7 de Febrero de 2026  
**Total Horas Estimadas:** 240-310 hours  
**Investment:** $25k-35k USD  
**ROI:** Producción escalable, compliant, observable
