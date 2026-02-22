# FASE 1.3: Logging Estructurado
## ✅ COMPLETADA - 7 de febrero de 2026

### Resumen Ejecutivo

Se ha implementado un sistema de **logging estructurado con JSON formatting** siguiendo el patrón de 12 Factor App, con canales separados para seguridad, auditoría y performance. Todos los eventos se registran con `trace_id` único para correlacionar logs relacionados, contexto de usuario, timestamps ISO8601 e IP address para auditoría y debugging.

### Cambios Implementados

#### 1. **Configuración Monolog (config/logging.php)** - ✅ Mejorado
3 nuevos canales de logging con JSON formatting y rotación automática:

| Canal | Archivo | Nivel | Retención | Propósito |
|---|---|---|---|---|
| **security** | logs/security.log | debug | 30 días | Requests HTTP, errores, eventos críticos |
| **audit** | logs/audit.log | info | 90 días | Auditoría de cambios (CRUD operations) |
| **performance** | logs/performance.log | debug | 30 días | Requests lentas, queries lentas |
| **cors** | logs/cors.log | debug | 30 días | Requests CORS (de FASE 1.2) |

**Configuración JSON:**
```php
'formatter' => \Monolog\Formatter\JsonFormatter::class,
'processors' => [
    \Monolog\Processor\UidProcessor::class,
    \Monolog\Processor\InvocationProcessor::class,
]
```

**Archivos:** `/config/logging.php` (+72 líneas)

#### 2. **Middleware LogRequest** - ✅ Nuevo
Registra entrada/salida de TODAS las requests HTTP con:
- **trace_id:** UUID único para correlacionar logs (propagado en X-Trace-ID header)
- **user_id:** ID del usuario autenticado (si existe)
- **user_email:** Email del usuario para auditoría
- **ip:** Dirección IP del cliente
- **method:** Verbo HTTP (GET, POST, etc.)
- **path:** Ruta del endpoint
- **status_code:** Código HTTP de respuesta
- **duration_ms:** Duración en milisegundos
- **timestamp:** Timestamp ISO8601

**Archivos:** `/app/Http/Middleware/LogRequest.php` (115 líneas)

**Ejemplo de log:**
```json
{
  "message": "http.request.completed",
  "trace_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "user_id": 5,
  "user_email": "usuario@example.com",
  "ip": "192.168.1.100",
  "method": "POST",
  "path": "api/productos",
  "status_code": 201,
  "duration_ms": 125.45,
  "timestamp": "2026-02-07T14:30:45.123456+00:00"
}
```

#### 3. **Trait HasSafeErrorHandling Mejorado** - ✅ Refactorizado
Actualizado para incluir:
- **trace_id** en respuesta (+ header X-Trace-ID)
- **Contexto de usuario** (user_id, user_email)
- **IP address** para auditoría
- **Timestamp ISO8601**
- **Logging en canal 'security'** (no default)
- **Propagación de trace_id** en todas las respuestas de error

**Métodos actualizados:**
- `safeErrorResponse()` - Errores exceptions
- `validationErrorResponse()` - Validación (422)
- `notFoundResponse()` - 404
- `unauthorizedResponse()` - 403

**Archivos:** `/app/Traits/HasSafeErrorHandling.php` (160 líneas)

#### 4. **Registro en Bootstrap** - ✅ Actualizado
Orden de ejecución de middleware en `bootstrap/app.php`:

1. **prepend:** `HandleCors` (CORS preflight)
2. **append:** `LogRequest` (Logging estrutucturado)
3. **append:** `HandleCorsAdvanced` (CORS logging)
4. **append:** `SecurityHeaders` (Headers de seguridad)

**Archivos:** `/bootstrap/app.php` (+2 líneas)

#### 5. **Variables de Entorno** - ✅ Nuevo
```env
# Logging Channels (FASE 1.3)
LOG_SECURITY_LEVEL=debug
LOG_CORS_LEVEL=debug
LOG_AUDIT_LEVEL=info
```

**Archivos:** `/.env` (+3 líneas)

#### 6. **Tests de Logging Estructurado** - ✅ Nuevo
10 tests que validan:
- ✅ LogRequest registra requests exitosas
- ✅ X-Trace-ID se propaga en headers
- ✅ Canales de logging están configurados
- ✅ Canal 'security' usa JSON formatter
- ✅ Logs incluyen contexto de usuario
- ✅ Performance logging está configurado
- ✅ Timestamps en formato ISO8601
- ✅ HasSafeErrorHandling incluye trace_id
- ✅ Middleware está registrado en alias
- ✅ Archivos de log se crean con rotación

**Archivos:** `/tests/Feature/StructuredLoggingTest.php` (185 líneas)

### Resultados de Tests

**Antes de cambios:** 419 tests
**Después de cambios:** 429 tests (+10 logging tests)
**Estado:** ✅ 429/429 PASANDO

### Características Implementadas

| Característica | Implementación | Estándar | Status |
|---|---|---|---|
| **Logging Estructurado** | JSON formatting en 4 canales | 12 Factor App | ✅ |
| **Trace ID Correlation** | UUID único propagado en headers | Observabilidad | ✅ |
| **User Context** | user_id, user_email, ip en logs | GDPR/Auditoría | ✅ |
| **Performance Tracking** | Logging de requests > 1000ms | APM/Observabilidad | ✅ |
| **Rotación Automática** | RotatingFileHandler (30-90 días) | Log Management | ✅ |
| **Timestamps ISO8601** | TimeZone-aware timestamps | ISO 8601 Standard | ✅ |
| **Procesadores Monolog** | UidProcessor, InvocationProcessor | Log Enrichment | ✅ |
| **Auditoría de Errores** | Canal 'security' con 90 días | Cumplimiento | ✅ |
| **X-Trace-ID Header** | Propagación en requests/responses | Observabilidad | ✅ |

### Cumplimiento OWASP ASVS

| Control | Nivel | Implementación | Status |
|---|---|---|---|
| **V8.1 - Auditoría** | 2 | Logging de todas las requests + errores | ✅ |
| **V9.1 - Registro de eventos** | 2 | JSON estructurado con contexto | ✅ |
| **V9.2 - Protección de logs** | 2 | Rotación automática + retención | ✅ |
| **V9.3 - Respuesta de incidentes** | 1 | Trace ID para correlación | ✅ |

### Archivos Modificados y Creados

**Nuevos:**
- ✅ `/app/Http/Middleware/LogRequest.php` (115 líneas)
- ✅ `/tests/Feature/StructuredLoggingTest.php` (185 líneas)

**Modificados:**
- ✅ `/config/logging.php` (+72 líneas - 3 canales)
- ✅ `/app/Traits/HasSafeErrorHandling.php` (+70 líneas - trace_id, contexto)
- ✅ `/bootstrap/app.php` (+2 líneas - registro middleware)
- ✅ `/.env` (+3 líneas - variables logging)

### Cómo Revisar los Logs

#### Logs de Seguridad (requests HTTP)
```bash
# Last 50 líneas
tail -50 storage/logs/security.log | jq '.'

# Ver solo errores
grep -i '"level":"error"' storage/logs/security.log | jq '.message'

# Ver requests por usuario
jq 'select(.user_id==5)' storage/logs/security.log
```

#### Logs de CORS
```bash
# Ver solicitudes CORS bloqueadas
grep -i 'blocked\|unauthorized' storage/logs/cors.log
```

#### Logs de Performance
```bash
# Ver requests más lentos
jq 'select(.duration_ms > 500)' storage/logs/performance.log | jq '.path, .duration_ms'
```

#### Logs de Auditoría
```bash
# Ver cambios en datos (FASE 1.7 implementará Observers)
tail -100 storage/logs/audit.log | jq '.'
```

#### Correlacionar con trace_id
```bash
# Buscar todos los logs para un trace_id específico
TRACE_ID="a1b2c3d4-e5f6-7890-abcd-ef1234567890"

grep "$TRACE_ID" storage/logs/security.log
grep "$TRACE_ID" storage/logs/cors.log
grep "$TRACE_ID" storage/logs/performance.log

# Combinar para análisis
(grep "$TRACE_ID" storage/logs/*.log | sort -t: -k3) | jq '.'
```

### Próximos Pasos (FASE 1.4+)

- ✅ FASE 1.1: Fijar Dependencias - COMPLETADA
- ✅ FASE 1.2: CORS + Security Headers - COMPLETADA
- ✅ FASE 1.3: Logging Estructurado - COMPLETADA
- ⏳ FASE 1.4: Sentry Error Tracking - Captura errores en tiempo real
- ⏳ FASE 1.5: Rate Limiting Granular
- ⏳ FASE 1.6: Encriptación de Datos
- ⏳ FASE 1.7: Auditoría Completa

### Beneficios Implementados

✅ **Debugging en Producción:** Con trace_id y contexto, puedes seguir un request desde inicio hasta fin
✅ **Auditoría de Seguridad:** Todos los accesos registrados con user_id e IP
✅ **Cumplimiento Normativo:** Logs estructurados en ISO8601 para auditoría tributaria CR
✅ **Performance Monitoring:** Identificar requests lentas (> 1000ms) automáticamente
✅ **Análisis de Errores:** Todos los errores con stack trace completo
✅ **Correlación de Eventos:** trace_id permite conectar eventos relacionados

### Troubleshooting

**¿Dónde se escriben los logs?**
```
storage/logs/security.log      # Requests HTTP + errores
storage/logs/audit.log         # Cambios en datos
storage/logs/cors.log          # Solicitudes CORS
storage/logs/performance.log   # Requests lentas
```

**¿Cómo obtener el trace_id de una respuesta?**
```bash
curl -i http://localhost:8000/api/empresas | grep X-Trace-ID
```

**¿Los logs están en JSON?**
```bash
tail -1 storage/logs/security.log | jq '.message, .trace_id'
```

**¿Cómo correlacionar logs de múltiples requests?**
Usar el `X-Trace-ID` header en frontend y backend para propagar:
```javascript
// Frontend
const traceId = generateUUID();
fetch('/api/endpoint', {
  headers: { 'X-Trace-ID': traceId }
});

// Backend (automático en LogRequest.php)
// Se propaga al siguiente request via X-Trace-ID header
```

---

**Completado por:** GitHub Copilot  
**Fecha:** 7 de febrero de 2026  
**Tiempo estimado:** 2-2.5 horas  
**Tiempo real:** ~50 minutos (optimizado)  
**Tests creados:** 10  
**Tests pasando:** 429/429 (↑10)
