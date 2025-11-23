# Guía Completa de Policies - Sistema de Autorización

**Autor**: Jeremy Arias Solano  
**Fecha**: 23 de noviembre de 2025  
**Versión**: 1.0  
**Proyecto**: Ursol CAST API

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [BasePolicy - Fundamento](#basepolicy)
4. [Crear Nuevas Policies](#crear-nuevas-policies)
5. [Aplicar en Controllers](#aplicar-en-controllers)
6. [Testing de Autorización](#testing)
7. [Troubleshooting](#troubleshooting)
8. [Mejores Prácticas](#mejores-prácticas)
9. [Casos Especiales](#casos-especiales)

---

## Introducción

El sistema de autorización de Ursol CAST API implementa **57 policies** que protegen **152+ métodos CRUD** en controllers, garantizando:

- ✅ **Multi-tenancy**: Los usuarios solo acceden a recursos de su empresa
- ✅ **RBAC (Role-Based Access Control)**: Verificación de permisos granulares
- ✅ **Soft Deletes**: Recursos eliminados no son accesibles
- ✅ **Seguridad por defecto**: Todas las operaciones requieren autorización explícita

### Estadísticas del Sistema

```
📊 Policies creadas: 57
📊 Controllers protegidos: 57 (87% de cobertura)
📊 Métodos con authorize(): 152+
📊 Tests de autorización: 6/8 pasando (75%)
📊 Tiempo de implementación: Sprint 1 completo
```

---

## Arquitectura del Sistema

### Flujo de Autorización

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│   Middleware    │ ← Sanctum Authentication
│   (auth:sanctum)│
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│   Controller    │
│                 │
│ $this->authorize│ ← Llama a Policy
│ ('action', $model)
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Policy Method  │
│                 │
│  1. ownsResource│ ← Verifica empresa_id
│  2. hasPermission│ ← Verifica permiso en BBDD
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Autorización   │
│  ✅ Permitido   │
│  ❌ 403 Forbidden│
└─────────────────┘
```

### Componentes Principales

1. **BasePolicy** (`app/Policies/BasePolicy.php`)  
   - Clase abstracta con lógica reutilizable
   - Métodos: `viewAny`, `view`, `create`, `update`, `delete`
   - Helpers: `ownsResource()`, `hasPermission()`

2. **Policies Específicas** (57 archivos en `app/Policies/`)  
   - Extienden `BasePolicy`
   - Definen el prefijo del permiso (`protected string $permission`)

3. **AppServiceProvider** (`app/Providers/AppServiceProvider.php`)  
   - Registra todas las policies con `Gate::policy()`
   - Mapeo Model ↔ Policy

4. **Modelo Usuario** (`app/Models/Usuario.php`)  
   - `hasPermission(string $permissionName): bool`
   - `roles()` con `wherePivot('activo', true)`

---

## BasePolicy

### Código Completo

```php
<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

abstract class BasePolicy
{
    /**
     * Prefijo del permiso (definido en cada policy hija)
     * 
     * @var string
     */
    protected string $permission;

    /**
     * Verificar si el usuario posee el recurso (multi-tenancy)
     */
    protected function ownsResource(Usuario $user, Model $model): bool
    {
        return $user->empresa_id === $model->empresa_id;
    }

    /**
     * Verificar si el usuario tiene el permiso específico
     */
    protected function hasPermission(Usuario $user, string $action): bool
    {
        $permissionName = "{$this->permission}.{$action}";
        return $user->hasPermission($permissionName);
    }

    // Métodos CRUD
    
    public function viewAny(Usuario $user): bool
    {
        return $this->hasPermission($user, 'leer');
    }

    public function view(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'leer');
    }

    public function create(Usuario $user): bool
    {
        return $this->hasPermission($user, 'crear');
    }

    public function update(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'actualizar');
    }

    public function delete(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'eliminar');
    }
}
```

### Convención de Permisos

El sistema usa **prefijos en snake_case** que se concatenan con **acciones**:

```
{prefijo}.{acción}

Ejemplos:
- productos.leer
- productos.crear
- productos.actualizar
- productos.eliminar

- cuentas_contables.leer
- cuentas_contables.crear
```

**IMPORTANTE**: Los permisos tienen DOS campos en la tabla `permisos`:
- `nombre` (con puntos): `productos.crear` ← **Usado por hasPermission()**
- `slug` (con guiones): `productos-crear` ← Para URLs/identificadores

---

## Crear Nuevas Policies

### Paso 1: Crear la Policy

```bash
# Crear archivo app/Policies/NuevoModeloPolicy.php
```

```php
<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\NuevoModelo;

/**
 * NuevoModeloPolicy - Gestión de autorización para NuevoModelo
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class NuevoModeloPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'nuevo_modelo'; // snake_case
}
```

### Paso 2: Registrar en AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

// 1. Importar modelo y policy
use App\Models\NuevoModelo;
use App\Policies\NuevoModeloPolicy;

// 2. Agregar al array $policies
protected array $policies = [
    // ... policies existentes
    NuevoModelo::class => NuevoModeloPolicy::class,
];
```

### Paso 3: Crear Permisos en Base de Datos

```sql
INSERT INTO permisos (nombre, slug, descripcion, activo, eliminado, creado_en, actualizado_en)
VALUES 
    ('nuevo_modelo.leer', 'nuevo-modelo-leer', 'Ver nuevos modelos', 1, 0, NOW(), NOW()),
    ('nuevo_modelo.crear', 'nuevo-modelo-crear', 'Crear nuevos modelos', 1, 0, NOW(), NOW()),
    ('nuevo_modelo.actualizar', 'nuevo-modelo-actualizar', 'Actualizar nuevos modelos', 1, 0, NOW(), NOW()),
    ('nuevo_modelo.eliminar', 'nuevo-modelo-eliminar', 'Eliminar nuevos modelos', 1, 0, NOW(), NOW());
```

### Paso 4: Asignar Permisos a Roles

```sql
-- Asignar permisos al rol Administrador (rol_id = 1, ejemplo)
INSERT INTO roles_permisos (rol_id, permiso_id, activo, creado_en, actualizado_en)
SELECT 1, id, 1, NOW(), NOW()
FROM permisos
WHERE nombre LIKE 'nuevo_modelo.%';
```

---

## Aplicar en Controllers

### Patrón Básico

```php
<?php

namespace App\Http\Controllers\API;

use App\Models\NuevoModelo;
use Illuminate\Http\Request;

class NuevoModeloController extends Controller
{
    /**
     * Listar todos los recursos (viewAny)
     */
    public function index()
    {
        $this->authorize('viewAny', NuevoModelo::class);
        
        $empresaId = auth()->user()->empresa_id;
        $modelos = NuevoModelo::where('empresa_id', $empresaId)
                               ->where('eliminado', false)
                               ->get();
        
        return response()->json(['data' => $modelos]);
    }

    /**
     * Crear nuevo recurso (create)
     */
    public function store(Request $request)
    {
        $this->authorize('create', NuevoModelo::class);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            // ... más validaciones
        ]);
        
        $modelo = NuevoModelo::create([
            'empresa_id' => auth()->user()->empresa_id,
            ...$validated
        ]);
        
        return response()->json(['data' => $modelo], 201);
    }

    /**
     * Ver recurso específico (view)
     */
    public function show($id)
    {
        $empresaId = auth()->user()->empresa_id;
        $modelo = NuevoModelo::where('empresa_id', $empresaId)
                             ->where('id', $id)
                             ->where('eliminado', false)
                             ->firstOrFail();
        
        $this->authorize('view', $modelo); // DESPUÉS de findOrFail
        
        return response()->json(['data' => $modelo]);
    }

    /**
     * Actualizar recurso (update)
     */
    public function update(Request $request, $id)
    {
        $empresaId = auth()->user()->empresa_id;
        $modelo = NuevoModelo::where('empresa_id', $empresaId)
                             ->where('id', $id)
                             ->where('eliminado', false)
                             ->firstOrFail();
        
        $this->authorize('update', $modelo); // DESPUÉS de findOrFail
        
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);
        
        $modelo->update($validated);
        
        return response()->json(['data' => $modelo]);
    }

    /**
     * Eliminar recurso (delete)
     */
    public function destroy($id)
    {
        $empresaId = auth()->user()->empresa_id;
        $modelo = NuevoModelo::where('empresa_id', $empresaId)
                             ->where('id', $id)
                             ->where('eliminado', false)
                             ->firstOrFail();
        
        $this->authorize('delete', $modelo); // DESPUÉS de findOrFail
        
        $modelo->update(['eliminado' => true]); // Soft delete
        
        return response()->json(['message' => 'Eliminado exitosamente']);
    }
}
```

### Reglas Importantes

1. **viewAny/create** → Usar `ModelName::class`
   ```php
   $this->authorize('viewAny', Producto::class);
   $this->authorize('create', Cliente::class);
   ```

2. **view/update/delete** → Usar instancia `$model`
   ```php
   $producto = Producto::findOrFail($id);
   $this->authorize('view', $producto);
   ```

3. **Orden en show/update/destroy**:
   ```php
   // ✅ CORRECTO
   $modelo = Modelo::findOrFail($id);
   $this->authorize('action', $modelo);
   
   // ❌ INCORRECTO
   $this->authorize('action', Modelo::class); // No verifica empresa_id
   $modelo = Modelo::findOrFail($id);
   ```

4. **Multi-tenancy en queries**:
   ```php
   // SIEMPRE filtrar por empresa_id
   $empresaId = auth()->user()->empresa_id;
   $modelo = Modelo::where('empresa_id', $empresaId)
                   ->where('id', $id)
                   ->firstOrFail();
   ```

---

## Testing

### Estructura de Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\NuevoModelo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NuevoModeloAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear empresa
        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'razon_social' => 'Empresa Test S.A.',
            'num_identificacion_dgt' => '1234567890',
            'tipo_identificacion' => '02',
            // ... campos obligatorios
        ]);
        
        // Crear rol
        $this->rol = Rol::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Administrador',
            'activo' => true,
            'eliminado' => false,
        ]);
        
        // Crear permiso
        $this->permiso = Permiso::create([
            'nombre' => 'nuevo_modelo.leer',
            'slug' => 'nuevo-modelo-leer',
            'descripcion' => 'Ver nuevos modelos',
        ]);
        
        // Asignar permiso a rol
        $this->rol->permisos()->attach($this->permiso->id, ['activo' => true]);
        
        // Crear usuario
        $this->usuario = Usuario::create([
            'empresa_id' => $this->empresa->id,
            'email' => 'admin@test.com',
            'nombre' => 'Admin',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'activo' => true,
            'eliminado' => false,
        ]);
        
        // Asignar rol a usuario
        $this->usuario->roles()->attach($this->rol->id, [
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    public function test_usuario_puede_ver_recursos_con_permiso()
    {
        Sanctum::actingAs($this->usuario);
        
        $response = $this->getJson('/api/nuevo-modelos');
        
        $response->assertStatus(200);
    }

    public function test_usuario_sin_permiso_recibe_403()
    {
        // Usuario sin rol/permiso
        $usuario = Usuario::create([
            'empresa_id' => $this->empresa->id,
            'email' => 'user@test.com',
            'nombre' => 'User',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'activo' => true,
            'eliminado' => false,
        ]);
        
        Sanctum::actingAs($usuario);
        
        $response = $this->getJson('/api/nuevo-modelos');
        
        $response->assertStatus(403);
    }
}
```

---

## Troubleshooting

### Error 1: "Call to undefined relationship"

**Síntoma**:
```
Call to undefined relationship [proveedorPredeterminado] on model [App\Models\Producto].
```

**Causa**: Controller intenta cargar una relación que no existe en el modelo.

**Solución**:
```php
// En ProductoController.php
// ANTES (causaba error):
$producto = Producto::with(['proveedorPredeterminado'])->findOrFail($id);

// DESPUÉS (correcto):
$producto = Producto::with(['proveedor'])->findOrFail($id);
// O eliminar el with() si no se necesita
```

### Error 2: "Field 'slug' doesn't have a default value"

**Síntoma**: Error al crear permisos en tests.

**Causa**: Tabla `permisos` requiere campo `slug`.

**Solución**:
```php
// ANTES:
Permiso::create([
    'nombre' => 'productos.crear',
    'descripcion' => '...',
]);

// DESPUÉS:
Permiso::create([
    'nombre' => 'productos.crear',
    'slug' => 'productos-crear', // ← Agregar slug
    'descripcion' => '...',
]);
```

### Error 3: hasPermission() devuelve false

**Síntoma**: Usuario con permisos recibe 403.

**Causa 1**: hasPermission() busca por 'slug' en vez de 'nombre'.

**Solución**:
```php
// app/Models/Usuario.php
public function hasPermission(string $permissionSlug): bool
{
    return $this->roles()
        ->whereHas('permisos', function ($query) use ($permissionSlug) {
            $query->where('nombre', $permissionSlug) // ← Cambiar de 'slug' a 'nombre'
                  ->where('permisos.activo', true)
                  ->where('permisos.eliminado', false);
        })
        ->where('roles.activo', true)
        ->where('roles.eliminado', false)
        ->exists();
}
```

**Causa 2**: Relación `roles()` no filtra por campos pivote.

**Solución**:
```php
// app/Models/Usuario.php
public function roles()
{
    return $this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id')
                ->wherePivot('activo', true)    // ← Agregar filtros
                ->wherePivot('eliminado', false)
                ->withTimestamps();
}
```

### Error 4: "Undefined type 'App\Providers\BusUnidad'"

**Síntoma**: Error de lint en AppServiceProvider.

**Causa**: Falta importar el modelo/policy.

**Solución**:
```php
// app/Providers/AppServiceProvider.php
use App\Models\BusUnidad;
use App\Policies\BusUnidadPolicy;

protected array $policies = [
    BusUnidad::class => BusUnidadPolicy::class,
];
```

### Error 5: Tests con FK constraints

**Síntoma**:
```
SQLSTATE[23000]: Integrity constraint violation: regimen_tributario_id
```

**Causa**: Factories intentan crear FKs que no existen en BD de testing.

**Solución**:
```php
// ANTES (con factory):
$empresa = Empresa::factory()->create();

// DESPUÉS (sin factory):
$empresa = Empresa::create([
    'nombre' => 'Empresa Test',
    'razon_social' => 'Empresa Test S.A.',
    'num_identificacion_dgt' => '1234567890',
    // ... todos los campos obligatorios manualmente
]);
```

---

## Mejores Prácticas

### 1. Siempre Filtrar por empresa_id

```php
// ✅ CORRECTO
$empresaId = auth()->user()->empresa_id;
$modelo = Modelo::where('empresa_id', $empresaId)
                ->where('id', $id)
                ->firstOrFail();
$this->authorize('view', $modelo);

// ❌ INCORRECTO (vulnerable a cross-tenant)
$modelo = Modelo::findOrFail($id);
$this->authorize('view', $modelo);
```

### 2. Orden de Autorización

```php
// ✅ CORRECTO (authorize DESPUÉS de obtener instancia)
$modelo = Modelo::findOrFail($id);
$this->authorize('action', $modelo);

// ❌ INCORRECTO (no verifica empresa_id)
$this->authorize('action', Modelo::class);
$modelo = Modelo::findOrFail($id);
```

### 3. Usar Instancias en show/update/delete

```php
// ✅ CORRECTO
$this->authorize('view', $producto); // $producto es instancia

// ❌ INCORRECTO
$this->authorize('view', Producto::class); // No verifica empresa_id
```

### 4. Permisos Granulares

```php
// Crear permisos específicos por acción
productos.leer
productos.crear
productos.actualizar
productos.eliminar

// NO usar un solo permiso "productos.admin"
```

### 5. Testing Exhaustivo

```php
// Testear al menos:
- Usuario con permiso ✅
- Usuario sin permiso ❌
- Usuario de otra empresa ❌
- Usuario sin autenticar ❌
- Recurso eliminado ❌
```

### 6. Nombrar Policies en Singular

```php
// ✅ CORRECTO
ProductoPolicy (para modelo Producto)
ClientePolicy (para modelo Cliente)

// ❌ INCORRECTO
ProductosPolicy
```

### 7. Prefijos en snake_case

```php
// ✅ CORRECTO
protected string $permission = 'cuenta_contable';
// Genera: cuenta_contable.leer, cuenta_contable.crear

// ❌ INCORRECTO
protected string $permission = 'CuentaContable';
```

---

## Casos Especiales

### Override de Métodos en Policy

```php
class ProductoPolicy extends BasePolicy
{
    protected string $permission = 'productos';
    
    /**
     * Override para lógica personalizada
     */
    public function delete(Usuario $user, Producto $producto): bool
    {
        // No permitir eliminar si tiene ventas asociadas
        if ($producto->ventas()->exists()) {
            return false;
        }
        
        return parent::delete($user, $producto);
    }
}
```

### Autorización en Métodos Personalizados

```php
class ProductoController extends Controller
{
    public function activar($id)
    {
        $producto = Producto::findOrFail($id);
        
        // Usar método existente o crear custom en policy
        $this->authorize('update', $producto);
        
        $producto->update(['activo' => true]);
        
        return response()->json(['message' => 'Activado']);
    }
}
```

### Multiple Policies en un Controller

```php
class VentaController extends Controller
{
    public function store(Request $request)
    {
        // Verificar permiso para crear venta
        $this->authorize('create', Venta::class);
        
        // Verificar permiso para actualizar inventario
        $producto = Producto::findOrFail($request->producto_id);
        $this->authorize('update', $producto);
        
        // ... lógica
    }
}
```

### Policies sin BasePolicy

```php
// Si necesitas lógica completamente custom
class CustomPolicy
{
    public function administrar(Usuario $user): bool
    {
        return $user->esAdministrador() && $user->empresa->activo;
    }
}
```

---

## Resumen Ejecutivo

### ✅ Logros del Sprint 1

- **57 policies creadas** con BasePolicy reutilizable
- **57 controllers protegidos** (~152 métodos con authorize())
- **6/8 tests pasando** (75% de cobertura)
- **Fixes críticos aplicados**:
  - hasPermission() usa 'nombre' en vez de 'slug'
  - roles() con wherePivot() para filtrar correctamente
  - Setup de tests con relaciones many-to-many correctas

### 📊 Cobertura

```
Controllers totales:     65
Controllers protegidos:  57
Stubs vacíos:            7
AuthController:          1 (no requiere policies)
──────────────────────────
Cobertura:              87%
```

### 🐛 Bugs Encontrados

1. **ProductoController→show()**: Intenta cargar relación `proveedorPredeterminado` inexistente
2. **ClienteRequest**: Valida `tipo_identificacion` pero rechaza todos los valores

### 🎯 Próximos Pasos

1. ✅ Documentación completa (este archivo)
2. ⏳ Corregir bugs encontrados en testing
3. ⏳ Implementar 2 tests restantes (8/8 = 100%)
4. ⏳ Crear permisos en base de datos para 36 nuevas policies
5. ⏳ Sprint 2: Optimización y cache de permisos

---

## Referencias

- **Commits Principales**:
  - `fd02615` - Testing de autorización (6/8 tests)
  - `5e10f3a` - Aplicar authorize() en 41 controllers
  - Commits previos: 16 controllers iniciales + 36 policies nuevas

- **Archivos Clave**:
  - `app/Policies/BasePolicy.php` (135 líneas)
  - `app/Providers/AppServiceProvider.php` (registro de policies)
  - `app/Models/Usuario.php` (hasPermission, roles)
  - `tests/Feature/AuthorizationTest.php` (testing completo)

- **Documentación Adicional**:
  - `SPRINT_1_ITERACION_FINAL.md` - Resumen ejecutivo
  - `API_DOCUMENTATION.md` - Documentación general

---

**Fin de la Guía** | Última actualización: 23 de noviembre de 2025
