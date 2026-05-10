# Relaciones de Modelos Eloquent - Senselab Core API

**Desarrollado por:** Senselab  
**Empresa:** Costa Rica | 30 años de experiencia  
**Proyecto:** Sistema ERP Multi-Tenant con Facturación Electrónica  
**Framework:** Laravel 11  
**Fecha:** 19 de noviembre de 2025

---

## ✅ Estado Actual

Se han configurado **65 modelos** con sus relaciones correspondientes y timestamps personalizados.

## 🔄 Configuración Global

### Timestamps Personalizados

Todos los modelos usan timestamps personalizados en lugar de los estándar de Laravel:

```php
const CREATED_AT = 'creado_en';
const UPDATED_AT = 'actualizado_en';
```

### Traits Implementados

#### CustomTimestamps
Trait para manejar timestamps personalizados de forma automática.

#### CustomSoftDeletes
Trait para soft deletes personalizados usando los campos:
- `activo` (boolean)
- `eliminado` (boolean)

**Métodos disponibles:**
- `delete()` - Marca como eliminado
- `restore()` - Restaura registro eliminado
- `forceDelete()` - Eliminación permanente
- `scopeWithDeleted()` - Incluir eliminados
- `scopeOnlyDeleted()` - Solo eliminados
- `scopeActivos()` - Solo activos
- `isDeleted()` - Verificar si está eliminado

## 📊 Modelos Principales y sus Relaciones

### 🏢 Empresa

**Tabla:** `empresas`

**Relaciones Uno a Muchos (hasMany):**
- `sucursales()` → Sucursal
- `usuarios()` → Usuario
- `empleados()` → Empleado
- `productos()` → Producto
- `clientes()` → Cliente
- `proveedores()` → Proveedor
- `almacenes()` → Almacen
- `ventas()` → Venta
- `ordenesCompra()` → OrdenCompra
- `categoriasProductos()` → CategoriaProducto
- `cuentasContables()` → CuentaContable
- `asientosContables()` → AsientoContable
- `presupuestos()` → Presupuesto
- `cajaChica()` → CajaChica
- `cuentasPorCobrar()` → CuentaPorCobrar
- `cuentasPorPagar()` → CuentaPorPagar
- `periodosNomina()` → PeriodoNomina
- `etiquetas()` → Etiqueta
- `rutas()` → Ruta
- `consecutivosFe()` → ConsecutivoFe
- `comprobantesRecibidos()` → ComprobanteRecibidoElectronico
- `configuraciones()` → Configuracion

**Relaciones Muchos a Uno (belongsTo):**
- `regimenTributario()` → RegimenTributario

---

### 🏪 Sucursal

**Tabla:** `sucursales`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `almacenes()` → Almacen (hasMany)
- `ventas()` → Venta (hasMany)
- `cajas()` → Caja (hasMany)
- `consecutivosFe()` → ConsecutivoFe (hasMany)

---

### 👤 Usuario

**Tabla:** `usuarios`

**Nota:** Este modelo fue modificado en **FASE 3** para implementar autenticación y RBAC.

**Herencia:**
```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    // ...
}
```

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `cargo()` → Cargo (belongsTo)
- `roles()` → Rol (belongsToMany, tabla pivote: `rol_usuario`)
- `ventas()` → Venta (hasMany)
- `ordenesCompra()` → OrdenCompra (hasMany)
- `asientosContables()` → AsientoContable (hasMany)

**Métodos RBAC (nuevos en FASE 3):**

```php
// Verificar si tiene un permiso específico
$usuario->hasPermission('empresas.crear'); // bool

// Verificar si tiene un rol específico
$usuario->hasRole('Administrador'); // bool

// Verificar si tiene uno de varios roles
$usuario->hasAnyRole(['Administrador', 'Gerente']); // bool

// Obtener todos los permisos del usuario (a través de sus roles)
$usuario->getAllPermissions(); // Collection
// ['empresas.crear', 'empresas.leer', 'empresas.actualizar', ...]

// Asignar roles al usuario
$usuario->assignRoles(['Vendedor', 'Cajero']);
```

**Autenticación:**
```php
// Obtener contraseña para autenticación
$usuario->getAuthPassword(); // Retorna $this->password_hash

// Campo personalizado para contraseña
protected $hidden = ['password_hash'];
```

**Notas:**
- Campo de contraseña: `password_hash` (no `password`)
- Soporta tokens Sanctum para API
- Implementa todos los métodos de `Authenticatable`
- Compatible con middleware `auth:sanctum`

---

### 🔐 Rol

**Tabla:** `roles`

**Nota:** Modelo mejorado en **FASE 3** para sistema RBAC.

**Relaciones:**
- `usuarios()` → Usuario (belongsToMany, tabla pivote: `rol_usuario`)
- `permisos()` → Permiso (belongsToMany, tabla pivote: `rol_permiso`)

**Métodos:**
```php
// Asignar permisos a un rol
$rol->assignPermissions(['empresas.crear', 'empresas.leer', ...]);
```

**Roles Predefinidos (7):**
1. **Administrador** - Todos los permisos (68)
2. **Gerente** - Gestión completa excepto configuraciones críticas
3. **Contador** - Módulos contables y financieros
4. **Vendedor** - Ventas, clientes, productos (solo lectura)
5. **Comprador** - Compras, proveedores, inventario
6. **Bodeguero** - Inventario, almacenes, productos
7. **Usuario** - Permisos básicos de lectura

**Uso:**
```php
// Obtener usuarios de un rol
$administradores = Rol::where('nombre', 'Administrador')
    ->first()
    ->usuarios;

// Obtener permisos de un rol
$permisos = Rol::find(1)->permisos;
```

---

### 🔑 Permiso

**Tabla:** `permisos`

**Nota:** Modelo mejorado en **FASE 3** para sistema RBAC.

**Campos clave:**
- `slug` - Identificador único del permiso (ej: `empresas.crear`)
- `nombre` - Nombre descriptivo (ej: "Crear Empresas")
- `modulo` - Módulo al que pertenece (ej: "empresas")
- `accion` - Acción permitida (ej: "crear", "leer", "actualizar", "eliminar")

**Relaciones:**
- `roles()` → Rol (belongsToMany, tabla pivote: `rol_permiso`)

**Estructura de Permisos (68 total):**
```
{modulo}.{accion}

Módulos (17): empresas, sucursales, almacenes, productos, 
              categorias_producto, clientes, proveedores, ventas, 
              compras, inventario, cuentas_contables, 
              asientos_contables, empleados, nomina, rutas, 
              buses, facturacion_electronica

Acciones (4): crear, leer, actualizar, eliminar

Total: 17 × 4 = 68 permisos
```

**Ejemplos:**
- `empresas.crear` - Crear empresas
- `ventas.leer` - Ver/listar ventas
- `productos.actualizar` - Modificar productos
- `empleados.eliminar` - Eliminar empleados

**Uso:**
```php
// Obtener todos los permisos de un módulo
$permisosEmpresas = Permiso::where('modulo', 'empresas')->get();

// Obtener roles que tienen un permiso
$roles = Permiso::where('slug', 'empresas.crear')
    ->first()
    ->roles;
```

---

### 👥 Cliente

**Tabla:** `clientes`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `ventas()` → Venta (hasMany)
- `cuentasPorCobrar()` → CuentaPorCobrar (hasMany)
- `salidasInventario()` → SalidaInventario (hasMany)

**Scopes:**
- `activos()`
- `porNombre($nombre)`
- `porIdentificacion($identificacion)`
- `porTipoIdentificacion($tipo)`
- `porEmail($email)`

**Métodos auxiliares:**
- `getNombreCompletoAttribute()` - Nombre completo
- `getTipoIdentificacionDescripcionAttribute()` - Descripción del tipo de ID
- `tieneIdentificacionValida()` - Validar para facturación electrónica

---

### 🏭 Proveedor

**Tabla:** `proveedores`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `productos()` → Producto (hasMany - como proveedor predeterminado)
- `ordenesCompra()` → OrdenCompra (hasMany)
- `cuentasPorPagar()` → CuentaPorPagar (hasMany)
- `entradasInventario()` → EntradaInventario (hasMany)
- `comprobantesRecibidos()` → ComprobanteRecibidoElectronico (hasMany)

---

### 📦 Producto

**Tabla:** `productos`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `categoria()` → CategoriaProducto (belongsTo)
- `unidadMedida()` → UnidadMedida (belongsTo)
- `marca()` → Marca (belongsTo)
- `proveedorPredeterminado()` → Proveedor (belongsTo)
- `impuesto()` → TipoImpuesto (belongsTo)
- `cabys()` → Cabys (belongsTo)

**Scopes:**
- `activos()`
- `porEmpresa($empresaId)`
- `porCategoria($categoriaId)`
- `porTipo($tipo)`

---

### 🛒 Venta

**Tabla:** `ventas`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `sucursal()` → Sucursal (belongsTo)
- `cliente()` → Cliente (belongsTo)
- `usuario()` → Usuario (belongsTo)
- `formaPago()` → FormaPago (belongsTo)
- `detalles()` → DetalleVenta (hasMany)
- `cuentasPorCobrar()` → CuentaPorCobrar (hasMany)
- `salidasInventario()` → SalidaInventario (hasMany)

---

### 📝 OrdenCompra

**Tabla:** `ordenes_compra`

**Relaciones:**
- `empresa()` → Empresa (belongsTo)
- `proveedor()` → Proveedor (belongsTo)
- `usuario()` → Usuario (belongsTo)
- `detalles()` → DetalleOrdenCompra (hasMany)
- `pagos()` → Pago (hasMany)
- `cuentasPorPagar()` → CuentaPorPagar (hasMany)
- `entradasInventario()` → EntradaInventario (hasMany)

**Métodos auxiliares:**
- `calcularSaldoPendiente()` - Calcula monto pendiente de pagar

**Scopes:**
- `activas()`
- `porProveedor($proveedorId)`
- `porEmpresa($empresaId)`
- `pendientes()`

---

## 🔗 Relaciones por Tipo

### Relaciones Polimórficas

#### EntidadEtiqueta
Sistema de etiquetado polimórfico que permite etiquetar diferentes entidades:
- Clientes
- Productos
- Ventas
- Empleados
- etc.

```php
// Campos polimórficos
'entidad_tipo' => 'clientes',  // Nombre de la tabla
'entidad_id' => 123             // ID del registro
```

---

## 📋 Catálogos y Configuración

### Catálogos Base
- **RegimenTributario** - Regímenes tributarios
- **Cargo** - Cargos de empleados
- **TipoCuenta** - Tipos de cuentas contables
- **Rol** - Roles de usuario
- **FormaPago** - Formas de pago
- **UnidadMedida** - Unidades de medida
- **TipoImpuesto** - Tipos de impuestos
- **Marca** - Marcas de productos
- **CategoriaProducto** - Categorías de productos
- **Cabys** - Códigos CABYS (Costa Rica)

### Configuración Multi-tenant
Todos los modelos operacionales incluyen `empresa_id` para multi-tenancy.

---

## 🎯 Uso de Relaciones

### Ejemplo: Obtener ventas de un cliente con sus detalles

```php
$cliente = Cliente::with(['ventas.detalles.producto'])
    ->find($id);

foreach ($cliente->ventas as $venta) {
    echo "Venta #{$venta->numero_comprobante}\n";
    foreach ($venta->detalles as $detalle) {
        echo "- {$detalle->producto->nombre}: {$detalle->cantidad}\n";
    }
}
```

### Ejemplo: Crear una venta con detalles

```php
$venta = Venta::create([
    'empresa_id' => 1,
    'sucursal_id' => 1,
    'cliente_id' => $clienteId,
    'usuario_id' => auth()->id(),
    'fecha_venta' => now(),
    // ... otros campos
]);

$venta->detalles()->createMany([
    [
        'producto_id' => 1,
        'cantidad' => 2,
        'precio_unitario' => 1000,
        'subtotal' => 2000,
    ],
    [
        'producto_id' => 2,
        'cantidad' => 1,
        'precio_unitario' => 500,
        'subtotal' => 500,
    ],
]);
```

### Ejemplo: Eager Loading para optimizar consultas

```php
// ❌ Malo (N+1 queries)
$productos = Producto::all();
foreach ($productos as $producto) {
    echo $producto->categoria->nombre;  // Query por cada producto
}

// ✅ Bueno (2 queries)
$productos = Producto::with('categoria')->get();
foreach ($productos as $producto) {
    echo $producto->categoria->nombre;
}

// ✅ Mejor (con múltiples relaciones)
$productos = Producto::with([
    'categoria',
    'marca',
    'unidadMedida',
    'proveedorPredeterminado'
])->get();
```

### Ejemplo: Usar scopes

```php
// Obtener solo productos activos de una empresa
$productos = Producto::activos()
    ->porEmpresa($empresaId)
    ->porCategoria($categoriaId)
    ->get();

// Obtener clientes activos por nombre
$clientes = Cliente::activos()
    ->porNombre('Juan')
    ->get();

// Obtener órdenes pendientes de un proveedor
$ordenes = OrdenCompra::pendientes()
    ->porProveedor($proveedorId)
    ->with('detalles')
    ->get();
```

---

## 🧪 Testing con Relaciones

### Ejemplo de Factory con Relaciones

```php
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Cliente;

// Crear empresa con sucursales y productos
$empresa = Empresa::factory()
    ->has(Sucursal::factory()->count(2))
    ->has(Producto::factory()->count(10))
    ->has(Cliente::factory()->count(5))
    ->create();

// Crear venta con detalles
$venta = Venta::factory()
    ->for($empresa)
    ->for($empresa->sucursales->first(), 'sucursal')
    ->has(DetalleVenta::factory()->count(3))
    ->create();
```

---

## 📚 Documentación Adicional

- Todos los modelos incluyen validación básica en `$rules`
- Los modelos con multi-tenancy usan el trait `BelongsToTenant`
- Las relaciones están optimizadas para eager loading
- Se recomienda usar scopes para consultas complejas
- Los timestamps personalizados se manejan automáticamente

---

## ⚠️ Notas Importantes

1. **Timestamps:** Todos los modelos usan `creado_en` y `actualizado_en`
2. **Soft Deletes:** Se usa `eliminado` en lugar de `deleted_at`
3. **Multi-tenant:** Filtrar siempre por `empresa_id` en contexto multi-tenant
4. **Eager Loading:** Siempre usar `with()` para evitar problemas N+1
5. **Scopes:** Usar los scopes definidos para mantener consistencia
6. **Autenticación (FASE 3):**
   - Usuario extiende `Authenticatable` (no `Model`)
   - Usa `HasApiTokens` trait de Laravel Sanctum
   - Campo de contraseña: `password_hash` (no `password`)
   - Métodos RBAC disponibles: `hasPermission()`, `hasRole()`, etc.
7. **RBAC (FASE 3):**
   - 68 permisos granulares: 17 módulos × 4 acciones
   - 7 roles predefinidos con permisos configurables
   - Middleware `CheckPermission` para protección de rutas
   - Rol Administrador tiene todos los permisos automáticamente
8. **Middleware de permisos:**
   ```php
   // En rutas
   ->middleware('permission:empresas.crear')
   
   // En controladores
   if (!auth()->user()->hasPermission('ventas.crear')) {
       abort(403, 'No tienes permiso para realizar esta acción');
   }
   ```

---

---

## 📞 Soporte Técnico

Para consultas sobre modelos, relaciones o estructura de base de datos:

**Senselab**
- **Email Técnico**: deadmooncr@gmail.com
- **Email Corporativo**: deadmooncr@gmail.com
- **WhatsApp**: +(506)8973-5665
- **Desarrollador**: [Jeremy Arias Solano](https://github.com/jeremy-sud)
- **Documentación**: [Senselab Reposit for Developers](https://sites.google.com/view/repdevsenselab/home/repositorio)

---

*© 2025 Senselab - Todos los derechos reservados*  
*Desarrollado con el "Toque Humano" que nos caracteriza*
