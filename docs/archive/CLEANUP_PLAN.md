# 🧹 Plan de Limpieza y Organización de Ursol CAST API

**Fecha:** 13 de febrero de 2026
**Responsable:** GitHub Copilot

Este documento detalla la estrategia y acciones para mejorar la organización del repositorio, eliminando duplicados, moviendo artefactos históricos y preparando el terreno para una refactorización más profunda, basándonos en los hallazgos de `MapaEstructuralAPIRESTMultitenant.txt`.

---

## 🎯 Objetivos de la Limpieza

1.  **Eliminar Ruido Operativo:** Mover archivos de respaldo y versiones antiguas a una ubicación centralizada (`docs/archive`).
2.  **Resolver Conflictos de Naming:** Identificar y unificar clases con nombres duplicados o muy similares que causan ambigüedad (ej. `ConsecutivoFE` vs `ConsecutivoFe`).
3.  **Identificar Áreas de Refactorización:** Listar controladores con alta complejidad o gran número de líneas para priorizar su refactorización (FASE 4.2).
4.  **Modularizar Rutas:** Sentar las bases para la partición del archivo `routes/api.php`.
5.  **Estandarizar Multi-tenancy:** Detectar modelos sin el trait `BelongsToTenant`.

---

## 🛠️ Scripts de Ayuda (Herramientas a crear)

Se crearán los siguientes scripts para automatizar la detección de problemas:

### 1. `scripts/cleanup/find_backups.sh`

**Propósito:** Identificar archivos con patrones de nombres que sugieren ser respaldos o versiones antiguas.
**Patrones a buscar:** `*.backup.php`, `*.bak`, `*.old`, `*-copy.php`, `*.php.temp`.
**Acción:** Listar archivos y sugerir moverlos a `docs/archive` o eliminarlos.

### 2. `scripts/cleanup/find_duplicates.sh`

**Propósito:** Detectar archivos PHP con nombres idénticos o con diferencias mínimas de mayúsculas/minúsculas en diferentes directorios, o clases con el mismo nombre pero diferente namespace.
**Ejemplos:**
    - `app/Http/Controllers/ConsecutivoFEController.php` vs `app/Http/Controllers/ConsecutivoFeController.php`
    - `app/Http/Resources/ConsecutivoFEResource.php` vs `app/Http/Resources/ConsecutivoFeResource.php`
**Acción:** Listar los duplicados y las recomendaciones de unificación.

### 3. `scripts/cleanup/list_large_controllers.sh`

**Propósito:** Listar todos los controladores PHP ordenados por número de líneas de código, destacando aquellos que exceden un umbral (ej. 400 líneas).
**Acción:** Proveer una lista priorizada para la FASE 4.2: Refactorización de Controladores.

### 4. `scripts/cleanup/list_untenanted_models.sh`

**Propósito:** Identificar modelos que no implementan el trait `BelongsToTenant`, lo que representa un riesgo de fuga de datos en un entorno multi-tenant.
**Acción:** Listar los modelos a los que se les debe agregar el trait `BelongsToTenant`.

---

## 🚀 Fases de Ejecución

1.  **Creación de Scripts:** Desarrollar los 4 scripts mencionados en el directorio `scripts/cleanup/`.
2.  **Ejecución y Análisis:** Ejecutar los scripts y analizar sus salidas.
3.  **Acción Manual:** Mover/eliminar los archivos de respaldo y resolver los conflictos de naming de forma manual.
4.  **Refactorización:** Utilizar la lista de controladores grandes para priorizar la FASE 4.2.
5.  **Estandarización:** Aplicar el trait `BelongsToTenant` a los modelos identificados.

---

## 📅 Cronograma (Estimado)

-   **Hoy (13 Feb):** Creación de `docs/CLEANUP_PLAN.md` y `scripts/cleanup/find_backups.sh`.
-   **Mañana (14 Feb):** Creación de `scripts/cleanup/find_duplicates.sh`, `scripts/cleanup/list_large_controllers.sh` y `scripts/cleanup/list_untenanted_models.sh`.
-   **15-16 Feb:** Ejecución de scripts y acciones manuales de limpieza.
-   **Continuo:** Integración de DTOs en controladores (FASE 4.3), PHPStan (FASE 4.1) y refactorización de controladores (FASE 4.2) basándose en los resultados.

---

**Próximo Paso:** Crear `scripts/cleanup/find_backups.sh`.