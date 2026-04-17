# PENDIENTES DEL PROYECTO — Ursol CAST API

**Última actualización:** 17 de abril de 2026  
**Versión:** v5.0.1 (Post-auditoría 13 abr 2026)  
**Referencia:** [ROADMAP.md](../ROADMAP.md) | [release_checklist.md](release_checklist.md) | [Auditoría Hacienda 17 abr](hacienda/AUDITORIA_FIRMA_DIGITAL_2026-04-17.md)

---

## 📊 Resumen Ejecutivo

| Categoría | Pendientes | Prioridad |
|-----------|-----------|-----------|
| Bloqueante producción | 2 (Hacienda firma digital) | 🔴 Crítico |
| Deuda técnica | 8 items (2 altos + 6 medios/bajos) | 🟠 Medio |
| Fases futuras ROADMAP | 0 (100% completado) | ✅ Completado |

> **Estado general:** Roadmap 100% completado (22 fases). Auditoría técnica 9.2/10. **Auditoría Hacienda 17 abr:** 10 hallazgos (2 críticos, 2 altos). La firma digital NO funciona con OpenSSL 3.x por certificado .p12 legacy. `HaciendaIntegrationService` es código muerto placeholder.

---

## 🔴 BLOQUEANTE PARA PRODUCCIÓN — Hacienda Firma Digital

> **Referencia completa:** [docs/hacienda/AUDITORIA_FIRMA_DIGITAL_2026-04-17.md](hacienda/AUDITORIA_FIRMA_DIGITAL_2026-04-17.md)

### H-1. Certificado .p12 incompatible con OpenSSL 3.x 🔴
- **Estado:** Pendiente
- **Problema:** Los certificados `.p12` de Hacienda CR usan `RC2-40-CBC` (legacy). OpenSSL 3.x (PHP 8.4) NO los soporta por defecto → `openssl_pkcs12_read()` falla con `error:0308010C:digital envelope routines::unsupported`.
- **Impacto:** `FirmaDigitalService` no puede cargar el certificado → ningún comprobante puede ser firmado ni enviado.
- **Validado:** OAuth funciona ✅, certificado convertido a formato moderno firma correctamente ✅.
- **Solución:** Implementar auto-conversión legacy→modern en `FirmaDigitalService::leerCertificadoP12()` usando `openssl` CLI con flag `-legacy`, o documentar conversión manual previa.
- **Archivos:** `app/Services/Hacienda/Xml/FirmaDigitalService.php`

### H-2. HaciendaIntegrationService es código muerto 🔴
- **Estado:** Pendiente
- **Problema:** Servicio con firma placeholder (`return $xmlContent`), XML concatenado con strings sin escape, URLs de API inexistentes (`send`, `get-status`), generador de clave con formato incorrecto (no 50 dígitos), y `openssl_x509_read()` en archivo .p12.
- **Impacto:** Todo método de este servicio falla o produce resultados inválidos. Ya existen servicios correctos: `XmlComprobanteBuilder`, `XadesEpesSigner`, `HaciendaApiClient`, `ClaveNumericaGenerator`.
- **Solución:** Eliminar `HaciendaIntegrationService` y migrar cualquier referencia a los servicios dedicados.
- **Archivos:** `app/Services/Hacienda/HaciendaIntegrationService.php`

### ~~FASE 19.6 — Validación E2E Hacienda Sandbox~~ ✅
- **Estado:** Completado. Test suite E2E contra sandbox real implementado.
- **Tests:** `HaciendaSandboxE2ETest.php` con 8 tests (OAuth, XML v4.4, firma XAdES-EPES, envío, consulta estado).
- **Hacienda v4.4:** 38/38 brechas resueltas (100%). Fase A, B y C completadas.

---

## 🟠 DEUDA TÉCNICA — Hacienda (Auditoría 17 abr 2026)

| ID | Descripción | Ubicación | Severidad |
|----|-------------|-----------|-----------|
| H-3 | Password de certificado en base64 (sin encriptar) | `FirmaDigitalService::desencriptarPassword()` | 🟠 Alto |
| H-4 | Tipo declarado `OpenSSLAsymmetricKey` pero valor real es `string` PEM | `FirmaDigitalService::$privateKey` | 🟠 Alto |
| H-6 | OAuthTokenManager usa Guzzle directo en vez de `Http::` facade | `OAuthTokenManager.php` | 🟡 Medio |
| H-7 | HaciendaApiClient instancia dependencias sin DI | `HaciendaApiClient::__construct()` | 🟡 Medio |
| H-8 | RateLimiter usa get+put no atómico (race condition con Horizon) | `RateLimiter::incrementRequestCount()` | 🟢 Bajo |
| H-9 | Token TTL default 3600s confuso (real es 300s) | `config/hacienda.php` | 🟢 Bajo |
| H-10 | UUID generado sin guiones (no RFC 4122) | `XadesEpesSigner::generateUuid()` | 🟢 Bajo |

---

## 🟡 DEUDA TÉCNICA — General (No Bloqueante)

| ID | Descripción | Ubicación | Severidad |
|----|-------------|-----------|-----------|
| DT-1 | 4 modelos con timestamps `created_at/updated_at` en vez de `creado_en/actualizado_en` | ZonaGeografica, CuentaBancaria, PlanillaCcss, MovimientoBancario | 🟡 Baja |
| ~~DT-2~~ | ~~3 observers declarados pero vacíos~~ | ~~Eliminados en FASE 19.7~~ | ✅ Resuelto |
| DT-3 | Dualidad naming ConsecutivoFE / ConsecutivoFe | Controllers + Resources duplicados con contratos distintos | 🟡 Baja |
| ~~DT-7~~ | ~~`shell_exec()` en HealthCheckController~~ | Ya usa `file_get_contents('/proc/uptime')` — sin `shell_exec()` en código | ✅ Resuelto (v5.0.1) |
| ~~DT-8~~ | ~~Placeholders en MetricsController~~ | Cache hit rate ahora usa Redis INFO stats reales | ✅ Resuelto (v5.0.1) |
| ~~DT-9~~ | ~~Imports a modelos inexistentes~~ | Import `App\Models\Comprobante` eliminado de HaciendaIntegrationTest | ✅ Resuelto (v5.0.1) |
| DT-10 | Tests detección N+1 automáticos | Futuro | 🟢 Baja |
| DT-11 | `tenant_id` automático en logs | Futuro | 🟢 Baja |

---

## ✅ FASES COMPLETADAS

| FASE | Descripción | Versión |
|------|-------------|-------------|
| ~~18~~ | ~~API Versionado (v1/v2)~~ | ✅ v4.0.0 |
| ~~20~~ | ~~Webhooks + Event-Driven Architecture~~ | ✅ v4.2.0 |
| ~~21~~ | ~~Reporting Engine avanzado~~ | ✅ v4.3.0 |
| ~~22~~ | ~~Escalabilidad (read replicas, Horizon, OpenTelemetry)~~ | ✅ v5.0.0 |
| ~~Post-auditoría~~ | ~~SSRF webhooks, Swagger reporting, test UseReadReplica, DT-7/8/9~~ | ✅ v5.0.1 |

---

## ✅ HISTORIAL DE RESOLUCIÓN (Resumen)

Los siguientes pendientes fueron resueltos entre julio 2025 y marzo 2026:

| Pendiente Original | Resuelto En | FASE |
|--------------------|-------------|------|
| Modelo MovimientoPresupuesto faltante | v2.4.0 | Sprint 7 |
| 3 reportes PDF no implementados | v2.4.0 | Sprint 7 |
| Token de Hacienda en SyncHaciendaJob | v2.1.0 | FASE 2.1 |
| Importaciones masivas (clientes/proveedores) | v2.4.0 | Sprint 7 |
| GDPR verificación real | v2.4.0 | Sprint 7 |
| PHPStan ~2,065 errores suprimidos | v2.6.0 | FASE 4 → Level 8, 0 errores |
| Service Layer parcial (solo Venta) | v3.3.0 | FASE 16 → BaseService + 22+ servicios |
| CQRS dead code | v3.0.0 | FASE 13 → 34 archivos eliminados |
| Cobertura tests ~35-40% | v4.2.0 | 1261 tests, 142+ archivos |
| Referencias a modelos inexistentes | v2.4.0 | Sprint 7.1 |
| XDebug en producción | v2.4.0 | Sprint 7.2 |
| DTOs duplicados (18) | v2.4.0 | Sprint 7.1 |
| Seeders duplicados (15) | v2.4.0 | Sprint 7.1 |
| Excepciones de dominio (solo 1) | v3.2.0 | FASE 15 → 11 excepciones tipadas |
| Respuestas API inconsistentes | v3.2.0 | FASE 15 → ApiResponse trait |
| Sin CORS configurado | v2.1.0 | FASE 1.2 |
| Sin logging estructurado | v2.1.0 | FASE 1.7 |
| Sin encriptación de datos | v2.1.0 | FASE 1.6 |
| Sin audit trail | v2.1.0 | FASE 1.7 |
| Rate limiting débil | v2.1.0 | FASE 1.5 |
| Dependencias con wildcard `*` | v2.1.0 | FASE 1 |

---

*Documento mantenido como registro vivo. Para el plan completo de desarrollo, ver [ROADMAP.md](../ROADMAP.md).*
