# Controladores API - Resumen Completo

## 📋 Resumen General

**Total de Controladores:** 14  
**Total de Rutas API:** 72  
**Última Actualización:** 19 de noviembre de 2025

---

## 🎯 Controladores Implementados

### 1. AuthController
**Propósito:** Autenticación y gestión de sesiones con Laravel Sanctum  
**Rutas:** 4  
**Endpoints:**
- `POST /api/login` - Iniciar sesión
- `POST /api/register` - Registrar nuevo usuario
- `GET /api/user` - Obtener perfil del usuario autenticado
- `POST /api/logout` - Cerrar sesión

---

### 2. EmpresaController
**Propósito:** Gestión de empresas (Multi-Tenant)  
**Rutas:** 5  
**Modelo:** `Empresa`  
**Resource:** `EmpresaResource`  
**FormRequests:** `StoreEmpresaRequest`, `UpdateEmpresaRequest`  
**Endpoints:**
- `GET /api/empresas` - Listar empresas
- `POST /api/empresas` - Crear empresa
- `GET /api/empresas/{id}` - Ver empresa
- `PUT/PATCH /api/empresas/{id}` - Actualizar empresa
- `DELETE /api/empresas/{id}` - Eliminar empresa (soft delete)

**Relaciones:**
- `hasMany` → Sucursales, Productos, Clientes, Proveedores, Ventas, Órdenes de Compra

---

### 3. SucursalController
**Propósito:** Gestión de sucursales por empresa  
**Rutas:** 5  
**Modelo:** `Sucursal`  
**Resource:** `SucursalResource`  
**FormRequests:** `StoreSucursalRequest`, `UpdateSucursalRequest`  
**Endpoints:**
- `GET /api/sucursales` - Listar sucursales
- `POST /api/sucursales` - Crear sucursal
- `GET /api/sucursales/{id}` - Ver sucursal
- `PUT/PATCH /api/sucursales/{id}` - Actualizar sucursal
- `DELETE /api/sucursales/{id}` - Eliminar sucursal

**Relaciones:**
- `belongsTo` → Empresa
- `hasMany` → Almacenes, Cajas

---

### 4. AlmacenController
**Propósito:** Gestión de almacenes/bodegas  
**Rutas:** 5  
**Modelo:** `Almacen`  
**Resource:** `AlmacenResource`  
**FormRequests:** `StoreAlmacenRequest`, `UpdateAlmacenRequest`  
**Endpoints:**
- `GET /api/almacenes` - Listar almacenes
- `POST /api/almacenes` - Crear almacén
- `GET /api/almacenes/{id}` - Ver almacén
- `PUT/PATCH /api/almacenes/{id}` - Actualizar almacén
- `DELETE /api/almacenes/{id}` - Eliminar almacén

**Relaciones:**
- `belongsTo` → Empresa, Sucursal
- `hasMany` → Entradas/Salidas de Inventario

---

### 5. ProductoController
**Propósito:** Catálogo de productos y servicios  
**Rutas:** 5  
**Modelo:** `Producto`  
**Resource:** `ProductoResource`  
**FormRequests:** `StoreProductoRequest`, `UpdateProductoRequest`  
**Endpoints:**
- `GET /api/productos` - Listar productos (con filtros)
- `POST /api/productos` - Crear producto
- `GET /api/productos/{id}` - Ver producto
- `PUT/PATCH /api/productos/{id}` - Actualizar producto
- `DELETE /api/productos/{id}` - Eliminar producto

**Relaciones:**
- `belongsTo` → Empresa, Categoría, Marca, Unidad Medida, Tipo Impuesto, CAByS

---

### 6. ClienteController
**Propósito:** Gestión de clientes  
**Rutas:** 5  
**Modelo:** `Cliente`  
**Resource:** `ClienteResource`  
**FormRequests:** `StoreClienteRequest`, `UpdateClienteRequest`  
**Endpoints:**
- `GET /api/clientes` - Listar clientes
- `POST /api/clientes` - Crear cliente
- `GET /api/clientes/{id}` - Ver cliente
- `PUT/PATCH /api/clientes/{id}` - Actualizar cliente
- `DELETE /api/clientes/{id}` - Eliminar cliente

**Validaciones Especiales:**
- Número de identificación único por empresa
- Tipos de identificación DGT (01-07)
- Régimen tributario Costa Rica

---

### 7. ProveedorController
**Propósito:** Gestión de proveedores  
**Rutas:** 5  
**Modelo:** `Proveedor`  
**Resource:** `ProveedorResource`  
**FormRequests:** `StoreProveedorRequest`, `UpdateProveedorRequest`  
**Endpoints:**
- `GET /api/proveedores` - Listar proveedores
- `POST /api/proveedores` - Crear proveedor
- `GET /api/proveedores/{id}` - Ver proveedor
- `PUT/PATCH /api/proveedores/{id}` - Actualizar proveedor
- `DELETE /api/proveedores/{id}` - Eliminar proveedor

---

### 8. VentaController
**Propósito:** Gestión de ventas y facturación  
**Rutas:** 5  
**Modelo:** `Venta`  
**Resource:** `VentaResource`  
**FormRequests:** `StoreVentaRequest`, `UpdateVentaRequest`  
**Endpoints:**
- `GET /api/ventas` - Listar ventas
- `POST /api/ventas` - Crear venta
- `GET /api/ventas/{id}` - Ver venta
- `PUT/PATCH /api/ventas/{id}` - Actualizar venta
- `DELETE /api/ventas/{id}` - Eliminar venta

**Características:**
- Cálculo automático de subtotales e impuestos
- Gestión de detalles de venta
- Estados: borrador, pendiente, pagada, anulada

---

### 9. OrdenCompraController
**Propósito:** Gestión de órdenes de compra  
**Rutas:** 5  
**Modelo:** `OrdenCompra`  
**Resource:** `OrdenCompraResource`  
**FormRequests:** `StoreOrdenCompraRequest`, `UpdateOrdenCompraRequest`  
**Endpoints:**
- `GET /api/ordenes-compra` - Listar órdenes
- `POST /api/ordenes-compra` - Crear orden
- `GET /api/ordenes-compra/{id}` - Ver orden
- `PUT/PATCH /api/ordenes-compra/{id}` - Actualizar orden (solo estados permitidos)
- `DELETE /api/ordenes-compra/{id}` - Eliminar orden

**Validaciones Especiales:**
- Solo se pueden editar órdenes en estado "borrador" o "pendiente"
- Cálculo automático de totales

---

### 10. EmpleadoController ✨ NUEVO
**Propósito:** Gestión de empleados  
**Rutas:** 5  
**Modelo:** `Empleado`  
**Resource:** `EmpleadoResource`  
**FormRequests:** `StoreEmpleadoRequest`, `UpdateEmpleadoRequest`  
**Endpoints:**
- `GET /api/empleados` - Listar empleados
- `POST /api/empleados` - Crear empleado
- `GET /api/empleados/{id}` - Ver empleado
- `PUT/PATCH /api/empleados/{id}` - Actualizar empleado
- `DELETE /api/empleados/{id}` - Eliminar empleado

**Características:**
- Filtrado por cargo y estado
- Validación de documentos únicos por empresa
- Cálculo de edad y antigüedad
- Relación opcional con usuarios del sistema
- Validación de edad mínima de contratación (15 años)

**Relaciones:**
- `belongsTo` → Empresa, Cargo, Usuario

---

### 11. CategoriaProductoController ✨ NUEVO
**Propósito:** Gestión de categorías de productos  
**Rutas:** 5  
**Modelo:** `CategoriaProducto`  
**Resource:** `CategoriaProductoResource`  
**FormRequests:** `StoreCategoriaProductoRequest`, `UpdateCategoriaProductoRequest`  
**Endpoints:**
- `GET /api/categorias-productos` - Listar categorías
- `POST /api/categorias-productos` - Crear categoría
- `GET /api/categorias-productos/{id}` - Ver categoría
- `PUT/PATCH /api/categorias-productos/{id}` - Actualizar categoría
- `DELETE /api/categorias-productos/{id}` - Eliminar categoría

**Características:**
- Nombres únicos por empresa
- Contador de productos por categoría

**Relaciones:**
- `belongsTo` → Empresa
- `hasMany` → Productos

---

### 12. MarcaController ✨ NUEVO
**Propósito:** Gestión de marcas de productos  
**Rutas:** 5  
**Modelo:** `Marca`  
**Resource:** `MarcaResource`  
**FormRequests:** `StoreMarcaRequest`, `UpdateMarcaRequest`  
**Endpoints:**
- `GET /api/marcas` - Listar marcas
- `POST /api/marcas` - Crear marca
- `GET /api/marcas/{id}` - Ver marca
- `PUT/PATCH /api/marcas/{id}` - Actualizar marca
- `DELETE /api/marcas/{id}` - Eliminar marca

**Características:**
- Tabla global (sin empresa_id según api_db.sql)
- Nombres únicos globalmente

**Relaciones:**
- `hasMany` → Productos

---

### 13. UnidadMedidaController ✨ NUEVO
**Propósito:** Gestión de unidades de medida  
**Rutas:** 5  
**Modelo:** `UnidadMedida`  
**Resource:** `UnidadMedidaResource`  
**FormRequests:** `StoreUnidadMedidaRequest`, `UpdateUnidadMedidaRequest`  
**Endpoints:**
- `GET /api/unidades-medida` - Listar unidades
- `POST /api/unidades-medida` - Crear unidad
- `GET /api/unidades-medida/{id}` - Ver unidad
- `PUT/PATCH /api/unidades-medida/{id}` - Actualizar unidad
- `DELETE /api/unidades-medida/{id}` - Eliminar unidad

**Características:**
- Tabla global (sin empresa_id)
- Código oficial para Hacienda CR
- Abreviaturas únicas

**Relaciones:**
- `hasMany` → Productos

---

### 14. InventarioController ✨ NUEVO
**Propósito:** Gestión de movimientos de inventario (entradas y salidas)  
**Rutas:** 8  
**Modelos:** `EntradaInventario`, `SalidaInventario`  
**Resources:** `EntradaInventarioResource`, `SalidaInventarioResource`  
**FormRequests:** `StoreEntradaInventarioRequest`, `StoreSalidaInventarioRequest`  

**Endpoints - Entradas:**
- `GET /api/inventario/entradas` - Listar entradas
- `POST /api/inventario/entradas` - Crear entrada
- `GET /api/inventario/entradas/{id}` - Ver entrada
- `POST /api/inventario/entradas/{id}/cancelar` - Cancelar entrada

**Endpoints - Salidas:**
- `GET /api/inventario/salidas` - Listar salidas
- `POST /api/inventario/salidas` - Crear salida
- `GET /api/inventario/salidas/{id}` - Ver salida
- `POST /api/inventario/salidas/{id}/cancelar` - Cancelar salida

**Características - Entradas:**
- Tipos: Compra, Ajuste Positivo, Devolución Cliente, Transferencia, Producción
- Relación con órdenes de compra y proveedores
- Control de lotes y fechas de vencimiento
- Estados: Pendiente, Procesada, Cancelada

**Características - Salidas:**
- Tipos: Venta, Ajuste Negativo, Devolución Proveedor, Transferencia, Consumo Interno
- Relación con ventas, clientes y proveedores
- Control de costos por lote
- Estados: Pendiente, Procesada, Cancelada

**Validaciones:**
- No se pueden cancelar movimientos ya procesados
- Todos los almacenes deben pertenecer a la empresa del usuario
- Detalles con productos válidos y cantidades positivas

**Relaciones:**
- `belongsTo` → Empresa, Almacen, OrdenCompra, Proveedor, Venta, Cliente
- `hasMany` → Detalles (DetalleEntradaInventario, DetalleSalidaInventario)

---

## 📊 Estadísticas de Implementación

| Componente | Cantidad | Estado |
|------------|----------|--------|
| **Controladores** | 14 | ✅ Completo |
| **Rutas API** | 72 | ✅ Completo |
| **Modelos** | 55 | ✅ Completo |
| **API Resources** | 14 | ✅ Completo |
| **FormRequests** | 24 | ✅ Completo |
| **Migraciones** | 58 | ✅ Completo |

---

## 🔐 Seguridad Implementada

- ✅ Autenticación con Laravel Sanctum
- ✅ Multi-Tenant por `empresa_id`
- ✅ Validación de pertenencia de recursos a empresa
- ✅ Soft Deletes en todos los recursos
- ✅ FormRequests con validaciones complejas
- ✅ Mensajes de error personalizados en español

---

## 📁 Estructura de Archivos

```
app/
├── Http/
│   ├── Controllers/API/
│   │   ├── AuthController.php
│   │   ├── EmpresaController.php
│   │   ├── SucursalController.php
│   │   ├── AlmacenController.php
│   │   ├── ProductoController.php
│   │   ├── ClienteController.php
│   │   ├── ProveedorController.php
│   │   ├── VentaController.php
│   │   ├── OrdenCompraController.php
│   │   ├── EmpleadoController.php ✨ NUEVO
│   │   ├── CategoriaProductoController.php ✨ NUEVO
│   │   ├── MarcaController.php ✨ NUEVO
│   │   ├── UnidadMedidaController.php ✨ NUEVO
│   │   └── InventarioController.php ✨ NUEVO
│   │
│   ├── Requests/
│   │   ├── StoreEmpresaRequest.php
│   │   ├── UpdateEmpresaRequest.php
│   │   ├── StoreProductoRequest.php
│   │   ├── UpdateProductoRequest.php
│   │   ├── StoreClienteRequest.php
│   │   ├── UpdateClienteRequest.php
│   │   ├── StoreProveedorRequest.php
│   │   ├── UpdateProveedorRequest.php
│   │   ├── StoreSucursalRequest.php
│   │   ├── UpdateSucursalRequest.php
│   │   ├── StoreAlmacenRequest.php
│   │   ├── UpdateAlmacenRequest.php
│   │   ├── StoreVentaRequest.php
│   │   ├── UpdateVentaRequest.php
│   │   ├── StoreOrdenCompraRequest.php
│   │   ├── UpdateOrdenCompraRequest.php
│   │   ├── StoreEmpleadoRequest.php ✨ NUEVO
│   │   ├── UpdateEmpleadoRequest.php ✨ NUEVO
│   │   ├── StoreCategoriaProductoRequest.php ✨ NUEVO
│   │   ├── UpdateCategoriaProductoRequest.php ✨ NUEVO
│   │   ├── StoreMarcaRequest.php ✨ NUEVO
│   │   ├── UpdateMarcaRequest.php ✨ NUEVO
│   │   ├── StoreUnidadMedidaRequest.php ✨ NUEVO
│   │   ├── UpdateUnidadMedidaRequest.php ✨ NUEVO
│   │   ├── StoreEntradaInventarioRequest.php ✨ NUEVO
│   │   └── StoreSalidaInventarioRequest.php ✨ NUEVO
│   │
│   └── Resources/
│       ├── EmpresaResource.php
│       ├── SucursalResource.php
│       ├── AlmacenResource.php
│       ├── ProductoResource.php
│       ├── ClienteResource.php
│       ├── ProveedorResource.php
│       ├── VentaResource.php
│       ├── OrdenCompraResource.php
│       ├── EmpleadoResource.php ✨ NUEVO
│       ├── CategoriaProductoResource.php ✨ NUEVO
│       ├── MarcaResource.php ✨ NUEVO
│       ├── UnidadMedidaResource.php ✨ NUEVO
│       ├── EntradaInventarioResource.php ✨ NUEVO
│       └── SalidaInventarioResource.php ✨ NUEVO
```

---

## 🚀 Próximos Controladores Sugeridos

### Alta Prioridad:
1. **RolController** / **PermisoController** - Sistema RBAC
2. **UsuarioController** - Gestión de usuarios
3. **CuentaPorCobrarController** - Cuentas por cobrar
4. **CuentaPorPagarController** - Cuentas por pagar
5. **FormaPagoController** - Formas de pago

### Media Prioridad:
6. **CargoController** - Cargos de empleados
7. **CabyController** - Catálogo CAByS
8. **TipoImpuestoController** - Tipos de impuestos
9. **CuentaContableController** - Plan de cuentas
10. **ReporteController** - Generación de reportes

### Baja Prioridad:
11. **AsientoContableController** - Contabilidad
12. **NominaController** - Gestión de nómina
13. **BusUnidadController** - Unidades de transporte
14. **RutaController** - Rutas de transporte
15. **FacturacionElectronicaController** - Facturación electrónica CR

---

**Desarrollado por:** Senselab  
**Equipo:** Jeremy Arias Solano, Eduardo Alberto Ureña Solano  
**Licencia:** MIT  
**Repositorio:** https://github.com/SenseLab-dev/Senselab_Core_API
