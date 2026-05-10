# Guía de Configuración de Sentry

## Introducción

Sentry es una plataforma de monitoreo de errores que ayuda a rastrear, priorizar y corregir errores en producción en tiempo real.

## Instalación

El paquete de Sentry ya está incluido en `composer.json`:

```bash
# Instalar dependencias (si no están instaladas)
composer install
```

## Configuración

### 1. Crear Cuenta en Sentry

1. Ir a [https://sentry.io](https://sentry.io)
2. Crear una cuenta gratuita
3. Crear un nuevo proyecto de tipo "Laravel"
4. Copiar el **DSN** que aparece en la configuración

### 2. Configurar Variables de Entorno

Agregar las siguientes variables en el archivo `.env`:

```dotenv
# Sentry Error Tracking
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/your-project-id
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=v1.0.0
SENTRY_SAMPLE_RATE=1.0
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false
SENTRY_BREADCRUMBS_SQL=true
SENTRY_BREADCRUMBS_SQL_BINDINGS=false
SENTRY_TRACING_ENABLED=true
```

### 3. Configuración de Variables

| Variable | Descripción | Valor Recomendado |
|----------|-------------|-------------------|
| `SENTRY_LARAVEL_DSN` | URL de tu proyecto Sentry | Obtener de Sentry.io |
| `SENTRY_ENVIRONMENT` | Ambiente actual | `production`, `staging`, `development` |
| `SENTRY_RELEASE` | Versión del release | `v1.0.0` (usar versionado semántico) |
| `SENTRY_SAMPLE_RATE` | % de errores a reportar | `1.0` (100%) en producción |
| `SENTRY_TRACES_SAMPLE_RATE` | % de transacciones a monitorear | `0.2` (20%) en producción |
| `SENTRY_PROFILES_SAMPLE_RATE` | % de profiles a capturar | `0.1` (10%) en producción |
| `SENTRY_SEND_DEFAULT_PII` | Enviar info personal | `false` (por privacidad) |
| `SENTRY_BREADCRUMBS_SQL` | Capturar queries SQL | `true` |
| `SENTRY_BREADCRUMBS_SQL_BINDINGS` | Capturar parámetros SQL | `false` (puede exponer datos sensibles) |
| `SENTRY_TRACING_ENABLED` | Habilitar performance monitoring | `true` en producción |

### 4. Publicar Configuración (Opcional)

Si necesitas personalizar más la configuración:

```bash
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

Esto creará `config/sentry.php` donde puedes hacer ajustes adicionales.

## Uso

### Reportar Errores Manualmente

```php
use Sentry\Laravel\Facade as Sentry;

// Capturar excepción
try {
    // Código que puede fallar
} catch (\Exception $e) {
    Sentry::captureException($e);
}

// Reportar mensaje simple
Sentry::captureMessage('Algo importante sucedió');

// Agregar contexto
Sentry::configureScope(function ($scope) {
    $scope->setUser([
        'id' => auth()->id(),
        'email' => auth()->user()->email,
    ]);
    
    $scope->setTag('empresa_id', auth()->user()->empresa_id);
    
    $scope->setExtra('additional_data', [
        'key' => 'value'
    ]);
});
```

### Breadcrumbs Personalizados

```php
use Sentry\Laravel\Facade as Sentry;

Sentry::addBreadcrumb([
    'message' => 'Usuario inició proceso de facturación',
    'category' => 'facturacion',
    'level' => 'info',
    'data' => [
        'factura_id' => $factura->id,
    ],
]);
```

### Ignorar Excepciones

En `config/sentry.php`:

```php
'ignore_exceptions' => [
    Illuminate\Auth\AuthenticationException::class,
    Illuminate\Validation\ValidationException::class,
],
```

## Mejores Prácticas

### 1. Usar Releases

Actualizar `SENTRY_RELEASE` cada vez que se hace deploy:

```bash
# En script de deploy
export SENTRY_RELEASE=$(git describe --tags --always)
php artisan config:cache
```

### 2. Separar por Ambiente

Usar diferentes proyectos en Sentry para cada ambiente:

```dotenv
# Producción
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/prod-project-id
SENTRY_ENVIRONMENT=production

# Staging
SENTRY_LARAVEL_DSN=https://yyy@sentry.io/staging-project-id
SENTRY_ENVIRONMENT=staging
```

### 3. Optimizar Performance

Para reducir overhead en producción:

```dotenv
# Reducir sample rates
SENTRY_TRACES_SAMPLE_RATE=0.1  # Solo 10% de transacciones
SENTRY_PROFILES_SAMPLE_RATE=0.05  # Solo 5% de profiles
SENTRY_BREADCRUMBS_SQL_BINDINGS=false  # No capturar parámetros SQL
```

### 4. Proteger Información Sensible

```php
// En config/sentry.php
'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
    // Remover información sensible
    $event->setExtra('sensitive_data', '[REDACTED]');
    
    return $event;
},
```

### 5. Monitorear Performance

Habilitar tracing para endpoints críticos:

```php
use Sentry\Laravel\Facade as Sentry;

$transaction = Sentry::startTransaction([
    'name' => 'Proceso de Facturación',
    'op' => 'facturacion.procesar',
]);

try {
    // Código a monitorear
    $this->procesarFactura();
} finally {
    $transaction->finish();
}
```

## Testing

Para evitar reportar errores durante tests:

```dotenv
# En .env.testing
SENTRY_LARAVEL_DSN=
```

O en `config/sentry.php`:

```php
'dsn' => env('APP_ENV') === 'testing' ? null : env('SENTRY_LARAVEL_DSN'),
```

## Verificación

### Probar Integración

```php
// En routes/web.php o artisan tinker
Route::get('/sentry-test', function () {
    throw new \Exception('Test de Sentry - Esta excepción debería aparecer en Sentry!');
});
```

Visita la ruta y verifica que el error aparezca en tu dashboard de Sentry.

## Troubleshooting

### Los errores no aparecen en Sentry

1. Verificar que `SENTRY_LARAVEL_DSN` está configurado
2. Verificar que `APP_ENV` no es `testing`
3. Revisar logs: `storage/logs/laravel.log`
4. Verificar conectividad a Sentry.io

### Demasiados errores reportados

1. Reducir `SENTRY_SAMPLE_RATE`
2. Agregar excepciones a `ignore_exceptions`
3. Usar filtros en `before_send`

### Performance degradado

1. Reducir `SENTRY_TRACES_SAMPLE_RATE`
2. Reducir `SENTRY_PROFILES_SAMPLE_RATE`
3. Deshabilitar `SENTRY_BREADCRUMBS_SQL` si hay muchas queries

## Recursos

- [Documentación Oficial Sentry Laravel](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Sentry Dashboard](https://sentry.io)
- [Best Practices](https://docs.sentry.io/platforms/php/guides/laravel/best-practices/)

## Soporte

Para problemas con la configuración de Sentry:
- Email: deadmooncr@gmail.com
- Documentación: Ver enlaces arriba
