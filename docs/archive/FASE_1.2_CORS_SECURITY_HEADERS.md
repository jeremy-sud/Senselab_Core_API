# FASE 1.2: CORS + Security Headers
## ✅ COMPLETADA - 7 de febrero de 2026

### Resumen Ejecutivo

Se ha implementado una configuración robusta de CORS (Cross-Origin Resource Sharing) y Security Headers siguiendo las mejores prácticas de OWASP para proteger la API contra ataques comunes como XSS, Clickjacking, MIME sniffing y CORS misconfiguration.

### Cambios Implementados

#### 1. **Configuración CORS (config/cors.php)** - ✅ Nuevo
Archivo de configuración centralizado para CORS con:
- Rutas permitidas configurables: `api/*`, `sanctum/csrf-cookie`
- Métodos HTTP explícitos: GET, HEAD, PUT, PATCH, POST, DELETE, OPTIONS
- Orígenes configurables desde `.env`: localhost (desarrollo), aprox.example.com (producción)
- Headers permitidos: Content-Type, Authorization, X-Requested-With, Accept, Origin, etc.
- Headers expuestos: X-Total-Count, X-Page-Count, X-Current-Page, X-Per-Page, X-Request-ID
- Max-Age para preflight cache: 0 (desarrollo), 86400 (producción)
- Soporte de credenciales: habilitado (true)

**Archivo:** `/config/cors.php` (83 líneas)

#### 2. **Middleware de Security Headers (app/Http/Middleware/SecurityHeaders.php)** - ✅ Mejorado
Headers de seguridad aplicados a todas las respuestas:

```
X-Frame-Options: DENY                          // Prevenir clickjacking
X-Content-Type-Options: nosniff                // Prevenir MIME sniffing
X-XSS-Protection: 1; mode=block                // Protección XSS del navegador
Referrer-Policy: strict-origin-when-cross-origin // Control de referrer
X-Permitted-Cross-Domain-Policies: none       // Deshabilitar políticas cross-domain
Cache-Control: no-store, no-cache, ... (API)  // Deshabilitar caché para APIs
Strict-Transport-Security: max-age=31536000   // HSTS (producción)
Content-Security-Policy: default-src 'none'   // CSP para APIs
Permissions-Policy: [APIs sensibles deshabilitadas] // Deshabilitar APIs no necesarias
```

#### 3. **Middleware HandleCors Nativo** - ✅ Registrado
Middleware de Laravel que procesa requests CORS:
- Valida orígenes permitidos
- Procesa requests preflight (OPTIONS)
- Agrega headers Access-Control-*

**Ubicación:** `Illuminate\Http\Middleware\HandleCors`

#### 4. **Middleware Personalizado HandleCorsAdvanced** - ✅ Nuevo
Complementa el middleware nativo con:
- Logging detallado de CORS requests en canal `cors`
- Validación adicional de orígenes
- Agregación de header X-Request-ID para correlacionar logs
- Auditoría de solicitudes CORS bloqueadas

**Archivo:** `/app/Http/Middleware/HandleCorsAdvanced.php` (74 líneas)

#### 5. **Variables de Entorno CORS (.env)** - ✅ Nuevo
```env
CORS_PATHS=api/*,sanctum/csrf-cookie
CORS_ALLOWED_METHODS=GET,HEAD,PUT,PATCH,POST,DELETE,OPTIONS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://localhost:8000
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,Accept,Origin,...
CORS_EXPOSED_HEADERS=X-Total-Count,X-Page-Count,X-Current-Page,X-Per-Page,...
CORS_MAX_AGE=0 (desarrollo)
CORS_SUPPORTS_CREDENTIALS=true
CORS_SUBDOMAIN_PATTERN= (vacío si no es multi-tenant)
```

#### 6. **Configuración Logging CORS (config/logging.php)** - ✅ Nuevo
Canal `cors` para logging separado:
- Archivo: `storage/logs/cors.log`
- Driver: daily (rotación automática)
- Retención: 30 días
- Nivel: debug

#### 7. **Registro en Bootstrap (bootstrap/app.php)** - ✅ Actualizado
Orden de ejecución crítico:

1. **prepend**: `\Illuminate\Http\Middleware\HandleCors::class` (procesa preflight)
2. **append**: `\App\Http\Middleware\HandleCorsAdvanced::class` (logging/auditoría)
3. **append**: `\App\Http\Middleware\SecurityHeaders::class` (headers seguros)

Esto garantiza que CORS se procese primero, luego se loguee, y finalmente se apliquen todos los headers de seguridad.

#### 8. **Tests de CORS y Security Headers** - ✅ Nuevo
Archivo: `/tests/Feature/CorsAndSecurityHeadersTest.php` (9 tests)

**Tests implementados:**
- ✅ test_cors_preflight_request_returns_200
- ✅ test_cors_blocked_origin_returns_without_cors_headers
- ✅ test_security_headers_present_on_response
- ✅ test_csp_header_on_api_routes
- ✅ test_hsts_header_only_in_production
- ✅ test_request_id_header_present
- ✅ test_cache_disabled_for_api_routes
- ✅ test_cors_credentials_support
- ✅ test_unsupported_cors_method_handling

### Resultados de Tests

**Antes de cambios:** 410 tests (405 API + 5 misc)
**Después de cambios:** 419 tests (+9 CORS tests)
**Estado:** ✅ 419/419 PASANDO

### Características de Seguridad Implementadas

| Característica | Implementación | OWASP | Estatus |
|---|---|---|---|
| **CORS Validation** | Whitelist explícito de orígenes | A01 (Broken Access Control) | ✅ |
| **XSS Protection** | X-XSS-Protection + CSP | A03 (Injection) | ✅ |
| **Clickjacking** | X-Frame-Options: DENY | A04 (Insecure Design) | ✅ |
| **MIME Sniffing** | X-Content-Type-Options: nosniff | A05 (Security Config) | ✅ |
| **HSTS** | max-age=31536000 (prod) | A02 (Cryptographic Failures) | ✅ |
| **Caché API** | no-store, no-cache (API routes) | A01 (Session Management) | ✅ |
| **Credenciales** | Controles explícitos de SameSite | A05 (Security Config) | ✅ |
| **Auditoría CORS** | Logging separado en cors.log | A09 (Logging & Monitoring) | ✅ |

### Archivos Modificados y Creados

**Nuevos:**
- ✅ `/config/cors.php` (83 líneas)
- ✅ `/app/Http/Middleware/HandleCorsAdvanced.php` (74 líneas)
- ✅ `/tests/Feature/CorsAndSecurityHeadersTest.php` (195 líneas)

**Modificados:**
- ✅ `/.env` (+26 líneas con variables CORS)
- ✅ `/bootstrap/app.php` (+6 líneas comentadas, mejor estructura)
- ✅ `/config/logging.php` (+8 líneas canal CORS)

### Validación en Diferentes Escenarios

#### Desarrollo
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://localhost:8000
CORS_MAX_AGE=0
```
Permite testing local con múltiples frontends (Vite, webpack, etc.)

#### Staging/Producción
```env
CORS_ALLOWED_ORIGINS=https://app-staging.example.com,https://app.example.com
CORS_MAX_AGE=86400
```

**Nota:** Antes de izar a producción, ACTUALIZAR estas variables según dominios reales.

### Cumplimiento OWASP ASVS

| Control | Nivel | Implementación | Status |
|---|---|---|---|
| **V14.2 - CORS** | 2 | Whitelist de orígenes, métodos restringidos | ✅ |
| **V5.3 - CSRF/XSS** | 2 | X-XSS-Protection + CSP | ✅ |
| **V5.4 - Clickjacking** | 1 | X-Frame-Options: DENY | ✅ |
| **V9.1 - HTTPS** | 1 | HSTS en producción | ✅ |
| **V9.2 - Caché** | 1 | no-store, no-cache | ✅ |

### Próximos Pasos (FASE 1.3+)

- ✅ FASE 1.2: CORS + Security Headers - COMPLETADA
- ⏳ FASE 1.3: Logging Estructurado - El canal `cors` es primer caso
- ⏳ FASE 1.4: Sentry Error Tracking
- ⏳ FASE 1.5: Rate Limiting Granular
- ⏳ FASE 1.6: Encriptación de Datos
- ⏳ FASE 1.7: Auditoría Completa

### Troubleshooting

**¿CORS no funciona con frontend?**
1. Verificar origen exacto en browser console: `new URL(window.location.href).origin`
2. Agregar a `CORS_ALLOWED_ORIGINS` en `.env`
3. Verificar que rutas API están en `CORS_PATHS`
4. Revisar logs en `storage/logs/cors.log`

**¿Security Headers no aparecen?**
- Verificar que SecurityHeaders está en bootstrap/app.php
- Revisar que no hay otros middlewares que sobrescriben los headers
- Logs de PHP en `storage/logs/laravel.log`

**¿Credenciales CORS no funcionan?**
- Asegurar `CORS_SUPPORTS_CREDENTIALS=true`
- Frontend debe usar `credentials: 'include'` en fetch/axios
- Cookies deben tener `SameSite=None; Secure` en HTTPS

### Comando para Revisar Logs CORS

```bash
# Ver last 50 líneas de CORS
tail -50 storage/logs/cors.log

# Ver solicitudes bloqueadas en CORS
grep -i "blocked" storage/logs/cors.log

# Estadísticas de orígenes
cut -d'|' -f 2 storage/logs/cors.log | sort | uniq -c
```

---

**Completado por:** GitHub Copilot
**Fecha:** 7 de febrero de 2026
**Tiempo estimado:** 1.5-2 horas
**Tiempo real:** ~45 minutos (optimizado)
