# API REST - Ursol CAST API

**Desarrollado por Sistemas Ursol S.A.**  
*Soluciones Tecnológicas | Costa Rica | 30 años de experiencia*

---

## 📖 Acceso Rápido

### Documentación Interactiva Swagger

**🚀 Recomendado:** Para explorar y probar la API de forma interactiva, accede a:

```
http://localhost:8000/api/documentation
```

**Características de Swagger UI:**
- ✅ Prueba endpoints directamente desde el navegador
- ✅ Autenticación Bearer integrada
- ✅ Ejemplos de request/response
- ✅ Documentación actualizada automáticamente
- ✅ Schemas de datos completos

### Esta Documentación

Este documento proporciona información detallada sobre todos los endpoints, incluyendo:
- Ejemplos de uso con curl
- Estructura de datos
- Códigos de error
- Casos de uso

---

## 🧪 Testing

El proyecto incluye **127 tests automatizados** que validan el funcionamiento de la API:

- **AuthTest (11 tests)**: Login, logout, tokens, permisos
- **ProductoTest (12 tests)**: CRUD completo, validación, búsqueda
- **PermissionTest (17 tests)**: Sistema RBAC completo
- **RoleTest (10 tests)**: Modelo Rol y relaciones
- **UsuarioTest (16 tests)**: Modelo Usuario y autenticación

Ejecutar tests:
```bash
php artisan test
```

Ver documentación completa de testing: [FASE_4_TESTING_COMPLETADA.md](FASE_4_TESTING_COMPLETADA.md)

---

## 📚 Documentación de Endpoints

### Configuración Base

**URL Base:** `http://localhost:8000/api`

**Autenticación:** Laravel Sanctum (Bearer Token)

**Headers requeridos:**
```json
{
  "Accept": "application/json",
  "Content-Type": "application/json",
  "Authorization": "Bearer {token}"
}
```

---

## 🔐 Autenticación

### Configuración

El sistema utiliza **Laravel Sanctum** para autenticación basada en tokens API.

**Características:**
- ✅ Autenticación stateless por tokens
- ✅ Tokens personales por usuario
- ✅ Revocación de tokens (logout)
- ✅ Múltiples tokens por usuario (diferentes dispositivos)
- ✅ Expiración configurable de tokens

### POST /login
Iniciar sesión y obtener token de acceso

**Endpoint en Swagger:** `POST /api/login`

**Request:**
```json
{
  "email": "admin@ursol.com",
  "password": "admin123"
}
```

**Ejemplo con curl:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@ursol.com",
    "password": "admin123"
  }'
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "usuario": {
      "id": 1,
      "nombre": "Administrador Sistema",
      "email": "admin@ursol.com",
      "empresa_id": 1,
      "cargo_id": 1,
      "activo": true,
      "roles": [
        {
          "id": 1,
          "nombre": "Administrador",
          "slug": "administrador"
        }
      ],
      "empresa": {
        "id": 1,
        "nombre": "Sistemas Ursol S.A.",
        "identificacion": "3-101-123456"
      }
    },
    "token": "1|dkjf9283hd9fh2938hf9823hf9823hf9823h",
    "permisos": [
      "empresas.crear",
      "empresas.leer",
      "empresas.actualizar",
      "empresas.eliminar",
      "productos.crear",
      "productos.leer"
      // ... Total: 68 permisos
    ]
  },
  "message": "Login exitoso"
}
```

**Errores:**
```json
// Credenciales incorrectas (422)
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "Las credenciales son incorrectas."
    ]
  }
}
```

**Testing:**
```php
// Ver AuthTest::test_usuario_puede_hacer_login()
$response = $this->postJson('/api/login', [
    'email' => 'admin@ursol.com',
    'password' => 'admin123',
]);

$response->assertStatus(200)
         ->assertJsonStructure(['success', 'data' => ['usuario', 'token', 'permisos']]);
```

### POST /logout
Cerrar sesión y revocar token actual

**Endpoint en Swagger:** `POST /api/logout`

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Ejemplo con curl:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|dkjf9283hd9fh2938hf9823hf9823hf9823h"
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout exitoso"
}
```

**Testing:**
```php
// Ver AuthTest::test_usuario_puede_hacer_logout()
$usuario = Usuario::factory()->create();
$token = $usuario->createToken('test-token')->plainTextToken;

$response = $this->withHeader('Authorization', 'Bearer ' . $token)
                 ->postJson('/api/logout');

$response->assertStatus(200);
```

---

### GET /user
Obtener información del usuario autenticado

**Endpoint en Swagger:** `GET /api/user`

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Ejemplo con curl:**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|dkjf9283hd9fh2938hf9823hf9823hf9823h"
```

**Response (200):**
```json
{
  "message": "Sesión cerrada exitosamente"
}
```

**Nota:** Solo revoca el token usado en la request. Otros tokens del mismo usuario permanecen activos.

### GET /me
Obtener información del usuario autenticado

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Response (200):**
```json
{
  "id": 1,
  "nombre": "Administrador",
  "apellidos": "Sistema",
  "email": "admin@ursol.com",
  "empresa": {
    "id": 1,
    "nombre": "Sistemas Ursol S.A.",
    "razon_social": "Sistemas Ursol Sociedad Anónima"
  },
  "cargo": {
    "id": 1,
    "nombre": "Gerente General"
  },
  "roles": [
    {
      "id": 1,
      "nombre": "Administrador",
      "descripcion": "Acceso total al sistema"
    }
  ],
  "permisos": [
    "empresas.crear",
    "empresas.leer",
    ...
  ]
}
```

### ~~POST /register~~ (DESHABILITADO)

Por razones de seguridad, el registro público está **deshabilitado**. Los usuarios se crean mediante:
1. **Seeders** - Para datos de prueba/desarrollo
2. **Panel de administración** - Administradores pueden crear usuarios
3. **Comandos Artisan** - Para usuarios iniciales

---

## 🔒 Autorización (RBAC)

### Sistema de Permisos

El sistema implementa **RBAC (Role-Based Access Control)** con 68 permisos granulares.

**Estructura de permisos:**
```
{modulo}.{accion}
```

**Acciones:**
- `crear` - Crear nuevos registros
- `leer` - Ver/listar registros
- `actualizar` - Modificar registros existentes
- `eliminar` - Eliminar registros (soft delete)

**Módulos (17 total):**
1. `empresas` - Gestión de empresas
2. `sucursales` - Sucursales
3. `almacenes` - Almacenes
4. `productos` - Productos y servicios
5. `categorias_producto` - Categorías
6. `clientes` - Clientes
7. `proveedores` - Proveedores
8. `ventas` - Ventas y facturación
9. `compras` - Órdenes de compra
10. `inventario` - Entradas/salidas de inventario
11. `cuentas_contables` - Plan de cuentas
12. `asientos_contables` - Asientos contables
13. `empleados` - Gestión de empleados
14. `nomina` - Nómina y pagos
15. `rutas` - Rutas de transporte
16. `buses` - Flota de buses
17. `facturacion_electronica` - Facturación electrónica DGT

**Total de permisos:** 17 módulos × 4 acciones = **68 permisos**

### Roles Predefinidos

1. **Administrador** - Todos los permisos (68)
2. **Gerente** - Gestión completa excepto configuraciones críticas
3. **Contador** - Módulos contables y financieros
4. **Vendedor** - Ventas, clientes, productos (solo lectura)
5. **Comprador** - Compras, proveedores, inventario
6. **Bodeguero** - Inventario, almacenes, productos
7. **Usuario** - Permisos básicos de lectura

### Middleware de Permisos

**Uso en rutas:**

```php
// Requiere un permiso específico
Route::get('/empresas', [EmpresaController::class, 'index'])
    ->middleware('permission:empresas.leer');

// Requiere uno de varios permisos (OR)
Route::post('/ventas', [VentaController::class, 'store'])
    ->middleware('permission:ventas.crear,administrador');
```

**Respuesta sin permiso (403):**
```json
{
  "message": "No tienes permiso para realizar esta acción"
}
```

### Métodos RBAC en Usuario

El modelo `Usuario` incluye métodos helper para verificar permisos:

```php
$usuario = auth()->user();

// Verificar un permiso
if ($usuario->hasPermission('empresas.crear')) {
    // Usuario puede crear empresas
}

// Verificar un rol
if ($usuario->hasRole('Administrador')) {
    // Usuario es administrador
}

// Verificar uno de varios roles
if ($usuario->hasAnyRole(['Administrador', 'Gerente'])) {
    // Usuario es admin o gerente
}

// Obtener todos los permisos del usuario
$permisos = $usuario->getAllPermissions();
// ['empresas.crear', 'empresas.leer', ...]

// Asignar roles a un usuario
$usuario->assignRoles(['Vendedor', 'Cajero']);
```

---

## 📝 Headers Requeridos

### Para todos los endpoints (excepto /login)

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Ejemplo completo con cURL

```bash
# 1. Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@ursol.com",
    "password": "admin123"
  }'

# Guardar el token de la respuesta
TOKEN="1|abc123def456..."

# 2. Usar el token en requests protegidos
curl -X GET http://localhost:8000/api/empresas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 3. Logout
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## 🏢 Empresas

### GET /empresas
Listar empresas con paginación

**Requiere permiso:** `empresas.leer`

**Query Parameters:**
- `per_page` (int) - Registros por página (default: 15)
- `search` (string) - Buscar por nombre, razón social, NIT/RUC, email
- `activos` (boolean) - Filtrar solo activos

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Mi Empresa S.A.",
      "razon_social": "Mi Empresa Sociedad Anónima",
      "nit_ruc": "123456789",
      "regimen_tributario_id": 1,
      "email": "info@miempresa.com",
      "telefono": "+506 2222-3333",
      "activo": true,
      "regimen_tributario": {
        "id": 1,
        "nombre": "Régimen Ordinario"
      }
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 50
}
```

### POST /empresas
Crear nueva empresa

**Requiere permiso:** `empresas.crear`

**Request:**
```json
{
  "nombre": "Mi Empresa S.A.",
  "razon_social": "Mi Empresa Sociedad Anónima",
  "nit_ruc": "123456789",
  "regimen_tributario_id": 1,
  "email": "info@miempresa.com",
  "telefono": "+506 2222-3333",
  "direccion": "San José, Costa Rica",
  "pais": "Costa Rica",
  "provincia": "San José",
  "ciudad": "San José",
  "codigo_postal": "10101",
  "sitio_web": "https://miempresa.com",
  "activo": true
}
```

**Response (201):**
```json
{
  "message": "Empresa creada exitosamente",
  "data": {
    "id": 1,
    "nombre": "Mi Empresa S.A.",
    ...
  }
}
```

### GET /empresas/{id}
Obtener empresa específica con relaciones

**Requiere permiso:** `empresas.leer`

**Response (200):**
```json
{
  "id": 1,
  "nombre": "Mi Empresa S.A.",
  "regimen_tributario": {...},
  "sucursales": [...],
  "usuarios": [...],
  "configuraciones": [...]
}
```

### PUT/PATCH /empresas/{id}
Actualizar empresa

**Requiere permiso:** `empresas.actualizar`

### DELETE /empresas/{id}
Eliminar empresa (soft delete)

**Requiere permiso:** `empresas.eliminar`

---

## 🏪 Sucursales

### GET /sucursales
Listar sucursales

**Requiere permiso:** `sucursales.leer`

**Query Parameters:**
- `empresa_id` (int) - Filtrar por empresa
- `activos` (boolean) - Solo activos

### POST /sucursales
Crear sucursal

**Requiere permiso:** `sucursales.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "nombre": "Sucursal Centro",
  "codigo": "SUC-001",
  "telefono": "+506 2222-4444",
  "email": "centro@miempresa.com",
  "direccion": "Av. Central, San José",
  "provincia": "San José",
  "canton": "San José",
  "distrito": "Carmen",
  "codigo_postal": "10101",
  "es_principal": true,
  "activo": true
}
```

**Validaciones:**
- Si `es_principal` es `true`, se desmarca automáticamente otras sucursales principales
- No se puede eliminar una sucursal principal

### GET /sucursales/{id}
Obtener sucursal con almacenes y cajas

### PUT/PATCH /sucursales/{id}
Actualizar sucursal

### DELETE /sucursales/{id}
Eliminar sucursal (no permite eliminar sucursal principal)

---

## 📦 Almacenes

### GET /almacenes
Listar almacenes

**Requiere permiso:** `almacenes.leer`

**Query Parameters:**
- `empresa_id` (int)
- `sucursal_id` (int)
- `activos` (boolean)

### POST /almacenes
Crear almacén

**Requiere permiso:** `almacenes.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "sucursal_id": 1,
  "nombre": "Almacén Principal",
  "codigo": "ALM-001",
  "descripcion": "Almacén central de productos",
  "ubicacion": "Bodega A, Planta Baja",
  "es_principal": true,
  "activo": true
}
```

### GET /almacenes/{id}
### PUT/PATCH /almacenes/{id}
### DELETE /almacenes/{id}

---

## 📦 Productos

### GET /productos
Listar productos con filtros avanzados

**Requiere permiso:** `productos.leer`

**Query Parameters:**
- `per_page` (int)
- `search` (string) - Buscar por nombre, código, código de barras, descripción
- `empresa_id` (int)
- `categoria_id` (int)
- `tipo` (string) - "producto" o "servicio"
- `activos` (boolean)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Laptop Dell XPS 15",
      "codigo": "PROD-001",
      "codigo_barras": "7501234567890",
      "tipo": "producto",
      "precio_compra": 800.00,
      "precio_venta": 1200.00,
      "stock_minimo": 5,
      "stock_maximo": 50,
      "categoria": {
        "id": 1,
        "nombre": "Electrónica"
      },
      "unidad_medida": {
        "id": 1,
        "nombre": "Unidad"
      },
      "marca": {
        "id": 1,
        "nombre": "Dell"
      }
    }
  ],
  "current_page": 1,
  "per_page": 15
}
```

### POST /productos
Crear producto

**Requiere permiso:** `productos.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "categoria_id": 1,
  "unidad_medida_id": 1,
  "nombre": "Laptop Dell XPS 15",
  "codigo": "PROD-001",
  "codigo_barras": "7501234567890",
  "descripcion": "Laptop de alta gama",
  "tipo": "producto",
  "precio_compra": 800.00,
  "precio_venta": 1200.00,
  "stock_minimo": 5,
  "stock_maximo": 50,
  "marca_id": 1,
  "proveedor_predeterminado_id": 1,
  "tipo_impuesto_id": 1,
  "cabys_id": 1,
  "imagen_url": "https://...",
  "activo": true
}
```

**Response (201):**
```json
{
  "message": "Producto creado exitosamente",
  "data": {
    "id": 1,
    "nombre": "Laptop Dell XPS 15",
    ...
  }
}
```

### GET /productos/{id}
Obtener producto con todas sus relaciones

### PUT/PATCH /productos/{id}
Actualizar producto

### DELETE /productos/{id}
Eliminar producto (soft delete)

---

## 👥 Clientes

### GET /clientes
Listar clientes

**Requiere permiso:** `clientes.leer`

**Query Parameters:**
- `search` (string) - Buscar por nombre, apellidos, razón social, identificación, email
- `empresa_id` (int)
- `tipo_identificacion` (string) - "fisica", "juridica", "dimex", "nite", "extranjero"
- `activos` (boolean)

### POST /clientes
Crear cliente

**Requiere permiso:** `clientes.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "tipo_identificacion": "fisica",
  "identificacion": "109870654",
  "nombre": "Juan",
  "apellidos": "Pérez González",
  "razon_social": null,
  "nombre_comercial": null,
  "email": "juan@example.com",
  "telefono": "+506 2222-5555",
  "celular": "+506 8888-9999",
  "direccion": "San José, Costa Rica",
  "provincia": "San José",
  "canton": "San José",
  "distrito": "Carmen",
  "codigo_postal": "10101",
  "limite_credito": 5000.00,
  "dias_credito": 30,
  "activo": true
}
```

**Validaciones:**
- La `identificacion` debe ser única por empresa
- El modelo incluye método `tieneIdentificacionValida()` para validar según tipo

**Response (201):**
```json
{
  "message": "Cliente creado exitosamente",
  "data": {
    "id": 1,
    "nombre_completo": "Juan Pérez González",
    "tipo_identificacion_descripcion": "Persona Física",
    ...
  }
}
```

### GET /clientes/{id}
Obtener cliente con últimas 10 ventas y cuentas por cobrar pendientes

### PUT/PATCH /clientes/{id}
Actualizar cliente

### DELETE /clientes/{id}
Eliminar cliente (soft delete)

---

## 🏭 Proveedores

### GET /proveedores
Listar proveedores

**Requiere permiso:** `proveedores.leer`

**Query Parameters:**
- `search` (string)
- `empresa_id` (int)
- `activos` (boolean)

### POST /proveedores
Crear proveedor

**Requiere permiso:** `proveedores.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "nombre": "Proveedor XYZ S.A.",
  "razon_social": "Proveedor XYZ Sociedad Anónima",
  "nit_ruc": "3-101-123456",
  "email": "ventas@proveedorxyz.com",
  "telefono": "+506 2222-6666",
  "celular": "+506 8888-7777",
  "direccion": "Cartago, Costa Rica",
  "pais": "Costa Rica",
  "provincia": "Cartago",
  "ciudad": "Cartago",
  "codigo_postal": "30101",
  "contacto_nombre": "María González",
  "contacto_telefono": "+506 8888-5555",
  "contacto_email": "maria@proveedorxyz.com",
  "dias_credito": 30,
  "limite_credito": 10000.00,
  "activo": true
}
```

**Nota:** El modelo Proveedor normaliza automáticamente campos (nombre, email, etc.) al guardar

### GET /proveedores/{id}
Obtener proveedor con últimas 10 órdenes de compra y cuentas por pagar pendientes

### PUT/PATCH /proveedores/{id}
### DELETE /proveedores/{id}

---

## 🛒 Ventas

### GET /ventas
Listar ventas

**Requiere permiso:** `ventas.leer`

**Query Parameters:**
- `empresa_id` (int)
- `sucursal_id` (int)
- `cliente_id` (int)
- `fecha_inicio` (date) - Formato: YYYY-MM-DD
- `fecha_fin` (date)

### POST /ventas
Crear venta con detalles

**Requiere permiso:** `ventas.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "sucursal_id": 1,
  "cliente_id": 1,
  "usuario_id": 1,
  "forma_pago_id": 1,
  "fecha_venta": "2025-11-18",
  "tipo_comprobante": "factura",
  "observaciones": "Venta contado",
  "detalles": [
    {
      "producto_id": 1,
      "cantidad": 2,
      "precio_unitario": 1200.00,
      "descuento": 50.00,
      "porcentaje_impuesto": 13,
      "descripcion": "Laptop Dell XPS 15"
    },
    {
      "producto_id": 2,
      "cantidad": 1,
      "precio_unitario": 150.00,
      "descuento": 0,
      "porcentaje_impuesto": 13,
      "descripcion": "Mouse inalámbrico"
    }
  ]
}
```

**Tipos de comprobante:**
- `factura`
- `tiquete`
- `nota_credito`
- `nota_debito`

**Proceso automático:**
1. Genera número de comprobante único: `FAC-00000001`, `TIQ-00000001`, etc.
2. Crea detalles de venta
3. Calcula subtotales, impuestos y total automáticamente
4. Actualiza totales de la venta

**Response (201):**
```json
{
  "message": "Venta creada exitosamente",
  "data": {
    "id": 1,
    "numero_comprobante": "FAC-00000001",
    "monto_subtotal": 2550.00,
    "monto_descuentos": 50.00,
    "monto_impuestos": 325.00,
    "monto_total": 2825.00,
    "cliente": {...},
    "detalles": [...]
  }
}
```

### GET /ventas/{id}
Obtener venta completa con detalles, cliente, usuario, etc.

### PUT/PATCH /ventas/{id}
Actualizar venta (solo observaciones y estado)

**Estados permitidos:**
- `pendiente`
- `pagada`
- `anulada`

### DELETE /ventas/{id}
Anular venta (marca estado como "anulada" y soft delete)

---

## 📝 Órdenes de Compra

### GET /ordenes-compra
Listar órdenes de compra

**Requiere permiso:** `compras.leer`

**Query Parameters:**
- `empresa_id` (int)
- `proveedor_id` (int)
- `estado` (string)
- `pendientes` (boolean)
- `activas` (boolean)

**Estados:**
- `borrador`
- `pendiente`
- `aprobada`
- `recibida`
- `cancelada`

### POST /ordenes-compra
Crear orden de compra

**Requiere permiso:** `compras.crear`

**Request:**
```json
{
  "empresa_id": 1,
  "proveedor_id": 1,
  "usuario_id": 1,
  "fecha_orden": "2025-11-18",
  "fecha_entrega_estimada": "2025-11-25",
  "estado": "pendiente",
  "observaciones": "Entrega en horario de oficina",
  "detalles": [
    {
      "producto_id": 1,
      "cantidad": 10,
      "precio_unitario": 800.00,
      "descuento": 100.00,
      "descripcion": "Laptop Dell XPS 15"
    },
    {
      "producto_id": 2,
      "cantidad": 20,
      "precio_unitario": 50.00,
      "descuento": 0
    }
  ]
}
```

**Proceso automático:**
1. Genera número de orden: `OC-000001`
2. Crea detalles
3. Calcula subtotal, impuestos y total
4. Soporta transacciones (rollback en caso de error)

**Response (201):**
```json
{
  "message": "Orden de compra creada exitosamente",
  "data": {
    "id": 1,
    "numero_orden": "OC-000001",
    "monto_subtotal": 8900.00,
    "monto_impuestos": 0,
    "monto_total": 8900.00,
    "proveedor": {...},
    "detalles": [...]
  }
}
```

### GET /ordenes-compra/{id}
Obtener orden con detalles, pagos, entradas de inventario y saldo pendiente

**Response incluye:**
```json
{
  "id": 1,
  "numero_orden": "OC-000001",
  "saldo_pendiente": 5000.00,
  "proveedor": {...},
  "detalles": [...],
  "pagos": [...],
  "entradas_inventario": [...]
}
```

### PUT/PATCH /ordenes-compra/{id}
Actualizar orden (solo en estado `borrador` o `pendiente`)

### DELETE /ordenes-compra/{id}
Eliminar orden (solo en estado `borrador`)

---

## 📊 Respuestas Estándar

### Éxito (200/201)
```json
{
  "message": "Operación exitosa",
  "data": {...}
}
```

### Error de Validación (422)
```json
{
  "message": "Error de validación",
  "errors": {
    "campo": ["El campo es requerido"]
  }
}
```

### No Encontrado (404)
```json
{
  "message": "Recurso no encontrado"
}
```

### Error del Servidor (500)
```json
{
  "message": "Error al procesar solicitud",
  "error": "Detalles del error"
}
```

---

## 🔒 Características de Seguridad

1. **Soft Deletes**: Todos los controladores usan soft delete (campos `activo` y `eliminado`)
2. **Validaciones**: Validación robusta en todos los endpoints
3. **Transacciones**: Operaciones complejas usan DB transactions
4. **Paginación**: Resultados paginados por defecto (15 registros)
5. **Eager Loading**: Carga optimizada de relaciones para evitar N+1
6. **Búsquedas**: Búsqueda por múltiples campos
7. **Filtros**: Filtros avanzados por empresa, sucursal, estado, etc.

---

## 🚀 Próximos Endpoints

- [ ] Inventario (entradas/salidas)
- [ ] Cuentas por cobrar/pagar
- [ ] Reportes
- [ ] Dashboard/Estadísticas
- [ ] Facturación Electrónica
- [ ] Nómina
- [ ] Contabilidad

---

## 📞 Soporte y Contacto

**Sistemas Ursol S.A.**
- **Email Corporativo**: sistemas@ursol.com
- **Email Técnico**: deadmooncr@gmail.com
- **WhatsApp**: +506 8868-7765
- **Web**: [ursol.com](https://ursol.com) | [ursol.net](https://ursol.net)
- **Repositorio**: [Ursol Reposit for Developers](https://sites.google.com/view/repdevursol/home/repositorio)
- **GitHub**: [github.com/SistemasUrsol](https://github.com/orgs/SistemasUrsol)
- **Desarrollador**: [Jeremy Arias Solano](https://github.com/jeremy-sud)

---

## 🐛 Reportar Issues

Para reportar errores o solicitar nuevas funcionalidades:
1. Accede a [GitHub Issues](https://github.com/SistemasUrsol/Ursol-CAST-API/issues)
2. Envía un correo a sistemas@ursol.com
3. Contacta por WhatsApp al +506 8868-7765

---

## 📝 Notas Importantes

1. Todos los endpoints requieren autenticación excepto `/login` y `/register`
2. Los timestamps usan campos personalizados: `creado_en` y `actualizado_en`
3. Multi-tenancy: Filtrar siempre por `empresa_id` cuando aplique
4. Números de comprobante/orden se generan automáticamente
5. Las validaciones de identificación única consideran el contexto de empresa
