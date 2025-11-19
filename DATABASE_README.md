# Base de Datos - Ursol CAST API

**Desarrollado por:** Sistemas Ursol S.A.  
**País:** Costa Rica | 30 años de experiencia tecnológica  
**Desarrollador:** Jeremy Arias Solano  
**Contacto:** sistemas@ursol.com | WhatsApp +506 8868-7765

---

## ✅ Estado Actual

**Base de datos completamente configurada y funcional**

### Migraciones
- ✅ **59 migraciones** ejecutadas exitosamente
- ✅ **59 tablas** creadas en la base de datos
- ✅ Todas las **foreign keys** configuradas correctamente
- ✅ Tipos de datos compatibles (INT UNSIGNED)

### Seeders Implementados
Se han creado seeders para poblar datos iniciales en las siguientes tablas:

1. **RegimenTributarioSeeder** - 2 registros
   - Régimen Tradicional
   - Régimen Simplificado

2. **CargoSeeder** - 7 registros
   - Gerente General, Contador, Vendedor, Cajero, Bodeguero, Conductor, Asistente Administrativo

3. **TipoCuentaSeeder** - 8 registros
   - Activo Corriente, Activo No Corriente, Pasivo Corriente, Pasivo No Corriente, Patrimonio, Ingresos, Costos, Gastos

4. **RolSeeder** - 6 registros
   - Administrador, Gerente, Contador, Vendedor, Cajero, Bodeguero

5. **FormaPagoSeeder** - 6 registros
   - Efectivo, Tarjeta, Transferencia, Cheque, Crédito, Otros

6. **UnidadMedidaSeeder** - 11 registros
   - Unidad, Kilogramo, Gramo, Litro, Mililitro, Metro, Metro cuadrado, Metro cúbico, Caja, Paquete, Servicio

7. **TipoImpuestoSeeder** - 6 registros
   - IVA, Impuesto Selectivo de Consumo, Impuesto Único sobre Combustibles, etc.

### Factories Implementadas
Se han creado factories para testing de las siguientes entidades:

1. **EmpresaFactory** - Generación de empresas de prueba
2. **ClienteFactory** - Generación de clientes
3. **ProveedorFactory** - Generación de proveedores
4. **ProductoFactory** - Generación de productos
5. **UsuarioFactory** - Generación de usuarios
6. **SucursalFactory** - Generación de sucursales
7. **UserFactory** - Factory original de Laravel

## 🚀 Comandos Disponibles

### Ejecutar migraciones desde cero
```bash
php artisan migrate:fresh --seed
```

### Ejecutar solo migraciones
```bash
php artisan migrate
```

### Ejecutar solo seeders
```bash
php artisan db:seed
```

### Limpiar base de datos
```bash
php artisan db:wipe
```

### Ver estado de la base de datos
```bash
php artisan db:show --counts
```

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

### Tablas Principales
- **empresas** - Datos de las empresas
- **sucursales** - Sucursales por empresa
- **usuarios** - Usuarios del sistema
- **roles** / **rol_usuario** - Sistema de roles y permisos
- **empleados** - Empleados de las empresas

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

## 🎯 Próximos Pasos Sugeridos

1. ✅ Configurar modelos Eloquent con relaciones
2. ✅ Crear controladores API
3. ✅ Implementar middleware de autenticación
4. ✅ Crear validaciones de request
5. ✅ Implementar tests unitarios y de integración
6. ✅ Documentar API con Swagger/OpenAPI
