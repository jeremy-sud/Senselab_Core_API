# PENDIENTES DEL PROYECTO — Ursol CAST API

**Última actualización:** 13 de abril de 2026  
**Versión:** v5.0.1 (Post-auditoría 13 abr 2026)  
**Referencia:** [ROADMAP.md](../ROADMAP.md) | [release_checklist.md](release_checklist.md)

---

## 📊 Resumen Ejecutivo

| Categoría | Pendientes | Prioridad |
|-----------|-----------|-----------|
| Bloqueante producción | 0 | ✅ Resuelto |
| Deuda técnica | 2 items menores | 🟡 Baja |
| Fases futuras ROADMAP | 0 (100% completado) | ✅ Completado |

> **Estado general:** Roadmap 100% completado (22 fases). Auditoría técnica 9.2/10. La post-auditoría v5.0.1 resolvió: SSRF en webhooks, Swagger reporting (3 controllers), test UseReadReplica, DT-7/DT-8/DT-9, e imports no válidos.

---

## ~~🔴 BLOQUEANTE PARA PRODUCCIÓN~~ ✅ RESUELTO

### ~~1. FASE 19.6 — Validación E2E Hacienda Sandbox~~ ✅
- **Estado:** Completado. Test suite E2E contra sandbox real implementado.
- **Tests:** `HaciendaSandboxE2ETest.php` con 8 tests (OAuth, XML v4.4, firma XAdES-EPES, envío, consulta estado).
- **Hacienda v4.4:** 38/38 brechas resueltas (100%). Fase A, B y C completadas.

---

## 🟡 DEUDA TÉCNICA (No Bloqueante)

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
