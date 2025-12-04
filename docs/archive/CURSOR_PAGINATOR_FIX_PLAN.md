# Plan de Corrección: CursorPaginator vs LengthAwarePaginator

## Problema Detectado
Múltiples controladores utilizan `cursorPaginate()` pero invocan métodos incompatibles:
- `->currentPage()` ❌ No existe en CursorPaginator
- `->total()` ❌ No existe en CursorPaginator
- `->perPage()` ✅ Disponible en ambos

## Archivos Afectados (28 ocurrencias)
1. `app/Http/Controllers/API/PresupuestoController.php` (líneas 80-81)
2. `app/Http/Controllers/API/SalidaInventarioController.php` (líneas 98-99, 592-593, 647-648)
3. `app/Http/Controllers/API/EntradaInventarioController.php` (líneas 98-99, 578-579, 633-634)
4. `app/Http/Controllers/API/ComprobanteRecibidoElectronicoController.php` (líneas 82-83, 496-497)
5. `app/Http/Controllers/RolUsuarioController.php` (líneas 33, 36)
6. `app/Http/Controllers/EntidadEtiquetaController.php` (líneas 52, 55)
7. `app/Http/Controllers/ConsecutivoFEController.php` (líneas 58, 61)
8. `app/Http/Controllers/RolPermisoController.php` (líneas 44, 47)
9. `app/Http/Controllers/InventarioProductoController.php` (líneas 40, 43)

## Opciones de Corrección

### Opción 1: Cambiar a `paginate()` (Recomendado para APIs con paginación tradicional)
**Ventaja**: Mantiene compatibilidad con `current_page`, `total`, `last_page`.  
**Desventaja**: Mayor carga DB al contar total de registros.

```php
// Cambiar de:
->cursorPaginate(15)

// A:
->paginate(15)
```

### Opción 2: Mantener `cursorPaginate()` y actualizar respuesta meta
**Ventaja**: Mejor rendimiento para grandes datasets (no cuenta total).  
**Desventaja**: Requiere cambio en estructura de respuesta API (breaking change para clientes).

```php
// Eliminar meta incompatible y usar:
'meta' => [
    'per_page' => $items->perPage(),
    'next_cursor' => $items->nextCursor()?->encode(),
    'prev_cursor' => $items->previousCursor()?->encode(),
    'has_more' => $items->hasMorePages()
]
```

## Decisión Recomendada
**Opción 1** para mantener compatibilidad con frontend existente y evitar breaking changes.

## Implementación
1. Búsqueda y reemplazo: `->cursorPaginate(` → `->paginate(`
2. Verificar tests que validen estructura de respuesta.
3. Commit: "fix: cambiar cursorPaginate a paginate para compatibilidad con meta total/current_page".

## Alternativa (Futuro)
Evaluar migración gradual a cursor-based pagination para endpoints con grandes volúmenes (logs, transacciones) una vez frontend soporte nueva estructura meta.
