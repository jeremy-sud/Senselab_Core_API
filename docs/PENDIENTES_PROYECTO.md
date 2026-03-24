# PENDIENTES DEL PROYECTO — Ursol CAST API

**Última actualización:** 24 de marzo de 2026  
**Versión:** v3.3.0 (Post-FASE 16)  
**Referencia:** [ROADMAP.md](../ROADMAP.md) | [release_checklist.md](release_checklist.md)

---

## 📊 Resumen Ejecutivo

| Categoría | Pendientes | Prioridad |
|-----------|-----------|-----------|
| Bloqueante producción | 1 (E2E Hacienda sandbox) | 🔴 Crítica |
| Deuda técnica | 6 items menores | 🟡 Baja |
| Fases futuras ROADMAP | 4 fases (18, 20-22) | 🟢 Planificado |

> **Estado general:** La gran mayoría de pendientes históricos (julio 2025 — marzo 2026) han sido resueltos a través de 18+ fases. Este documento refleja solo los pendientes reales vigentes.

---

## 🔴 BLOQUEANTE PARA PRODUCCIÓN

### 1. FASE 19.6 — Validación E2E Hacienda Sandbox
- **Flujo:** OAuth → XML v4.4 → Firma XAdES-EPES → Envío → Consulta Estado → Logout
- **Prerrequisito:** Credenciales OVi (en trámite con representante legal)
- **Tests existentes:** 55 unit tests pasan, pero falta validación contra sandbox real
- **Ref:** `tests/Feature/FacturacionElectronicaE2ETest.php` (5 tests skipped por falta de certificado)

---

## 🟡 DEUDA TÉCNICA (No Bloqueante)

| ID | Descripción | Ubicación | Severidad |
|----|-------------|-----------|-----------|
| DT-1 | 4 modelos con timestamps `created_at/updated_at` en vez de `creado_en/actualizado_en` | ZonaGeografica, CuentaBancaria, PlanillaCcss, MovimientoBancario | 🟡 Baja |
| DT-2 | 3 observers declarados pero vacíos | AsientoContableObserver, ClienteObserver, VentaObserver | 🟡 Baja |
| DT-3 | Dualidad naming ConsecutivoFE / ConsecutivoFe | Controllers + Resources duplicados con contratos distintos | 🟡 Baja |
| DT-7 | `shell_exec()` en HealthCheckController | Input hardcoded (seguro), pero anti-patrón | 🟠 Media |
| DT-8 | Placeholders en MetricsController | `registerApplicationMetrics()` inexistente, hit rate fijo 75.5 | 🟡 Baja |
| DT-9 | Imports a modelos inexistentes | Comprobante, Factura, InventarioMovimiento en HaciendaIntegrationService, audit.php | 🟡 Baja |

---

## 🟢 FASES FUTURAS (Según ROADMAP)

| FASE | Descripción | Dependencia |
|------|-------------|-------------|
| 18 | API Versionado (v1/v2) | Breaking change — coordinar con frontend |
| 20 | Webhooks + Event-Driven Architecture | Post-producción |
| 21 | Reporting Engine avanzado | Post-producción |
| 22 | Escalabilidad (read replicas, Horizon, OpenTelemetry) | Post-producción |

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
| Cobertura tests ~35-40% | v3.3.0 | 997 tests, 141 archivos |
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
