# Auditoría Completa — Senselab Core API

**Fecha:** 22 de febrero de 2026  
**Branch:** main  
**PHP:** 8.4.11 | **Laravel:** 12.x | **PHPStan:** Level 8 | **PHPUnit:** 11.5.44

---

## Puntuación General: 6.5 / 10

---

## Radiografía del Proyecto

| Métrica | Valor | Veredicto |
|---|---|---|
| Líneas de código (app/) | 88,416 | Proyecto grande y ambicioso |
| Líneas de tests | 12,357 | ~14% ratio test/código — bajo |
| Modelos | 86 | Dominio rico y completo |
| Controllers | 92 | Buena cobertura funcional |
| Rutas API | ~560 | API extensa |
| Migrations | 96 (66 con FKs) | BD bien estructurada |
| FormRequests | 170 | Validación de entrada sólida |
| Resources | 79 | Serialización separada del modelo |
| Policies | 80 (62 registradas) | RBAC implementado |
| DTOs | 42 | Separación de capas |
| Services | 31 | Lógica de negocio extraída |
| Factories | 83 / 86 modelos | 96% cobertura de factories |
| Seeders | 86 | BD testeable |
| Tests | 46 archivos (529 tests, 1698 assertions) | Existen pero cubren poco |
| PHPStan | Level 8 — 0 errores | Excelente |
| PHPUnit | 529 tests, 0 failures, 5 skipped | Pasan todos |

---

## Desglose por Área

### 1. Arquitectura y Patrones — 7/10

**Lo bueno:**
- Multi-tenancy implementado con `BelongsToTenant` (42/86 modelos)
- Services (31), DTOs (42), Resources (79), FormRequests (170) — capas bien separadas
- CQRS con Command/Query Bus, contratos e interfaces
- 11 Traits reutilizables: `BelongsToTenant`, `HasActiveScope`, `HasAuditFields`, `HasAuditableEvents`, `HasCacheableQueries`, `HasCustomSoftDeletes`, `HasEmpresaContext`, `HasEncryptedAttributes`, `EncryptsAttributes`, `HasPermissionCache`, `HasSafeErrorHandling`
- Observers (7) para side-effects del ciclo de vida de modelos
- Jobs (8) para procesamiento asíncrono

**Lo malo:**
- CQRS solo implementado para **Ventas** (4 commands + 6 queries). Los otros 91 controllers son CRUD directo — patrón incompleto e inconsistente
- No hay Events/Listeners (0 archivos) — todo se resuelve en observers o inline
- Solo 1 Exception personalizada (`InventarioException`) — para 92 controllers deberían haber más
- `ThrottleApiRequests` middleware es un stub vacío (solo `return $next($request)`)
- 44 modelos no tienen `BelongsToTenant` — potencial fuga de datos multi-tenant

### 2. Testing — 4.5/10

**Lo bueno:**
- 529 tests, 1698 assertions, 0 failures
- Tests Feature (24 archivos) + Unit (22 archivos) con buena estructura
- Factories para 83/86 modelos (96%)
- CI con GitHub Actions (tests.yml)
- Tests cubren: Auth, CORS, encryption, rate limiting, Hacienda, RBAC, modelos

**Lo malo:**
- 92 controllers, 46 test files — solo ~50% de cobertura por archivo
- No hay configuración de code coverage en phpunit.xml
- `sonar.php.coverage.reportPaths=coverage.xml` referencia un archivo que nunca se genera
- 0 tests para los 4 controllers de AI (anomalías, contenido, Cabys, crédito)
- 0 tests de integración para Hacienda services
- Ratio test/código del 14% es bajo para un ERP financiero
- Tests faltantes para: Inventario, Contabilidad, Compras, Nómina, Transporte

### 3. Seguridad — 6/10

**Lo bueno:**
- Sanctum con expiración de 480 min (corregido en esta auditoría)
- 80 Policies + middleware `CheckPermission` para RBAC
- Security Headers middleware (OWASP compliance)
- Rate limiting granular: 5 limiters (api, reports, imports, hacienda, login)
- CORS bien configurado con env vars, documentado según OWASP
- Soft deletes en 64 modelos — no se borran datos (auditoría)
- Encryption service disponible

**Lo malo:**
- Multi-tenancy parcial (42/86 modelos) — modelos sin tenant podrían filtrar datos
- `EncryptsAttributes` / `HasEncryptedAttributes` traits existen pero **0 modelos los usan** — datos sensibles sin cifrar en BD
- Los 4 AI controllers usan `DB::table()` bypassing scopes y tenant isolation
- Sin 2FA
- Sin WAF ni detección de inyección avanzada
- Sin Content Security Policy detallada

### 4. Base de Datos — 7.5/10

**Lo bueno:**
- 96 migraciones bien organizadas
- 66 con foreign keys (69% de las migraciones)
- 86 seeders para datos de prueba completos
- Custom soft deletes consistente

**Lo malo:**
- 7 modelos sin factory: `AuditLog`, `Cabys`, `DataRetentionPolicy`, `GdprDeletionRequest`, `HaciendaComprobante`, `MovimientoPresupuesto`, `UsuarioRol`
- Sin vistas materializadas ni procedimientos almacenados (útiles para reportes ERP)
- Indices no verificados exhaustivamente

### 5. API Design — 7/10

**Lo bueno:**
- 560 rutas organizadas en 14 archivos temáticos: `ai`, `auth`, `catalogos`, `compliance`, `compras`, `configuracion`, `contabilidad`, `core`, `fe`, `inventario`, `nomina`, `observabilidad`, `transporte`, `ventas`
- Versionado API (v1)
- RESTful conventions
- OpenAPI/Swagger UI generado en `public/docs/`
- Resources para serialización uniforme

**Lo malo:**
- 5 controllers con `DB::raw()` (ConsecutivoFE, ComplianceDashboard, Pago, CuentaPorPagar, CuentaPorCobrar)
- 2 AI controllers con `DB::table()` (Content, Cabys)
- Sin rate limiting en endpoints AI
- Sin documentación de paginación estándar
- Sin versionado de respuestas de error

### 6. DevOps / Infraestructura — 8/10

**Lo bueno:**
- Docker multi-stage (mysql, nginx, php, redis)
- docker-compose para dev y staging
- Kubernetes con base + overlays (dev/prod) + HPA + PVC + Ingress
- 6 GitHub Actions workflows: ci-cd.yml, tests.yml, phpstan.yml, code-analysis.yml, deploy-staging.yml, deploy-production.yml
- SonarQube integrado
- Makefile con comandos de desarrollo
- `.env.example` presente

**Lo malo:**
- Coverage XML nunca se genera (SonarQube no recibe datos de cobertura)
- Sin smoke tests post-deploy
- Sin health check de dependencias (Redis, MySQL) en Docker health

### 7. Documentación — 6.5/10

**Lo bueno:**
- README.md completo
- CHANGELOG.md extenso (656 líneas)
- OpenAPI/Swagger UI
- Documentación de Hacienda (DGT)
- `docs/` organizado en subcarpetas: archive, guides, hacienda

**Lo malo:**
- 15 TODO/FIXME pendientes en el código
- Sin diagramas de arquitectura (C4, secuencia)
- Sin Architecture Decision Records (ADRs)
- Sin guía de contribución detallada

---

## Issues CRITICAL Corregidos en Esta Auditoría

| # | Archivo | Problema | Fix aplicado |
|---|---|---|---|
| 1 | `InventarioProductoController` | 4 métodos sin filtro `empresa_id` — fuga multi-tenant | Filtro `whereHas('almacen', ...)` + validación almacén ∈ empresa |
| 2 | `RolUsuarioController` | index/rolesPorUsuario/usuariosPorRol/asignarRoles sin tenant filtering | Filtro `empresa_id` en todos, `DB::transaction`, `$this->authorize()` |
| 3 | `CreditController` | calculateScore/getDetailedAnalysis permiten acceso cross-tenant | `Cliente::where('empresa_id', ...)->findOrFail()` |
| 4 | `EntidadEtiquetaController` | Sin authorization/policy | Añadido `$this->authorize()` |
| 5 | `config/sanctum.php` | Tokens nunca expiran (`null`) | `env('SANCTUM_TOKEN_EXPIRATION', 480)` |
| 6 | `CuentaContableResource` | N+1: `subcuentas()->count()` y `getNivel()` recursivo | `whenCounted()` + relaciones cargadas sin queries extra |
| 7 | `ProductoObserver` | `Cache::tags()` falla con driver file | Guard con `TaggableStore` instanceof check |
| 8 | `AuthorizationTest` | `dump()` debug en código de test | Eliminado |
| 9 | `RolUsuarioPolicy` | `hasPermissionTo()` método inexistente | Corregido a `hasPermission()` |

## Issues WARNING Corregidos en Esta Auditoría

| # | Archivo | Problema | Fix aplicado |
|---|---|---|---|
| 1 | `config/app.php` | `timezone` hardcoded | `env('APP_TIMEZONE', 'UTC')` |
| 2 | `config/session.php` | `secure` cookie sin default | `env('SESSION_SECURE_COOKIE', null)` |
| 3 | `config/cors.php` | Patrón vacío causa warning PHP 8.4 | `array_filter()` elimina entradas vacías |

---

## Siguientes Pasos Prioritarios

### P0 — Seguridad (URGENTE para ERP financiero)

1. **Completar multi-tenancy** — Auditar los 44 modelos sin `BelongsToTenant`. Los que manejan datos de empresa deben tener el trait. Riesgo #1 del proyecto.

2. **Implementar `EncryptsAttributes`** — El trait existe pero 0 modelos lo usan. Modelos prioritarios: `Cliente`, `CuentaBancaria`, `Empleado`, `Proveedor` (cédulas, cuentas bancarias, salarios).

3. **Aislar endpoints AI** — Los 4 AI controllers usan `DB::table()` directo. Deben migrar a Eloquent con scopes de tenant. Añadir rate limiting específico.

### P1 — Testing (antes de producción)

4. **Alcanzar 80% de cobertura** — Faltan tests para ~46 controllers. Priorizar:
   - Controllers financieros (Pagos, CuentasPorCobrar/Pagar)
   - Controllers de Hacienda (facturación electrónica)
   - Controllers de inventario
   - Controllers AI

5. **Configurar code coverage real** — Agregar sección `<coverage>` en phpunit.xml. Generar `coverage.xml` en CI para SonarQube.

6. **Tests E2E para flujos críticos** — Venta → factura electrónica → envío a Hacienda → respuesta.

### P2 — Arquitectura (deuda técnica)

7. **Decidir sobre CQRS** — Solo Ventas lo usa. Opciones:
   - Extenderlo a módulos core (Compras, Contabilidad, Inventario) — recomendado
   - O eliminar la implementación parcial para evitar inconsistencia

8. **Implementar Events/Listeners** — Actualmente 0. Eventos sugeridos:
   - `VentaCreated`, `FacturaEnviada`, `StockBajoMinimo`, `PagoRecibido`, `ComprobanteFirmado`

9. **Más Exceptions específicas** — Solo existe `InventarioException`. Crear:
   - `HaciendaException`, `TenantException`, `ContabilidadException`, `FacturacionException`

10. **Eliminar o implementar `ThrottleApiRequests`** — Actualmente es un stub vacío. Usar `ThrottleRequestsWithRetryAfter` que ya funciona.

### P3 — Calidad de código

11. **Resolver los 15 TODO/FIXME** pendientes en el código.

12. **Migrar 5 controllers con `DB::raw()`** a Eloquent query builder.

13. **Completar 7 factories faltantes** — AuditLog, Cabys, DataRetentionPolicy, GdprDeletionRequest, HaciendaComprobante, MovimientoPresupuesto, UsuarioRol.

14. **Configurar coverage threshold en CI** — Bloquear PRs que bajen el porcentaje de cobertura.

15. **Agregar diagramas de arquitectura** — C4, flujos de facturación, diagrama ER.

---

## Verificación Post-Auditoría

```
PHPStan:  677/677 archivos — 0 errores (Level 8)
PHPUnit:  529 tests, 1698 assertions, 0 failures, 5 skipped
```

---

*Auditoría realizada sobre el branch `main` del repositorio `SenseLab-dev/Senselab_Core_API`.*
