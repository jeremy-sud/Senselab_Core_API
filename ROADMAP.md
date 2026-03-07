# Roadmap — Ursol CAST API

**Fecha de creación:** 6 de marzo 2026  
**Basado en:** Auditoría profunda del código fuente (no solo documentación)  
**Versión actual:** v2.9.0 (FASE 12 completada)  
**Última FASE completada:** FASE 12 — Migración PHPUnit Attributes + Coverage

---

## Estado Verificado del Proyecto (conteos reales del código)

| Métrica | Documentación | Código Real | Nota |
|---|---|---|---|
| Controllers | 95 | **92** | Excluye `Controller.php` base |
| Modelos Eloquent | 88 | **87** | — |
| Servicios | 40 | **40** | 10 AI + 8 Hacienda + 22 core |
| CQRS archivos | 34 | **34** | ⚠️ Dead code (0 dispatches) |
| Test files | 68 | **68** | — |
| Tests totales | 802 | 802 | 799 passing, 3 skipped |
| Migraciones | 97 | **98** | — |
| Factories | — | **~70** | ~7 con campos incorrectos |
| PHPStan | Level 8, 0 errores | ✅ | Baseline vacío |

### Discrepancias Críticas Detectadas

1. **CQRS es código muerto** — 34 archivos implementados, `CQRSServiceProvider` con 12 mappings, pero **0 dispatches** en toda la base de código. Los controllers usan Services directamente.
2. **Cobertura real de tests ~35-40%** — Solo ~30/92 controllers tienen Feature tests.
3. **Solo 1 excepción custom** — `InventarioException`. Los demás módulos usan excepciones genéricas.
4. **~7+ factories con campos incorrectos** — No coinciden con columnas reales de las migraciones.
5. **Swagger sin autenticación** — `/api/documentation` es público.
6. **35 seeders no autoejecutados** — Existen pero no se llaman desde `DatabaseSeeder::run()`.

---

## Fases Completadas (v2.0.0 → v2.9.0)

| FASE | Versión | Descripción | Estado |
|---|---|---|---|
| FASE 1 | v2.0.0 | Seguridad Crítica (CORS, headers, rate limiting, encriptación, auditoría) | ✅ |
| FASE 2 | v2.0.0 | Observabilidad (health checks, Prometheus, metrics middleware) | ✅ |
| FASE 3 | v2.3.0 | Auditoría integral (9 relaciones, MetricsController, 4 test suites) | ✅ |
| FASE 4 | v2.2.0 | Calidad de Código (PHPStan Level 8, 229 errores corregidos) | ✅ |
| FASE 5 | v2.2.0 | Rendimiento (Gzip, N+1 queries, cache warming) | ✅ |
| FASE 6 | v2.2.0 | CQRS infraestructura (CommandBus, QueryBus, 1 módulo) | ✅ |
| Sprint 7.1 | v2.4.0 | Limpieza crítica (18 DTOs + 15 seeders duplicados eliminados) | ✅ |
| Sprint 7.2 | v2.4.0 | Tests críticos (4 suites: Inventario, Contabilidad, Compras, Nómina) | ✅ |
| FASE 8 | v2.5.0 | Service Layer en 6 módulos (Almacén, CuentaContable, Proveedor, etc.) | ✅ |
| FASE 9 | v2.6.0 | Unit tests para servicios (86 tests) + 10 bugs críticos corregidos | ✅ |
| FASE 10 | v2.7.0 | CQRS 3 módulos + 6 controllers refactorizados + PHPStan baseline → 0 | ✅ |
| FASE 11 | v2.8.0 | 42 tests pre-existentes corregidos + 8 bugs de producción | ✅ |
| FASE 12 | v2.9.0 | PHPUnit attributes (405 migraciones) + 35 tests nuevos + 2 bugs FE | ✅ |

---

## Fases Pendientes (v3.0.0 → v5.0.0)

### Orden de ejecución recomendado

```
CRÍTICO (antes de producción):
├── FASE 13: CQRS cleanup + factory fix         [8-12h]   → v3.0.0
├── FASE 17: Seguridad pre-producción            [8-12h]   → v3.0.0
│
ALTO (calidad de software):
├── FASE 14: Tests críticos (+200 tests)         [40-60h]  → v3.1.0
├── FASE 15: Excepciones de dominio              [12-16h]  → v3.2.0
│
MEDIO (madurez arquitectónica):
├── FASE 16: Service Layer secundarios            [60-80h]  → v3.3.0
├── FASE 18: API versionado                      [20-30h]  → v4.0.0
├── FASE 19: Testing avanzado + CI/CD            [20-25h]  → v4.1.0
│
MEDIO-BAJO (features avanzados):
├── FASE 20: Webhooks + Event-Driven             [25-35h]  → v4.2.0
├── FASE 21: Reporting Engine + Dashboard API    [30-40h]  → v4.3.0
│
BAJO (escalabilidad futura):
└── FASE 22: Optimización + Escalabilidad        [30-40h]  → v5.0.0

TOTAL ESTIMADO: 254-371 horas
```

---

### FASE 13 — Decisión CQRS + Factory Cleanup (v3.0.0)

**Prioridad:** CRÍTICA  
**Estimación:** 8-12h  
**Objetivo:** Eliminar código muerto y corregir infraestructura de testing.

| # | Tarea | Detalle | Impacto |
|---|---|---|---|
| 13.1 | Resolver CQRS | **Opción A (recomendada):** Eliminar los 34 archivos CQRS + `CQRSServiceProvider` — son dead code, 0 dispatches en el codebase. **Opción B:** Integrar dispatch en los 14 controllers con Service Layer (refactor mayor). | Reduce complejidad, elimina confusión arquitectónica |
| 13.2 | Corregir factories | Auditar y corregir ~7+ factories con campos incorrectos vs esquema DB real (`CajaChicaFactory`, `PresupuestoFactory`, `ConfiguracionFactory`, `EntradaInventarioFactory`, `CargoFactory`, `AlmacenFactory`, `ProductoFactory`). | Tests más confiables |
| 13.3 | Actualizar documentación | Corregir conteos en `ESTADO_ACTUAL_PROYECTO.md` con números verificados del código. | Documentación precisa |

**Criterio de aceptación:**
- 0 archivos CQRS sin uso (eliminados o integrados)
- Todas las factories pasan `Factory::make()` sin errores
- Conteos de `ESTADO_ACTUAL_PROYECTO.md` coinciden con la realidad

---

### FASE 14 — Cobertura de Tests Críticos (v3.1.0)

**Prioridad:** ALTA  
**Estimación:** 40-60h  
**Objetivo:** Llevar cobertura de Feature tests de ~35% a >70%.

| # | Batch | Controllers a cubrir | Tests estimados |
|---|---|---|---|
| 14.1 | Módulo Financiero | `CuentaContableController`, `AsientoContableController`, `DetalleAsientoController`, `CajaController` | ~30 |
| 14.2 | Módulo Comercial | `ClienteController`, `ProveedorController`, `ProductoController`, `CategoriaProductoController` | ~30 |
| 14.3 | Módulo RRHH | `EmpleadoController`, `NominaEmpleadoController`, `PagoNominaController`, `CargoController` | ~30 |
| 14.4 | Módulo Cuentas | `CuentaPorCobrarController`, `CuentaPorPagarController`, `PagoCuentaCobrarController`, `PagoCuentaPagarController` | ~25 |
| 14.5 | Módulo Compras | `OrdenCompraController`, `DetalleOrdenCompraController`, `PagoController` | ~20 |
| 14.6 | Módulo Inventario | `SalidaInventarioController`, `DetalleSalidaInventarioController`, `DetalleEntradaInventarioController`, `AlmacenController` | ~25 |
| 14.7 | Módulo Admin | `UsuarioController`, `RolController`, `PermisoController`, `SucursalController` | ~25 |

**Meta total:** +27 Feature test files, +200 tests nuevos, cobertura >70%

**Criterio de aceptación:**
- >70% de controllers con Feature tests
- 0 tests failing
- Cada test cubre: CRUD completo + validaciones + permisos RBAC + multi-tenancy

---

### FASE 15 — Excepciones de Dominio + Error Handling (v3.2.0)

**Prioridad:** MEDIA-ALTA  
**Estimación:** 12-16h  
**Objetivo:** Reemplazar excepciones genéricas por excepciones de dominio semánticas.

| # | Tarea | Detalle |
|---|---|---|
| 15.1 | Crear excepciones base | `DomainException` abstracta + excepciones por módulo: `HaciendaException`, `ContabilidadException`, `VentaException`, `CompraException`, `NominaException`, `MultiTenancyException`, `FacturacionElectronicaException` |
| 15.2 | Integrar en Services | Reemplazar `throw new \Exception(...)` por excepciones tipadas en los 40 services existentes |
| 15.3 | Exception Handler | Configurar `Handler.php` para mapear cada excepción de dominio a HTTP status codes semánticos (409, 422, 502, etc.) |
| 15.4 | Tests de excepciones | Tests unitarios para cada escenario de error por excepción |

**Criterio de aceptación:**
- 0 `throw new \Exception()` genéricos en services
- Cada módulo tiene su excepción tipada
- Exception handler mapea correctamente a HTTP codes

---

### FASE 16 — Service Layer Pattern: Módulos Secundarios (v3.3.0)

**Prioridad:** MEDIA  
**Estimación:** 60-80h  
**Objetivo:** Extender Service Layer a los ~20 controllers más usados que aún no lo implementan.

| # | Batch | Controllers a refactorizar |
|---|---|---|
| 16.1 | Catálogos Core | `TipoImpuestoController`, `TasaImpuestoController`, `FormaPagoController`, `UnidadMedidaController`, `MarcaController` |
| 16.2 | Transacciones | `PagoController`, `MovimientoCajaChicaController`, `PagoCuentaCobrarController`, `PagoCuentaPagarController` |
| 16.3 | Facturación | `FeCertificadoDigitalController`, `ConsecutivoFeController`, `MensajeHaciendaController`, `ComprobanteRecibidoElectronicoController` |
| 16.4 | Transporte | `RutaController`, `HorarioRutaController`, `BusUnidadController`, `ModeloBusController` |
| 16.5 | Admin | `UsuarioController`, `RolController`, `SucursalController`, `NotificacionController` |

**Patrón a seguir:** Mismo patrón de FASE 8-10: Service con DI, DTO `toArray()`, controller delegando en service.

**Criterio de aceptación:**
- +20 Services nuevos
- Controllers reducidos ~50% en LOC
- Tests unitarios para cada service nuevo

---

### FASE 17 — Seguridad Pre-Producción (v3.0.0 — junto con FASE 13)

**Prioridad:** CRÍTICA (si se despliega a producción)  
**Estimación:** 8-12h  
**Objetivo:** Cerrar las brechas de seguridad identificadas en la auditoría.

| # | Tarea | Detalle |
|---|---|---|
| 17.1 | Swagger auth | Proteger `/api/documentation` con middleware `auth:sanctum` o restricción por IP/env. Desactivar `generate_always` en producción. |
| 17.2 | Secret rotation | Script automatizado para rotar: Sanctum tokens expirados, `APP_KEY`, API keys de Gemini/OpenAI. Verificar que no hay secrets hardcodeados (`git grep`). |
| 17.3 | Auditar headers | Verificar CSP, HSTS (`max-age=31536000`), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` en todos los endpoints reales (no solo middleware). |
| 17.4 | Rate limiting audit | Verificar que los 7 rate limiters están registrados AND asignados a las rutas correctas. Test E2E de throttling. |
| 17.5 | Input sanitization | Revisar los 170+ FormRequests para XSS (`strip_tags`), SQL injection (parametrizado), y validación adecuada de tipos. |
| 17.6 | Release checklist | Actualizar `docs/release_checklist.md` para reflejar estado post-FASE 12. |

**Criterio de aceptación:**
- Swagger protegido (401 sin token)
- 0 secrets hardcodeados en el repositorio
- Todos los headers de seguridad presentes en responses reales
- Release checklist actualizado y completo

---

### FASE 18 — API Versionado (v4.0.0)

**Prioridad:** MEDIA  
**Estimación:** 20-30h  
**Objetivo:** Preparar la API para evolución sin romper clientes existentes.

| # | Tarea | Detalle |
|---|---|---|
| 18.1 | Estructura rutas v1/v2 | Reorganizar `routes/api/` con prefijo `/api/v1/` para rutas existentes. Nuevas rutas bajo `/api/v2/`. |
| 18.2 | Namespace controllers | Crear `app/Http/Controllers/Api/V1/` (controllers existentes) y `V2/` (nuevos). |
| 18.3 | Resources versionados | Resources v1 (compatibilidad) y v2 (mejoras: HATEOAS links, paginación estandarizada). |
| 18.4 | Header-based versioning | Soporte alternativo: `Accept: application/vnd.ursol.v2+json`. |
| 18.5 | Deprecation strategy | Middleware que agrega header `Sunset` a endpoints v1 con fecha de deprecación. |
| 18.6 | Swagger dual | Swagger UI con selector de versión v1/v2. |

**Criterio de aceptación:**
- Rutas v1 funcionan idénticamente a las actuales (backward compatible)
- Rutas v2 disponibles con mejoras
- Header `Sunset` presente en responses v1

---

### FASE 19 — Testing Avanzado + CI/CD (v4.1.0)

**Prioridad:** MEDIA  
**Estimación:** 20-25h  
**Objetivo:** Elevar la calidad del pipeline de testing y CI/CD.

| # | Tarea | Detalle |
|---|---|---|
| 19.1 | Load testing | Scripts con k6 o Artillery para benchmarking de endpoints críticos (login, ventas, facturación). |
| 19.2 | Contract testing | Pact tests para validar contratos de API con consumidores frontend. |
| 19.3 | Mutation testing | Infection PHP para validar la calidad real de los tests existentes (¿realmente detectan bugs?). |
| 19.4 | CI pipeline mejorado | GitHub Actions: PHPStan + tests + mutation + coverage badge + deploy automático a staging. |
| 19.5 | Migration rollback tests | Verificar que las 98 migraciones ejecutan `up()` y `down()` correctamente. |
| 19.6 | E2E Hacienda sandbox | Test suite contra sandbox real de Hacienda con certificado de prueba del Ministerio de Hacienda. |

**Criterio de aceptación:**
- Scripts de load testing ejecutables
- CI pipeline: <10 min, badge de cobertura visible
- Mutation score >70%

---

### FASE 20 — Webhooks + Event-Driven (v4.2.0)

**Prioridad:** MEDIA-BAJA  
**Estimación:** 25-35h  
**Objetivo:** Permitir integraciones externas vía webhooks.

| # | Tarea | Detalle |
|---|---|---|
| 20.1 | Webhook system | Modelo `Webhook` + migración. Campos: `url`, `eventos[]`, `secret`, `activo`, `empresa_id`. |
| 20.2 | Event dispatchers | Laravel Events para: `venta.creada`, `factura.emitida`, `pago.recibido`, `inventario.bajo`, `cliente.creado`. |
| 20.3 | Webhook delivery | Job asíncrono con retry (3 intentos, backoff exponencial), firma HMAC-SHA256, timeout configurable. |
| 20.4 | Webhook logs | Modelo `WebhookLog`: status, response_code, latencia, payload_size, timestamps. |
| 20.5 | Admin endpoints | CRUD para gestionar webhooks por tenant: `POST /api/webhooks`, `GET /api/webhooks`, `DELETE /api/webhooks/{id}`. |
| 20.6 | Documentación | Guía de integración para consumidores externos de webhooks. |

**Criterio de aceptación:**
- Webhooks configurables por tenant
- Entrega confirmada con retry automático
- Logs de entrega consultables vía API

---

### FASE 21 — Reporting Engine + Dashboard API (v4.3.0)

**Prioridad:** MEDIA-BAJA  
**Estimación:** 30-40h  
**Objetivo:** Endpoints de reportes consolidados y dashboard de KPIs.

| # | Tarea | Detalle |
|---|---|---|
| 21.1 | Reportes financieros | Endpoint `/api/reportes/financiero` — Estado de Resultados (P&L), Balance General, Flujo de Caja. |
| 21.2 | Dashboard KPIs | Endpoint `/api/dashboard` — Ventas del mes, cuentas vencidas, inventario bajo mínimo, nómina pendiente. |
| 21.3 | Exportación multi-formato | PDF (DomPDF existente), Excel (PhpSpreadsheet), CSV. Parámetro `?formato=pdf\|excel\|csv`. |
| 21.4 | Reportes programados | Job para enviar reportes por email periódicamente (diario, semanal, mensual). Configurable por tenant. |
| 21.5 | Cache de reportes | Reportes pesados con cache Redis, TTL configurable por tipo de reporte. Invalidación al insertar datos nuevos. |
| 21.6 | Filtros avanzados | Rango de fechas, sucursal, moneda, comparación con período anterior (mes/trimestre/año). |

**Criterio de aceptación:**
- Reportes financieros con datos reales del tenant
- Exportación funcional en 3 formatos
- Cache reduce tiempo de respuesta >80% en consultas repetidas

---

### FASE 22 — Optimización y Escalabilidad (v5.0.0)

**Prioridad:** BAJA  
**Estimación:** 30-40h  
**Objetivo:** Preparar la API para alto volumen y escalabilidad horizontal.

| # | Tarea | Detalle |
|---|---|---|
| 22.1 | Repository Pattern | Abstracción de data access en módulos con Service Layer. Evaluar ROI — solo implementar si hay beneficio claro (testing, swappable storage). |
| 22.2 | Database read replicas | Configuración Laravel para MySQL read replicas en queries pesados (reportes, dashboards). |
| 22.3 | Queue monitoring | Laravel Horizon para monitoreo visual de jobs, failed jobs, throughput. |
| 22.4 | ETags + conditional GET | Headers `ETag` + `If-None-Match` para reducir transferencia de datos (304 Not Modified). |
| 22.5 | Database partitioning | Particionado por fecha en tablas de alto volumen (`ventas`, `audit_logs`, `comprobantes_electronicos`). |
| 22.6 | GraphQL endpoint | Endpoint GraphQL opcional con Lighthouse PHP para consultas flexibles (no reemplaza REST). |

**Criterio de aceptación:**
- Read replicas configuradas y testeadas
- Horizon dashboard funcional
- ETags reducen tráfico >30% en endpoints de listado

---

## Resumen de Versiones Planificadas

| Versión | Fases | Tipo | Horas Est. |
|---|---|---|---|
| **v3.0.0** | FASE 13 + 17 | Cleanup + Seguridad | 16-24h |
| **v3.1.0** | FASE 14 | Tests críticos | 40-60h |
| **v3.2.0** | FASE 15 | Excepciones de dominio | 12-16h |
| **v3.3.0** | FASE 16 | Service Layer secundarios | 60-80h |
| **v4.0.0** | FASE 18 | API Versionado (BREAKING) | 20-30h |
| **v4.1.0** | FASE 19 | Testing avanzado + CI/CD | 20-25h |
| **v4.2.0** | FASE 20 | Webhooks | 25-35h |
| **v4.3.0** | FASE 21 | Reporting Engine | 30-40h |
| **v5.0.0** | FASE 22 | Escalabilidad | 30-40h |
| | | **TOTAL** | **254-371h** |

---

## Notas

- Las estimaciones son para **1 desarrollador**. Con 2 devs, reducir ~40% en fases paralelizables (14, 16, 20, 21).
- **FASE 13 + 17 son pre-requisito** para cualquier despliegue a producción.
- FASE 18 (API Versioning) es un **breaking change** — requiere coordinación con frontend.
- Las fases 20-22 son **opcionales** según las necesidades del negocio.
- Este roadmap se basa en la auditoría del código fuente realizada el 6 de marzo 2026, no en documentación previa.
