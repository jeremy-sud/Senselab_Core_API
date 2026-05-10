# Guía de Multi‑Tenancy (Empresa)

## Objetivo
Estandarizar el acceso y scoping por `empresa_id` en controladores y modelos, evitando usos directos de `auth()->user()->empresa_id` y `$request->user()->empresa_id`, mejorando seguridad y consistencia.

## Resolución del tenant (Header/Subdominio)

- **Finder activo**: `App\Multitenancy\TenantFinder\HeaderSubdomainTenantFinder`.
- **Headers soportados**: `X-Empresa-Id`, `X-Tenant-Id`, `X-Tenant`. El valor debe ser el `id` numérico de la empresa.
- **Subdominios**: si la petición llega por `https://{subdominio}.api.senselab.com`, el finder comparará `{subdominio}` contra `empresas.subdominio` (configura `TENANT_BASE_DOMAIN` en `.env`).
- **Validación**: aun cuando se envía un header o subdominio distinto, `resolveEmpresaOrFail()` compara ese ID con el `empresa_id` del usuario autenticado para prevenir accesos cruzados.

```bash
curl -X GET https://tenant-123.api.senselab.com/api/ventas \
    -H "Authorization: Bearer $TOKEN"

# O con header explícito
curl -X GET http://localhost:8000/api/ventas \
    -H "Authorization: Bearer $TOKEN" \
    -H "X-Empresa-Id: 123"
```

## Trait `HasEmpresaContext`
Ubicación: `app/Traits/HasEmpresaContext.php`

- `getEmpresaId(): ?int` — Usa el usuario Sanctum o el tenant actual (de `HeaderSubdomainTenantFinder`).
- `scopeEmpresa(Builder $query, ?int $empresaId = null): Builder` — Aplica where `empresa_id` al query.
- `assertEmpresa(Model $model, ?int $empresaId = null): void` — Verifica pertenencia del modelo a la empresa actual; lanza 403 si no coincide.
- `resolveEmpresaOrFail(?int $requested): int` — Garantiza que el `empresa_id` solicitado (query/body) coincida con el tenant activo.

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
- El trait `BelongsToTenant` ahora lee tanto al usuario autenticado como al tenant actual para aplicar el scope `empresa_id` y auto-completar el campo en `creating`.
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
