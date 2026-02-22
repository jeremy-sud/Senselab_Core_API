# ANÁLISIS COMPLETO DE PENDIENTES - Ursol CAST API

**Fecha de generación:** 20 de febrero de 2026

## 📊 Resumen Ejecutivo

| Categoría | Cantidad | Prioridad |
|-----------|----------|-----------|
| TODOs en Código | 12 | Alta/Media |
| Tests Skipped | 5 | Media |
| Modelo Faltante | 1 | Alta |
| Reportes PDF | 3 | Alta |
| Importaciones | 2 | Media |
| PHPStan Baseline | ~2,065 errores PHPDoc | Baja |

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
