# API REST - Ursol CAST API

**Desarrollado por Sistemas Ursol S.A.**  
*Soluciones Tecnológicas | Costa Rica | 30 años de experiencia*

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

### POST /login
Iniciar sesión

**Request:**
```json
{
  "email": "usuario@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "nombre": "Juan",
    "email": "usuario@example.com"
  }
}
```

### POST /register
Registrar nuevo usuario

**Request:**
```json
{
  "nombre": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "empresa_id": 1
}
```

### POST /logout
Cerrar sesión (requiere autenticación)

### GET /user
Obtener usuario autenticado

---

## 🏢 Empresas

### GET /empresas
Listar empresas con paginación

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

### DELETE /empresas/{id}
Eliminar empresa (soft delete)

---

## 🏪 Sucursales

### GET /sucursales
Listar sucursales

**Query Parameters:**
- `empresa_id` (int) - Filtrar por empresa
- `activos` (boolean) - Solo activos

### POST /sucursales
Crear sucursal

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

**Query Parameters:**
- `empresa_id` (int)
- `sucursal_id` (int)
- `activos` (boolean)

### POST /almacenes
Crear almacén

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

**Query Parameters:**
- `search` (string) - Buscar por nombre, apellidos, razón social, identificación, email
- `empresa_id` (int)
- `tipo_identificacion` (string) - "fisica", "juridica", "dimex", "nite", "extranjero"
- `activos` (boolean)

### POST /clientes
Crear cliente

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

**Query Parameters:**
- `search` (string)
- `empresa_id` (int)
- `activos` (boolean)

### POST /proveedores
Crear proveedor

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

**Query Parameters:**
- `empresa_id` (int)
- `sucursal_id` (int)
- `cliente_id` (int)
- `fecha_inicio` (date) - Formato: YYYY-MM-DD
- `fecha_fin` (date)

### POST /ventas
Crear venta con detalles

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
