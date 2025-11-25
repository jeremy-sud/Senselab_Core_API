# Guía de Multi‑Tenancy (Empresa)

## Objetivo
Estandarizar el acceso y scoping por `empresa_id` en controladores y modelos, evitando usos directos de `auth()->user()->empresa_id` y `$request->user()->empresa_id`, mejorando seguridad y consistencia.

## Trait `HasEmpresaContext`
Ubicación: `app/Traits/HasEmpresaContext.php`

- `getEmpresaId(): ?int` — Obtiene el `empresa_id` del usuario autenticado (Sanctum), con null‑safety.
- `scopeEmpresa(Builder $query, ?int $empresaId = null): Builder` — Aplica where `empresa_id` al query.
- `assertEmpresa(Model $model, ?int $empresaId = null): void` — Verifica pertenencia del modelo a la empresa actual; lanza 403 si no coincide.

### Uso en controladores
```php
use App\Traits\HasEmpresaContext;

class EjemploController extends Controller {
    use HasEmpresaContext;

    public function index(Request $request) {
        $empresaId = $this->getEmpresaId();
        $items = Modelo::where('empresa_id', $empresaId)->paginate(15);
        return ModeloResource::collection($items);
    }

    public function show(int $id) {
        $item = Modelo::findOrFail($id);
        $this->assertEmpresa($item);
        return new ModeloResource($item);
    }
}
```

## Patrón recomendado
- Lectura: usar `$this->getEmpresaId()` y/o `scopeEmpresa($query)`.
- Validación de acceso a registros concretos: `assertEmpresa($model)` antes de operar.
- Evitar acoplamiento directo al request o a `Auth` en cada método; centralizar en el trait.

## Migración de código (plan)
1. Reemplazar patrones:
   - `auth('sanctum')->user()->empresa_id` → `$this->getEmpresaId()`
   - `$request->user()->empresa_id` → `$this->getEmpresaId()`
2. Añadir `use HasEmpresaContext` en controladores; mantener `HasCacheableQueries` cuando aplique.
3. Donde haya `firstOrFail()` y luego lógica sensible, aplicar `assertEmpresa($model)` para reforzar pertenencia.
4. Ejecutar PHPStan nivel 6 para validar (firmas de métodos y tipos).

## Consideraciones
- Si `getEmpresaId()` retorna null, se lanzará 403 al usar `assertEmpresa`. Para listados, si empresa es null, decidir comportamiento (normalmente 403 o lista vacía).
- Mantener uso de `paginate()` para compatibilidad con meta `current_page` y `total`.

## Estado actual
- Trait creado y aplicado en `CuentaPorCobrarController` (piloto), sin errores nuevos en PHPStan.
- Paginación: migrado `cursorPaginate` → `paginate` en controladores para compatibilidad.

## Próximos pasos
- Refactor masivo en controladores restantes siguiendo este patrón.
- Documentar reglas de negocio específicas que dependan de empresa (políticas, scopes globales, etc.).
