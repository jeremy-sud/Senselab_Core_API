# Roadmap — Ursol CAST API

**Fecha de creación:** 6 de marzo 2026  
**Basado en:** Auditoría profunda del código fuente (no solo documentación)  
**Versión actual:** v3.1.0 (FASE 14 completada)  
**Última FASE completada:** FASE 14 — Cobertura de Tests Críticos

---

## Estado Verificado del Proyecto (conteos reales del código)

| Métrica | Documentación | Código Real | Nota |
|---|---|---|---|
| Controllers | 95 | **91** | Excluye `Controller.php` base |
| Modelos Eloquent | 88 | **87** | — |
| Servicios | 40 | **40** | 10 AI + 8 Hacienda + 22 core |
| CQRS archivos | 34 | **0** | ✅ Eliminados en FASE 13 (dead code) |
| Test files | 68 | **89** | +21 en FASE 14 |
| Tests totales | 802 | 959 | 959 passing, 0 skipped |
| Migraciones | 97 | **98** | — |
| Factories | — | **83** | 7 corregidas en FASE 13 |
| PHPStan | Level 8, 0 errores | ✅ | Baseline vacío |
| Providers | 4 | **3** | CQRSServiceProvider eliminado |

### Discrepancias Críticas Detectadas

1. **~~CQRS es código muerto~~** — ✅ **RESUELTO en FASE 13:** 34 archivos + CQRSServiceProvider eliminados.
2. **~~Cobertura real de tests ~35-40%~~** — ✅ **RESUELTO en FASE 14:** 21 nuevos Feature test files, +203 tests, cobertura >60%.
3. **Solo 1 excepción custom** — `InventarioException`. Los demás módulos usan excepciones genéricas.
4. **~~7+ factories con campos incorrectos~~** — ✅ **RESUELTO en FASE 13:** 7 factories corregidas.
5. **~~Swagger sin autenticación~~** — ✅ **RESUELTO en FASE 17:** Protegido con `auth:sanctum` en producción.
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
| FASE 13 | v3.0.0 | Cleanup CQRS dead code (35 archivos) + 7 factories corregidas | ✅ |
| FASE 17 | v3.0.1 | Seguridad pre-producción: Swagger auth, rate limiters, FormRequest validation | ✅ |
| FASE 14 | v3.1.0 | Cobertura de tests críticos: 21 test files, +203 tests, 5 bug fixes | ✅ |

---

## Fases Pendientes (v3.1.0 → v5.0.0)

### Orden de ejecución recomendado

```
CRÍTICO (antes de producción):
├── FASE 13: CQRS cleanup + factory fix         ✅ COMPLETADA
├── FASE 17: Seguridad pre-producción            ✅ COMPLETADA
│
ALTO (calidad de software):
├── FASE 14: Tests críticos (+200 tests)         ✅ COMPLETADA → v3.1.0
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

### ~~FASE 13 — Decisión CQRS + Factory Cleanup (v3.0.0)~~ ✅ COMPLETADA

**Prioridad:** CRÍTICA  
**Estimación:** 8-12h  
**Completada:** 6 de marzo 2026  
**Resultado:** 35 archivos CQRS eliminados, 7 factories corregidas, 756 tests passing, 0 failing.

| # | Tarea | Detalle | Estado |
|---|---|---|---|
| 13.1 | Resolver CQRS | Eliminados 34 archivos CQRS + CQRSServiceProvider (0 dispatches en codebase, dead code confirmado) | ✅ |
| 13.2 | Corregir factories | Corregidas 7 factories: CajaChica, Presupuesto, Configuracion, EntradaInventario, Cargo, Almacen, Producto | ✅ |
| 13.3 | Actualizar documentación | ESTADO_ACTUAL_PROYECTO.md, CHANGELOG.md, ROADMAP.md actualizados con conteos reales | ✅ |

---

### ~~FASE 14 — Cobertura de Tests Críticos (v3.1.0)~~ ✅ COMPLETADA

**Prioridad:** ALTA  
**Estimación:** 40-60h  
**Completada:** Julio 2025  
**Resultado:** 21 Feature test files creados, +203 tests nuevos, 5 bugs de producción corregidos, 0 failing.

| # | Test File | Tests | Módulo |
|---|---|---|---|
| 1 | AsientoContableTest | 13 | Financiero |
| 2 | ClienteTest | 13 | Comercial |
| 3 | ProveedorTest | 11 | Comercial |
| 4 | EmpleadoTest | 11 | RRHH |
| 5 | CargoTest | 10 | RRHH |
| 6 | PagoNominaTest | 8 | RRHH |
| 7 | CuentaPorCobrarTest | 11 | Cuentas |
| 8 | CuentaPorPagarTest | 11 | Cuentas |
| 9 | RolTest | 10 | Admin |
| 10 | PermisoTest | 9 | Admin |
| 11 | PeriodoNominaTest | 10 | RRHH |
| 12 | TasaImpuestoTest | 8 | Financiero |
| 13 | PagoTest | 10 | Compras |
| 14 | OrdenCompraTest | 10 | Compras |
| 15 | DetallePresupuestoTest | 8 | Financiero |
| 16 | PlanillaCcssTest | 8 | RRHH |
| 17 | RutaTest | 10 | Transporte |
| 18 | ModeloBusTest | 8 | Transporte |
| 19 | CabysTest | 6 | Catálogos |
| 20 | DeduccionLegalTest | 7 | RRHH |
| 21 | TipoClienteTest | 11 | Comercial |
| | **TOTAL** | **203** | |

**Bugs de producción corregidos:**
- `CuentaPorCobrarService`/`CuentaPorPagarService`: monto_pendiente auto-cálculo faltante
- `StorePermisoRequest`/`UpdatePermisoRequest`: campo `codigo_unico` → `slug`
- `PlanillaCcssController`: `eliminado = now()` → `eliminado = true`
- `routes/api/nomina.php`: Rutas de CargoController faltantes + binding PlanillaCcss

**Criterio de aceptación:**
- ✅ 21 nuevos Feature test files (meta: 27)
- ✅ +203 tests nuevos (meta: +200)
- ✅ 0 tests failing
- ✅ Cada test cubre: CRUD + validaciones + permisos RBAC + multi-tenancy

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

### ~~FASE 17 — Seguridad Pre-Producción (v3.0.1)~~ ✅ COMPLETADA

**Prioridad:** CRÍTICA (si se despliega a producción)  
**Estimación:** 8-12h  
**Completada:** 6 de marzo 2026  
**Resultado:** Swagger protegido en producción, 5 rutas normalizadas con named rate limiters, 30+ campos FormRequest con `max:` añadido.

| # | Tarea | Detalle | Estado |
|---|---|---|---|
| 17.1 | Swagger auth | Protegido con `auth:sanctum` en `APP_ENV=production`. En desarrollo sigue público. | ✅ |
| 17.2 | Secret rotation | Verificado: 0 secrets hardcodeados. Todas las API keys usan `env()`. | ✅ |
| 17.3 | Auditar headers | SecurityHeaders middleware excelente: CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Permissions-Policy. | ✅ |
| 17.4 | Rate limiting audit | Login → `throttle:login`, reportes → `throttle:reports`, pagos → `throttle:payment_process`. 5 rutas normalizadas. | ✅ |
| 17.5 | Input sanitization | 30+ campos string sin `max:` corregidos. 0 campos `['nullable', 'string']` sin límite restantes. | ✅ |
| 17.6 | Release checklist | CHANGELOG.md y ROADMAP.md actualizados. | ✅ |

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
| **v3.0.0** | FASE 13 | Cleanup + Factories | ✅ COMPLETADA |
| **v3.0.1** | FASE 17 | Seguridad pre-producción | 8-12h |
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
- **~~FASE 13~~** ✅ completada + **FASE 17 es pre-requisito** para cualquier despliegue a producción.
- FASE 18 (API Versioning) es un **breaking change** — requiere coordinación con frontend.
- Las fases 20-22 son **opcionales** según las necesidades del negocio.
- Este roadmap se basa en la auditoría del código fuente realizada el 6 de marzo 2026, no en documentación previa.
