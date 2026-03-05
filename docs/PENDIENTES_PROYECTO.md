# ANÁLISIS COMPLETO DE PENDIENTES - Ursol CAST API

**Fecha de generación:** 2 de julio de 2025

## 📊 Resumen Ejecutivo

| Categoría | Cantidad | Prioridad |
|-----------|----------|-----------|
| TODOs en Código | 12 | Alta/Media |
| Tests Skipped | 5 | Media |
| ~~Modelo Faltante~~ | ~~1~~ ✅ | ~~Alta~~ |
| Reportes PDF | 3 | Alta |
| Importaciones | 2 | Media |
| PHPStan Baseline | ~2,065 errores PHPDoc | Baja |
| CQRS Expansión | 3+ módulos | Media |
| Service Layer Expansión | ~~6 módulos críticos~~ ✅ ~79 controllers restantes | Media |

---

## 🔴 ALTA PRIORIDAD

### 1. Modelo Faltante: `MovimientoPresupuesto`
- **Ubicación:** Referenciado en `app/Models/DetallePresupuesto.php:97`
- **Impacto:** Relación `movimientos()` comentada, funcionalidad de presupuestos incompleta
- **Acción:** Crear modelo + migración + relaciones

### 2. Reportes PDF No Implementados
En `app/Jobs/GeneratePdfReportJob.php`:
- Línea 116: Reporte de **Inventario**
- Línea 125: Reporte de **Cuentas por Cobrar**
- Línea 134: Reporte de **Nómina**

### 3. Token de Hacienda
- `app/Jobs/SyncHaciendaJob.php:174`: Obtención de token no implementada

---

## 🟡 MEDIA PRIORIDAD

### 4. Importaciones Masivas Pendientes
En `app/Jobs/ProcessImportJob.php`:
- Línea 141: Importación de **Clientes**
- Línea 150: Importación de **Proveedores**

### 5. GDPR - Verificación Real
- `app/Http/Controllers/GdprController.php:135`: Lógica de verificación pendiente

### 6. Sistema de Notificaciones
- `app/Jobs/GeneratePdfReportJob.php:73`: Notificar usuario cuando PDF está listo
- `app/Jobs/ProcessImportJob.php:76`: Notificar al usuario tras importación

### 7. Tests Skipped (5)
En `tests/Feature/FacturacionElectronicaE2ETest.php`:
- Líneas 269, 306: Requieren certificado real de Hacienda

En `tests/Feature/FacturacionElectronicaE2ECasosEdgeTest.php`:
- Línea 138: Validación de totales no implementada
- Línea 234: Error 500 en cálculo con líneas exentas
- Línea 416: Funcionalidad de reenvío (redundante)

---

## 🟢 BAJA PRIORIDAD (Deuda Técnica)

### 8. PHPStan Baseline
- **~2,065 errores** suprimidos (principalmente `@param`/`@return` en PHPDoc)
- El código compila y funciona (nivel 8 con baseline)
- Recomendación: Reducir gradualmente mediante refactorización

### 9. Documentación de Fases
Las fases **5 (Rendimiento)** y **6 (CQRS)** están implementadas pero la documentación no está actualizada en los archivos FASE_*.md

---

## 📝 PLAN DE ACCIÓN SUGERIDO

**Sprint Inmediato (~20h):**
1. ~~Crear modelo `MovimientoPresupuesto` + migración~~ ✅
2. ~~Implementar 3 reportes PDF (inventario, CxC, nómina)~~ ✅
3. ~~Completar importación de clientes/proveedores~~ ✅

**Sprint Siguiente (~15h):**
4. ~~Implementar token de Hacienda en SyncHaciendaJob~~ ✅
5. ~~Completar verificación GDPR real~~ ✅
6. ~~Sistema de notificaciones para Jobs~~ ✅

**Mantenimiento Continuo:**
7. Reducir baseline PHPStan (10-20 errores por sprint)
8. Resolver tests skipped cuando haya certificados de prueba

---

## ✅ RESUELTO EN v2.5.0 (FASE 8 — Service Layer Pattern)

- ✅ **5 servicios nuevos creados** — AlmacenService, CuentaContableService, EmpleadoService, OrdenCompraService, PeriodoNominaService
- ✅ **1 servicio mejorado** — ProveedorService (DTOs→arrays, métodos listar/obtener añadidos)
- ✅ **6 controladores refactorizados** — Constructor DI, eliminado HasCacheableQueries, ~50% reducción promedio de líneas
- ✅ **8 módulos con Service Layer** en total (incluyendo Venta y AsientoContable de FASE 4)

## ✅ RESUELTO EN v2.4.0 (Sprint 7.1 + 7.2)

- ✅ **Referencias a modelos inexistentes** — Corregidas 5 referencias en config/audit.php, config/encryption.php, InstallSecurityFeatures.php
- ✅ **XDebug en producción** — Eliminado del Dockerfile (condicional vía ARG)
- ✅ **PHP 8.2→8.4** — Unificado en composer.json + 6 workflows CI/CD
- ✅ **18 DTOs duplicados** — Eliminados de subdirectorios
- ✅ **15 seeders duplicados** — Naming singular vs plural unificado
- ✅ **Tests módulos críticos** — 4 suites nuevas (Inventario, Contabilidad, Compras, Nómina)

## 🔴 PENDIENTES NUEVOS (Identificados en Auditoría v2.4.0)

### Service Layer Pattern
- ~~Solo `VentaController` usa Service Layer; los ~85 controllers restantes tienen lógica de negocio inline~~ 
- ✅ **FASE 8 completada:** 8 controladores con Service Layer (Venta, AsientoContable, Almacén, CuentaContable, Proveedor, Empleado, OrdenCompra, PeriodoNomina)
- **Pendiente:** ~79 controllers restantes sin Service Layer (prioridad baja — módulos secundarios)

### CQRS Expansión
- Infraestructura CQRS completa pero solo implementada para Venta
- **Siguientes módulos:** Inventario, Contabilidad, Compras

### Cobertura de Tests
- Cobertura estimada ~35-40% (55 archivos de test)
- **Meta:** 60% para v3.0
