# 🛠️ Generador de Módulos ERP

**Comando:** `php artisan make:erp-module`

El generador de módulos permite crear rápidamente todos los componentes necesarios para un nuevo módulo de negocio en la API, siguiendo los patrones y estándares del proyecto.

## 🚀 Uso Básico

```bash
# Crear un módulo con campos por defecto
php artisan make:erp-module MiModulo

# Con campos personalizados
php artisan make:erp-module OrdenTrabajo \
  --fields="cliente_id:foreignId,titulo:string,descripcion:text:nullable,monto:decimal"

# Con relaciones
php artisan make:erp-module Factura \
  --fields="cliente_id:foreignId,total:decimal,fecha:date" \
  --relations="Cliente:belongsTo,Productos:hasMany"
```

## 📦 Componentes Generados

Por cada módulo, se generan automáticamente:

| # | Componente | Ubicación | Descripción |
|---|------------|-----------|-------------|
| 1 | **Modelo** | `app/Models/X.php` | Con traits multi-tenant, soft deletes, auditoría |
| 2 | **Controller** | `app/Http/Controllers/API/XController.php` | CRUD completo, cache Redis, OpenAPI docs |
| 3 | **Policy** | `app/Policies/XPolicy.php` | RBAC extendiendo BasePolicy |
| 4 | **StoreRequest** | `app/Http/Requests/X/StoreXRequest.php` | Validaciones para crear |
| 5 | **UpdateRequest** | `app/Http/Requests/X/UpdateXRequest.php` | Validaciones para actualizar |
| 6 | **Resource** | `app/Http/Resources/XResource.php` | Transformación de respuestas |
| 7 | **Migration** | `database/migrations/xxxx_create_x_table.php` | Con campos de auditoría |
| 8 | **Factory** | `database/factories/XFactory.php` | Para testing con Faker |
| 9 | **Test** | `tests/Feature/XTest.php` | Tests CRUD + autenticación |

## 🔧 Opciones Disponibles

```bash
php artisan make:erp-module <Nombre> [opciones]
```

| Opción | Descripción | Ejemplo |
|--------|-------------|---------|
| `--fields` | Campos del modelo | `--fields="nombre:string,precio:decimal"` |
| `--relations` | Relaciones | `--relations="Cliente:belongsTo"` |
| `--no-migration` | No crear migración | - |
| `--no-factory` | No crear factory | - |
| `--no-test` | No crear tests | - |
| `--no-routes` | No agregar rutas | - |
| `--force` | Sobrescribir existentes | - |

## 📝 Tipos de Campo Soportados

| Tipo | SQL | Ejemplo |
|------|-----|---------|
| `string` | VARCHAR(255) | `nombre:string` |
| `text` | TEXT | `descripcion:text` |
| `integer` | INT | `cantidad:integer` |
| `bigInteger` | BIGINT | `total:bigInteger` |
| `decimal` | DECIMAL(15,2) | `precio:decimal` |
| `float` | FLOAT | `porcentaje:float` |
| `boolean` | TINYINT(1) | `activo:boolean` |
| `date` | DATE | `fecha_inicio:date` |
| `datetime` | DATETIME | `procesado_en:datetime` |
| `timestamp` | TIMESTAMP | `verificado_en:timestamp` |
| `json` | JSON | `metadata:json` |
| `email` | VARCHAR(255) | `correo:email` |
| `foreignId` | BIGINT UNSIGNED | `cliente_id:foreignId` |

### Modificadores

- `:nullable` - Permite valores nulos
- `:default:valor` - Valor por defecto

```bash
--fields="descripcion:text:nullable,estado:string:default:pendiente,activo:boolean:default:true"
```

## 🔗 Tipos de Relación Soportados

| Tipo | Descripción |
|------|-------------|
| `belongsTo` | Pertenece a (N:1) |
| `hasMany` | Tiene muchos (1:N) |
| `hasOne` | Tiene uno (1:1) |
| `belongsToMany` | Muchos a muchos (N:N) |

```bash
--relations="Empresa:belongsTo,Cliente:belongsTo,Productos:hasMany"
```

## 📋 Ejemplo Completo

```bash
php artisan make:erp-module OrdenTrabajo \
  --fields="cliente_id:foreignId,titulo:string,descripcion:text:nullable,fecha_inicio:date,fecha_fin:date:nullable,estado:string:default:pendiente,monto_total:decimal,prioridad:string:default:media" \
  --relations="Cliente:belongsTo,Empleado:belongsTo"
```

### Output esperado:

```
🚀 Generando módulo ERP: OrdenTrabajo

📋 Resumen del módulo a generar:
+---------------+--------------------------------------------------------------+
| Componente    | Archivo                                                      |
+---------------+--------------------------------------------------------------+
| Modelo        | app/Models/OrdenTrabajo.php                                  |
| Controller    | app/Http/Controllers/API/OrdenTrabajoController.php          |
| Policy        | app/Policies/OrdenTrabajoPolicy.php                          |
| StoreRequest  | app/Http/Requests/OrdenTrabajo/StoreOrdenTrabajoRequest.php  |
| UpdateRequest | app/Http/Requests/OrdenTrabajo/UpdateOrdenTrabajoRequest.php |
| Resource      | app/Http/Resources/OrdenTrabajoResource.php                  |
| Migration     | database/migrations/xxxx_create_orden_trabajos_table.php     |
| Factory       | database/factories/OrdenTrabajoFactory.php                   |
| Test          | tests/Feature/OrdenTrabajoTest.php                           |
+---------------+--------------------------------------------------------------+

📦 Campos:
   • empresa_id: foreignId
   • cliente_id: foreignId
   • titulo: string
   ...

🎉 ¡Módulo generado exitosamente!
```

## ✅ Pasos Post-Generación

Después de generar un módulo, debes completar estos pasos:

### 1. Ejecutar migración

```bash
php artisan migrate
```

### 2. Registrar Policy

Agregar en `app/Providers/AuthServiceProvider.php`:

```php
use App\Models\OrdenTrabajo;
use App\Policies\OrdenTrabajoPolicy;

protected $policies = [
    // ... otras policies
    OrdenTrabajo::class => OrdenTrabajoPolicy::class,
];
```

### 3. Agregar permisos al Seeder

En `database/seeders/PermisosSeeder.php`:

```php
$modulos[] = 'orden-trabajos';
```

Esto creará automáticamente:
- `ver-orden-trabajos`
- `crear-orden-trabajos`
- `editar-orden-trabajos`
- `eliminar-orden-trabajos`

### 4. Ejecutar tests

```bash
php artisan test --filter=OrdenTrabajo
```

### 5. Revisar y ajustar

- Revisar validaciones en FormRequests
- Ajustar relaciones en el modelo si es necesario
- Personalizar Resource para campos específicos

## 🛡️ Características de Seguridad

El código generado incluye automáticamente:

- ✅ **Multi-tenancy**: Filtro por `empresa_id` del usuario
- ✅ **RBAC**: Autorización via Policy para cada acción
- ✅ **Soft Deletes**: Campo `eliminado` en lugar de borrado físico
- ✅ **Auditoría**: Campos `creado_por`, `actualizado_por`, `creado_en`, `actualizado_en`
- ✅ **Cache**: Redis cache con invalidación automática

## 🔄 Endpoints Generados

Para un módulo `OrdenTrabajo`, se crean estos endpoints:

| Método | Endpoint | Acción | Permiso |
|--------|----------|--------|---------|
| GET | `/api/orden-trabajos` | Listar todos | `ver-orden-trabajos` |
| POST | `/api/orden-trabajos` | Crear nuevo | `crear-orden-trabajos` |
| GET | `/api/orden-trabajos/{id}` | Ver uno | `ver-orden-trabajos` |
| PUT | `/api/orden-trabajos/{id}` | Actualizar | `editar-orden-trabajos` |
| DELETE | `/api/orden-trabajos/{id}` | Eliminar | `eliminar-orden-trabajos` |

## 💡 Tips

1. **Nombres en PascalCase**: Usa `OrdenTrabajo`, no `orden_trabajo`
2. **Plural automático**: El sistema pluraliza automáticamente para rutas y tablas
3. **Empresa siempre incluida**: `empresa_id` se agrega automáticamente para multi-tenancy
4. **`activo` por defecto**: Si no especificas `activo`, se agrega con valor `true`

---

**Última actualización:** 5 de Diciembre 2025
