# 🔐 Guía de Seguridad - Ursol CAST API

> **Última actualización:** 13 de Febrero de 2026  
> **Versión:** 2.1.0  
> **Clasificación:** Documento Interno de Seguridad  
> **Auditor:** Verificación de Código Actual + Mejoras Implementadas

---

## 📋 Tabla de Contenidos

1. [OWASP Top 10 - Implementación](#owasp-top-10---implementación)
2. [Gestión de Dependencias (pnpm)](#gestión-de-dependencias-pnpm)
3. [Autenticación y Autorización](#autenticación-y-autorización)
4. [Protección de Datos](#protección-de-datos)
5. [Configuración Segura](#configuración-segura)
6. [Auditoría y Monitoreo](#auditoría-y-monitoreo)
7. [Respuesta a Incidentes](#respuesta-a-incidentes)

---

## 🔐 MEJORAS IMPLEMENTADAS (FEBRERO 2026)

### FASE 1.5 - Rate Limiting Granular ✅
- **7 Limiters independientes** configurados con diferentes umbrales
- **Servicio centralizado** `RateLimitingService` (253 líneas)
- **Middleware mejorado** `ThrottleRequestsWithRetryAfter` con header 429
- **IP blocking** tras múltiples violaciones  
- **Listas de excepción** inteligentes

**Configuración:**
```
API General:      60 reqs/min
Reportes:         5 generaciones/hora
Importaciones:    2/día
Exportaciones:    5/día
Hacienda:         10 envíos/hora
Login:            5 intentos/15 min
Payment:          3 transacciones/hora
```

### FASE 1.6 - Encriptación AES-256-CBC ✅
- **30+ campos sensibles** encriptados automáticamente
- **Búsqueda hash-based** sin desencriptar datos
- **Soporte rotación de claves** para cumplimiento normativo
- **Control de acceso** por IP y rol
- **Campos protegidos:**
  - Usuario: email, teléfono, identificación
  - Empresa: CIF, banco, código DANE
  - Proveedor: cuenta bancaria
  - Transacción: detalles financieros
- **Audit trail** completo de acceso

### FASE 1.7 - Auditoría Completa ✅
- **AuditLog Model** con 23 columnas
- **CRUD automático** en todos los modelos
- **GDPR/LGPD compliance** - Right-to-be-forgotten implementado
- **Máscaras automáticas** de valores sensibles
- **Retención configurable** (90 días default)
- **Full-text search** en cambios de datos
- **13 Scopes** para análisis y reporting

### FASE 2.1 - Hacienda Integration ✅
- **HaciendaComprobante Model** con 13 scopes
- **HaciendaIntegrationService** (410 líneas, DGT-R-000-2024 v4.4 compliant)
- **Generador de clave** de 29 dígitos (Algoritmo Mod-9)
- **Firma digital XAdES-EPES** implementada
- **8 endpoints REST** para gestión completa
- **Estados:** pending, signed, sent, accepted, rejected, error

---

## 🔴 ÁREAS DE MEJORA (FASE 4 - en progreso)

### A01:2021 - Broken Access Control ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **Policies Laravel:** 80+ políticas de autorización implementadas
- **Multi-tenancy:** Aislamiento completo de datos por empresa
- **Middleware:** `EmpresaMiddleware` para validación de contexto
- **Scopes globales:** `EmpresaScope` aplicado automáticamente a queries

```php
// Ejemplo de protección en controladores
public function show(Factura $factura): JsonResponse
{
    $this->authorize('view', $factura); // Policy check
    // ...
}
```

**Archivos clave:**
- `app/Policies/*` - 80+ archivos de políticas
- `app/Models/Scopes/EmpresaScope.php`
- `app/Http/Middleware/EmpresaMiddleware.php`

---

### A02:2021 - Cryptographic Failures ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **Contraseñas:** Bcrypt con cost factor 12
- **Tokens:** Laravel Sanctum con tokens SHA-256
- **Certificados:** XAdES-EPES para firma digital
- **TLS:** Forzado en producción (HTTPS)
- **Datos sensibles:** Encriptados con APP_KEY

```php
// Configuración de encriptación
'cipher' => 'AES-256-CBC',
'key' => env('APP_KEY'), // 256-bit key
```

**Variables de entorno sensibles:**
```env
APP_KEY=base64:... # Clave de encriptación
DB_PASSWORD=...     # Nunca en código
HACIENDA_P12_PASSWORD=... # Certificado digital
```

---

### A03:2021 - Injection ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **SQL Injection:** Eloquent ORM con bindings
- **XSS:** Blade escaping automático
- **Command Injection:** No uso de shell_exec()
- **LDAP Injection:** No aplica

```php
// ✅ Seguro - Eloquent con bindings
$productos = Producto::where('nombre', 'like', "%{$search}%")->get();

// ❌ NUNCA hacer esto
// DB::raw("SELECT * FROM productos WHERE nombre = '$search'");
```

**FormRequest Validation:**
```php
public function rules(): array
{
    return [
        'email' => ['required', 'email:rfc,dns'],
        'codigo' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9-]+$/'],
    ];
}
```

---

### A04:2021 - Insecure Design ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **Arquitectura:** Separación de capas (Controllers, Services, Models)
- **DTOs:** Clases específicas para transferencia de datos
- **Validación:** FormRequest en todas las entradas
- **Rate Limiting:** Throttle en rutas sensibles

```php
// Rate limiting configurado
Route::middleware(['throttle:api'])->group(function () {
    // 60 requests por minuto por defecto
});

Route::middleware(['throttle:login'])->post('/login', ...);
// 5 intentos por minuto
```

---

### A05:2021 - Security Misconfiguration ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **DEBUG:** Deshabilitado en producción
- **Headers:** Configurados correctamente
- **CORS:** Restrictivo por defecto
- **Error Messages:** Genéricos en producción

```php
// config/app.php
'debug' => env('APP_DEBUG', false),

// config/cors.php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
```

**Headers de seguridad recomendados (nginx):**
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'" always;
```

---

### A06:2021 - Vulnerable and Outdated Components ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **pnpm:** Gestor de paquetes seguro (no npm)
- **Composer audit:** Verificación de vulnerabilidades
- **Dependabot:** Alertas automáticas en GitHub
- **Actualizaciones:** Proceso regular de actualización

```bash
# Auditoría de seguridad
composer audit
pnpm audit

# Actualización segura
composer update --with-all-dependencies
pnpm update --latest
```

---

### A07:2021 - Identification and Authentication Failures ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **Laravel Sanctum:** Tokens seguros para API
- **Password Policy:** Mínimo 8 caracteres, complejidad
- **Session Management:** Tokens con expiración
- **Multi-factor:** Preparado para implementar

```php
// config/sanctum.php
'expiration' => 60 * 24, // 24 horas

// Validación de contraseñas
'password' => ['required', Password::min(8)->mixedCase()->numbers()],
```

---

### A08:2021 - Software and Data Integrity Failures ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **pnpm lockfile:** Integridad verificada
- **Composer.lock:** Versiones fijadas
- **CI/CD:** Pipeline seguro
- **Firma digital:** XAdES-EPES para facturas

```bash
# pnpm verifica integridad automáticamente
pnpm install --frozen-lockfile
```

---

### A09:2021 - Security Logging and Monitoring Failures ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **Laravel Logging:** Configurado para producción
- **Sentry:** Monitoreo de errores
- **Audit Trail:** Registros de cambios
- **Activity Log:** Acciones de usuario

```php
// config/logging.php
'channels' => [
    'stack' => [
        'channels' => ['daily', 'sentry'],
    ],
],
```

---

### A10:2021 - Server-Side Request Forgery (SSRF) ✅

**Estado:** IMPLEMENTADO

**Medidas aplicadas:**
- **URLs validadas:** Solo dominios permitidos
- **Timeout:** Configurado en requests externos
- **Hacienda API:** URLs fijas, no dinámicas

```php
// Solo URLs de Hacienda permitidas
private const HACIENDA_URLS = [
    'production' => 'https://api.comprobanteselectronicos.go.cr',
    'sandbox' => 'https://api-sandbox.comprobanteselectronicos.go.cr',
];
```

---

## 📦 Gestión de Dependencias (pnpm)

### ¿Por qué pnpm en lugar de npm?

| Característica | npm | pnpm |
|---------------|-----|------|
| Almacenamiento | Duplicados | Content-addressable |
| Velocidad | Lento | 2-3x más rápido |
| Seguridad | Vulnerable a supply chain | Mejor aislamiento |
| Integridad | Básica | Verificación estricta |
| Espacio en disco | Alto | Mínimo |

### Instalación de pnpm

```bash
# Instalación global
npm install -g pnpm

# O con corepack (recomendado)
corepack enable
corepack prepare pnpm@latest --activate

# Verificar instalación
pnpm --version
```

### Comandos de seguridad

```bash
# Auditoría de vulnerabilidades
pnpm audit

# Actualizar con parches de seguridad
pnpm update --latest

# Verificar integridad del lockfile
pnpm install --frozen-lockfile

# Listar dependencias outdated
pnpm outdated
```

### Configuración (.npmrc)

```ini
# Usar pnpm exclusivamente
only-allow-pnpm=true

# Verificar integridad
verify-store-integrity=true

# Auditoría automática
audit=true
```

---

## 🔑 Autenticación y Autorización

### Laravel Sanctum

```php
// Protección de rutas API
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('facturas', FacturaController::class);
});
```

### Policies

```php
// Autorización granular
class FacturaPolicy
{
    public function view(User $user, Factura $factura): bool
    {
        return $user->empresa_id === $factura->empresa_id;
    }
}
```

### Roles y Permisos

El sistema implementa RBAC (Role-Based Access Control):

- **Super Admin:** Acceso total
- **Admin Empresa:** Gestión de su empresa
- **Usuario:** Operaciones según permisos

---

## 🛡️ Protección de Datos

### Datos en Tránsito

- TLS 1.3 obligatorio en producción
- HSTS habilitado
- Certificados válidos

### Datos en Reposo

- Encriptación AES-256
- Hashing Bcrypt para contraseñas
- Backups encriptados

### Datos Sensibles

```php
// Modelo con campos ocultos
protected $hidden = [
    'password',
    'remember_token',
    'p12_password',
];

// Campos encriptados
protected $casts = [
    'password' => 'hashed',
    'datos_sensibles' => 'encrypted',
];
```

---

## ⚙️ Configuración Segura

### Variables de Entorno

```env
# Producción
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tudominio.com

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # No exponer externamente

# Cache y sesiones
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### Headers HTTP

```php
// Middleware de seguridad recomendado
public function handle($request, $next)
{
    $response = $next($request);
    
    return $response
        ->header('X-Frame-Options', 'DENY')
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('X-XSS-Protection', '1; mode=block')
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
}
```

---

## 📊 Auditoría y Monitoreo

### Logs de Seguridad

```php
// Registrar eventos importantes
Log::channel('security')->info('Login exitoso', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

### Alertas

- Intentos de login fallidos (> 5)
- Acceso denegado repetido
- Cambios en roles/permisos
- Errores 500 en producción

### Sentry Integration

```php
// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'traces_sample_rate' => 0.2,
```

---

## 🚨 Respuesta a Incidentes

### Proceso

1. **Detección:** Monitoreo automático + reportes
2. **Contención:** Aislar el problema
3. **Erradicación:** Eliminar la causa
4. **Recuperación:** Restaurar operaciones
5. **Lecciones:** Documentar y mejorar

### Contactos de Seguridad

```
Email: seguridad@ursol.com
Urgencias: +506 8868-7765
```

### Checklist de Incidente

- [ ] Identificar alcance
- [ ] Notificar equipo
- [ ] Preservar evidencia
- [ ] Contener amenaza
- [ ] Comunicar a afectados
- [ ] Documentar timeline
- [ ] Implementar mejoras

---

## 📝 Reporte de Vulnerabilidades

Si encuentras una vulnerabilidad de seguridad:

1. **NO** publiques públicamente
2. Envía email a: `seguridad@ursol.com`
3. Incluye:
   - Descripción detallada
   - Pasos para reproducir
   - Impacto potencial
   - Sugerencia de fix (opcional)

Respondemos en máximo 48 horas hábiles.

---

## ✅ Checklist de Despliegue Seguro

### Pre-producción

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] HTTPS configurado
- [ ] Firewall activo
- [ ] Base de datos no expuesta
- [ ] Redis protegido
- [ ] Logs configurados
- [ ] Backups automáticos
- [ ] Monitoreo activo

### Post-despliegue

- [ ] Verificar headers de seguridad
- [ ] Probar autenticación
- [ ] Verificar rate limiting
- [ ] Revisar logs por errores
- [ ] Confirmar SSL/TLS

---

*Documento generado por Sistemas Ursol S.A. - Confidencial*
