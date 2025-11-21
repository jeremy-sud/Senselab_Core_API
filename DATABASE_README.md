# Base de Datos - Ursol CAST API

**Desarrollado por:** Sistemas Ursol S.A.  
**País:** Costa Rica | 30 años de experiencia tecnológica  
**Desarrollador:** Jeremy Arias Solano  
**Contacto:** sistemas@ursol.com | WhatsApp +506 8868-7765

---

## ✅ Estado Actual

**Base de datos completamente configurada y funcional - PRODUCCIÓN LISTA**

### Estadísticas Generales
- ✅ **65 tablas** en total (60 business + 5 estratégicas)
- ✅ **60 migraciones** ejecutadas exitosamente
- ✅ **112 registros** de datos iniciales cargados
- ✅ **4 índices FULLTEXT** para búsquedas avanzadas
- ✅ **14 índices compuestos** para optimización de queries
- ✅ **Sistema RBAC** completo (68 permisos + 8 roles)

### Migraciones
- ✅ **60 migraciones** ejecutadas (59 business + 1 Sanctum)
- ✅ **60 tablas business** creadas
- ✅ Todas las **foreign keys** configuradas correctamente
- ✅ Tipos de datos compatibles (INT UNSIGNED)
- ✅ **personal_access_tokens** (Laravel Sanctum) para autenticación API

### Tablas Estratégicas Adicionales (5)

Estas tablas se crean automáticamente por el sistema y no requieren migración:

1. **archivos** - Almacenamiento de archivos del sistema
2. **auditoria_actividades** - Registro de auditoría de acciones
3. **sesiones_usuarios** - Control de sesiones activas
4. **notificaciones** - Sistema de notificaciones
5. **configuraciones_api** - Configuraciones de la API

**Total tablas:** 60 (business) + 5 (estratégicas) = **65 tablas**

### Índices de Optimización

#### Índices FULLTEXT (4 tablas)
Mejoran significativamente las búsquedas de texto:

1. **productos** - `nombre`, `codigo`, `descripcion`, `codigo_barras`
2. **clientes** - `nombre`, `email`, `identificacion`
3. **proveedores** - `nombre`, `email`, `identificacion`
4. **empresas** - `nombre`, `nombre_comercial`, `identificacion`

**Uso:**
```sql
SELECT * FROM productos 
WHERE MATCH(nombre, codigo, descripcion) AGAINST('laptop' IN BOOLEAN MODE);
```

#### Índices Compuestos (14 índices)
Optimizan queries frecuentes con múltiples condiciones:

- **productos**: `empresa_id + activo`, `categoria_id + tipo`
- **clientes**: `empresa_id + activo`, `tipo_cliente + provincia`
- **proveedores**: `empresa_id + activo`
- **ventas**: `empresa_id + fecha`, `cliente_id + estado`
- **compras**: `empresa_id + fecha`, `proveedor_id + estado`
- **asientos_contables**: `empresa_id + fecha`, `periodo_id + tipo`
- **empleados**: `empresa_id + activo`, `departamento_id + cargo_id`
- **facturas_electronicas**: `empresa_id + clave`, `estado + fecha_emision`

### Seeders Implementados (9 total)

Se han creado seeders para poblar datos iniciales en las siguientes tablas:

#### Datos Maestros (6 seeders - 96 registros)

1. **RegimenesTributariosSeeder** - 2 registros
   - Régimen Tradicional
   - Régimen Simplificado

2. **FormasPagoSeeder** - 6 registros
   - Efectivo, Tarjeta, Transferencia, Cheque, Crédito, Otros

3. **TiposCuentasSeeder** - 8 registros
   - Activo Corriente, Activo No Corriente, Pasivo Corriente, Pasivo No Corriente, Patrimonio, Ingresos, Costos, Gastos

4. **UnidadesMedidaSeeder** - 11 registros
   - Unidad, Kilogramo, Gramo, Litro, Mililitro, Metro, Metro cuadrado, Metro cúbico, Caja, Paquete, Servicio

5. **PermisosSeeder** - 68 registros
   - Generación dinámica: 17 módulos × 4 acciones (crear, leer, actualizar, eliminar)
   - Módulos: empresas, sucursales, almacenes, productos, categorias_producto, clientes, proveedores, ventas, compras, inventario, cuentas_contables, asientos_contables, empleados, nomina, rutas, buses, facturacion_electronica

6. **RolesSeeder** - 8 registros
   - Administrador, Gerente, Contador, Vendedor, Comprador, Bodeguero, Usuario, Auditor
   - El rol Administrador se asigna automáticamente todos los 68 permisos

#### Datos Demo/Test (3 seeders - 16 registros)

7. **CargosSeeder** - 7 registros
   - Gerente General, Contador, Vendedor, Cajero, Bodeguero, Conductor, Asistente Administrativo

8. **EmpresaDemoSeeder** - 2 registros
   - 1 Empresa: "Sistemas Ursol S.A." (3-101-123456)
   - 1 Sucursal: "Oficina Central"

9. **UsuarioAdminSeeder** - 7 registros
   - 1 Usuario: admin@ursol.com (password: admin123)
   - 6 Relaciones rol_usuario (usuario con rol Administrador)
   - Asignación automática de todos los 68 permisos

**Total de registros:** **112** (96 datos maestros + 16 datos demo/test)

### Factories Implementadas

Se han creado factories para testing de las siguientes entidades:

1. **EmpresaFactory** - Generación de empresas de prueba
2. **ClienteFactory** - Generación de clientes
3. **ProveedorFactory** - Generación de proveedores
4. **ProductoFactory** - Generación de productos
5. **UsuarioFactory** - Generación de usuarios
6. **SucursalFactory** - Generación de sucursales
7. **RolFactory** - Generación de roles
8. **PermisoFactory** - Generación de permisos
9. **UserFactory** - Factory original de Laravel

**Uso en Tests:**
```php
// Crear empresa con sucursales
$empresa = Empresa::factory()
    ->has(Sucursal::factory()->count(3))
    ->create();

// Crear producto con relaciones
$producto = Producto::factory()
    ->for($empresa)
    ->create();

// Crear usuario con roles
$usuario = Usuario::factory()
    ->hasAttached(Rol::factory()->count(2))
    ->create();
```

### Database Testing

Base de datos separada para tests: `api_db_testing`

```bash
# Crear base de datos de testing
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS api_db_testing;"

# Los tests automáticamente usan RefreshDatabase
php artisan test
```

Ver documentación completa: [FASE_4_TESTING_COMPLETADA.md](FASE_4_TESTING_COMPLETADA.md)

## 🚀 Comandos Disponibles

### Instalación Completa

```bash
# Ejecutar migraciones + seeders (fresh install con 112 registros)
php artisan migrate:fresh --seed
```

Este comando:
1. Elimina todas las tablas existentes
2. Ejecuta las 60 migraciones (59 business + 1 Sanctum)
3. Ejecuta los 9 seeders en orden:
   - RegimenesTributariosSeeder (2)
   - FormasPagoSeeder (6)
   - TiposCuentasSeeder (8)
   - UnidadesMedidaSeeder (11)
   - PermisosSeeder (68)
   - RolesSeeder (7 + asignación de permisos)
   - CargosSeeder (7)
   - EmpresaDemoSeeder (2)
   - UsuarioAdminSeeder (7 + 6 relaciones rol_usuario)
4. **Total insertado: 112 registros**

### Comandos Individuales

```bash
# Solo migraciones (sin seeders)
php artisan migrate

# Solo seeders (sin recrear tablas)
php artisan db:seed

# Seeder específico
php artisan db:seed --class=PermisosSeeder
php artisan db:seed --class=UsuarioAdminSeeder

# Limpiar base de datos (eliminar todas las tablas)
php artisan db:wipe

# Ver estado de la base de datos con conteos
php artisan db:show --counts

# Rollback de última migración
php artisan migrate:rollback

# Rollback de todas las migraciones
php artisan migrate:reset
```

### Credenciales de Acceso

Después de ejecutar los seeders, puedes iniciar sesión con:

```bash
# Login con cURL
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@ursol.com",
    "password": "admin123"
  }'
```

**Credenciales:**
- **Email:** admin@ursol.com
- **Password:** admin123
- **Rol:** Administrador
- **Permisos:** 68 (acceso total)
- **Empresa:** Sistemas Ursol S.A. (ID: 1)

## 🧪 Testing con Factories

### Crear registros de prueba con Tinker
```bash
php artisan tinker
```

Dentro de Tinker:
```php
// Crear una empresa con sucursal y usuarios
$empresa = \App\Models\Empresa::factory()
    ->has(\App\Models\Sucursal::factory()->count(2))
    ->has(\App\Models\Usuario::factory()->count(5))
    ->create();

// Crear productos
$productos = \App\Models\Producto::factory()->count(20)->create([
    'empresa_id' => $empresa->id
]);

// Crear clientes
$clientes = \App\Models\Cliente::factory()->count(10)->create([
    'empresa_id' => $empresa->id
]);

// Crear proveedores
$proveedores = \App\Models\Proveedor::factory()->count(5)->create([
    'empresa_id' => $empresa->id
]);
```

## 📊 Estructura de la Base de Datos

### Tablas de Sistema

- **migrations** - Historial de migraciones ejecutadas
- **personal_access_tokens** - Tokens de autenticación (Laravel Sanctum)

### Tablas Principales (Empresas y Usuarios)

- **empresas** - Datos de las empresas (multi-tenancy)
- **sucursales** - Sucursales por empresa
- **usuarios** - Usuarios del sistema (Authenticatable + HasApiTokens)
- **roles** - Roles del sistema (7 roles predefinidos)
- **permisos** - Permisos granulares (68 permisos: 17 módulos × 4 acciones)
- **rol_usuario** - Relación many-to-many entre usuarios y roles
- **rol_permiso** - Relación many-to-many entre roles y permisos
- **empleados** - Empleados de las empresas
- **cargos** - Cargos/puestos de trabajo (7 cargos predefinidos)

### Módulo de Ventas
- **clientes** - Clientes
- **productos** - Catálogo de productos
- **ventas** - Ventas realizadas
- **detalle_ventas** - Detalle de cada venta
- **formas_pago** - Formas de pago disponibles

### Módulo de Compras
- **proveedores** - Proveedores
- **ordenes_compra** - Órdenes de compra
- **detalle_ordenes_compra** - Detalle de órdenes

### Módulo de Inventario
- **almacenes** - Almacenes
- **entradas_inventario** / **detalle_entradas_inventario**
- **salidas_inventario** / **detalle_salidas_inventario**
- **inventario_productos** - Stock por almacén

### Módulo Contable
- **cuentas_contables** - Plan de cuentas
- **tipos_cuentas** - Clasificación de cuentas
- **asientos_contables** / **detalle_asientos** - Asientos contables
- **cuentas_por_cobrar** / **pagos_cuentas_cobrar**
- **cuentas_por_pagar** / **pagos_cuentas_pagar**
- **presupuestos** / **detalle_presupuestos**

### Módulo de Transporte
- **rutas** - Rutas de transporte
- **buses_unidades** - Unidades de transporte
- **modelos_buses** - Modelos de buses
- **horarios_ruta** - Horarios por ruta
- **tiquetes_detalle** - Detalle de tiquetes vendidos

### Módulo de Nómina
- **periodos_nomina** - Períodos de nómina
- **nomina_empleados** - Nómina de empleados
- **pagos_nomina** - Pagos de nómina

### Otros Módulos
- **caja_chica** / **movimientos_caja_chica**
- **cajas** - Cajas por sucursal
- **consecutivos_fe** - Consecutivos de facturación electrónica
- **comprobantes_recibidos_electronicos**
- **etiquetas** / **entidad_etiquetas** - Sistema de etiquetado
- **configuraciones** - Configuraciones por empresa

## 🔧 Correcciones Aplicadas

### Problema de Tipos de Datos
**Problema original:** Las migraciones usaban `id()` y `foreignId()` que generan `BIGINT UNSIGNED`, pero el esquema SQL usa `INT UNSIGNED`.

**Solución aplicada:**
- Cambiado `id()` por `increments('id')`
- Cambiado `foreignId()` y `unsignedBigInteger()` por `unsignedInteger()`
- Todas las foreign keys ahora son compatibles

### Migraciones Creadas
Se crearon 10 migraciones faltantes:
1. modelos_buses
2. cajas
3. tipos_cuentas
4. cuentas_contables (corregida)
5. etiquetas
6. entidad_etiquetas
7. presupuestos
8. detalle_presupuestos
9. pagos
10. pagos_nomina

### Migraciones Corregidas
Se corrigieron 4 migraciones existentes:
1. buses_unidades - Nombre de tabla y campos
2. detalle_asientos_contables - Nombre de tabla y campos (debe/haber)
3. cuentas_contables - Campo permite_movimientos
4. horarios_ruta - Campo fecha_salida

## 📝 Notas Importantes

1. **Timestamps personalizados:** Las tablas usan `creado_en` y `actualizado_en` en lugar de `created_at` y `updated_at`
2. **Soft Deletes personalizados:** Se usa `activo` y `eliminado` en lugar de `deleted_at`
3. **Multi-tenant:** Todas las tablas operacionales tienen `empresa_id`
4. **Facturación Electrónica:** Campos preparados para integración con Hacienda Costa Rica
5. **Autenticación:** Laravel Sanctum con tokens personales por usuario
6. **RBAC:** Sistema completo de roles y permisos con 68 permisos granulares
7. **Seeders:** Ejecutar siempre en orden (DatabaseSeeder se encarga automáticamente)
8. **Credenciales demo:** admin@ursol.com / admin123 (solo en desarrollo)

## 🎯 Próximos Pasos

1. ✅ Configurar modelos Eloquent con relaciones → **COMPLETADO**
2. ✅ Crear controladores API → **COMPLETADO**
3. ✅ Implementar middleware de autenticación → **COMPLETADO (Sanctum + CheckPermission)**
4. ✅ Crear validaciones de request → **COMPLETADO (FormRequests)**
5. ✅ Implementar seeders de datos maestros → **COMPLETADO (9 seeders, 112 registros)**
6. 🔄 Implementar tests unitarios y de integración → **EN PROGRESO (FASE 4)**
7. 📝 Documentar API con Swagger/OpenAPI → **PENDIENTE (FASE 5)**
8. 🚀 Integración con Hacienda (Facturación Electrónica) → **PENDIENTE**
9. 📊 Dashboard y reportes → **PENDIENTE**
10. 📱 API versioning → **PENDIENTE**
