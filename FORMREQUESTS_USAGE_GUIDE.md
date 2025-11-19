# Guía de Uso - FormRequests

## 📖 Cómo Usar los FormRequests

Esta guía muestra ejemplos prácticos de cómo usar los FormRequests implementados en la API.

---

## 1. Crear una Empresa (StoreEmpresaRequest)

### Request HTTP
```http
POST /api/empresas
Content-Type: application/json

{
    "nombre": "Mi Empresa S.A.",
    "razon_social": "Mi Empresa Sociedad Anónima",
    "nit_ruc": "3-101-123456",
    "regimen_tributario_id": 1,
    "email": "contacto@miempresa.com",
    "telefono": "2222-3333",
    "direccion": "San José, Costa Rica",
    "pais": "Costa Rica",
    "provincia": "San José",
    "ciudad": "San José",
    "codigo_postal": "10101",
    "sitio_web": "https://miempresa.com",
    "activo": true
}
```

### Validaciones Aplicadas
- ✅ `nombre` es requerido
- ✅ `nit_ruc` debe ser único en toda la BD
- ✅ `sitio_web` debe ser URL válida
- ✅ `email` formato email válido

### Respuesta Exitosa (201)
```json
{
    "message": "Empresa creada exitosamente",
    "data": {
        "id": 1,
        "nombre": "Mi Empresa S.A.",
        "razon_social": "Mi Empresa Sociedad Anónima",
        ...
    }
}
```

### Respuesta Error (422)
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "nit_ruc": [
            "El NIT/RUC ya ha sido registrado"
        ],
        "email": [
            "El formato del email no es válido"
        ]
    }
}
```

---

## 2. Crear un Cliente (StoreClienteRequest)

### Request HTTP
```http
POST /api/clientes
Content-Type: application/json

{
    "empresa_id": 1,
    "tipo_identificacion": "fisica",
    "identificacion": "1-0234-0567",
    "nombre": "Juan",
    "apellidos": "Pérez García",
    "email": "juan.perez@email.com",
    "telefono": "2222-1111",
    "celular": "8888-9999",
    "direccion": "Curridabat, San José",
    "provincia": "San José",
    "canton": "Curridabat",
    "distrito": "Curridabat",
    "limite_credito": 500000,
    "dias_credito": 30,
    "activo": true
}
```

### Validaciones Aplicadas
- ✅ `identificacion` única por `empresa_id` (callback withValidator)
- ✅ `tipo_identificacion` enum: fisica, juridica, dimex, nite, extranjero
- ✅ Mensajes personalizados en español

### Respuesta Error Única
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "identificacion": [
            "Ya existe un cliente con esta identificación en la empresa"
        ]
    }
}
```

---

## 3. Crear una Venta (StoreVentaRequest)

### Request HTTP
```http
POST /api/ventas
Content-Type: application/json

{
    "empresa_id": 1,
    "sucursal_id": 1,
    "cliente_id": 5,
    "usuario_id": 1,
    "forma_pago_id": 1,
    "fecha_venta": "2025-11-19",
    "tipo_comprobante": "factura",
    "observaciones": "Venta al contado",
    "detalles": [
        {
            "producto_id": 10,
            "cantidad": 2,
            "precio_unitario": 15000,
            "descuento": 500,
            "porcentaje_impuesto": 13,
            "descripcion": "Producto A"
        },
        {
            "producto_id": 11,
            "cantidad": 1,
            "precio_unitario": 25000,
            "descuento": 0,
            "porcentaje_impuesto": 13,
            "descripcion": "Producto B"
        }
    ]
}
```

### Validaciones Aplicadas
- ✅ **Validación de array** `detalles` con mínimo 1 elemento
- ✅ **Validación anidada** `detalles.*.producto_id` debe existir
- ✅ `tipo_comprobante` enum: factura, tiquete, nota_credito, nota_debito
- ✅ Cantidades y precios deben ser >= 0

### Respuesta Error en Detalles
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "detalles": [
            "Debe agregar al menos un detalle a la venta"
        ],
        "detalles.0.producto_id": [
            "El producto es obligatorio en cada detalle"
        ],
        "detalles.0.cantidad": [
            "La cantidad debe ser mayor a 0"
        ]
    }
}
```

---

## 4. Actualizar una Empresa (UpdateEmpresaRequest)

### Request HTTP
```http
PUT /api/empresas/1
Content-Type: application/json

{
    "nombre": "Mi Empresa Actualizada S.A.",
    "email": "nuevo@miempresa.com",
    "sitio_web": "https://nuevositio.com"
}
```

### Validaciones Aplicadas
- ✅ **Actualizaciones parciales** con `sometimes`
- ✅ **NIT/RUC único** excluyendo ID actual con `Rule::unique()->ignore()`
- ✅ Solo valida campos enviados

---

## 5. Crear una Orden de Compra (StoreOrdenCompraRequest)

### Request HTTP
```http
POST /api/ordenes-compra
Content-Type: application/json

{
    "empresa_id": 1,
    "proveedor_id": 3,
    "usuario_id": 1,
    "fecha_orden": "2025-11-19",
    "fecha_entrega_estimada": "2025-11-26",
    "estado": "pendiente",
    "observaciones": "Urgente",
    "detalles": [
        {
            "producto_id": 20,
            "cantidad": 50,
            "precio_unitario": 1200,
            "descuento": 100,
            "descripcion": "Compra mayorista"
        }
    ]
}
```

### Validaciones Aplicadas
- ✅ `fecha_entrega_estimada` >= `fecha_orden`
- ✅ `estado` enum: borrador, pendiente, aprobada, recibida, cancelada
- ✅ Validación de array detalles

---

## 6. Actualizar una Orden de Compra (UpdateOrdenCompraRequest)

### Request HTTP
```http
PUT /api/ordenes-compra/5
Content-Type: application/json

{
    "estado": "aprobada",
    "observaciones": "Aprobado por gerencia"
}
```

### Validaciones Aplicadas
- ✅ **Callback withValidator** verifica que estado sea borrador/pendiente
- ✅ Solo permite actualizar si orden está en estados editables

### Respuesta Error de Workflow
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "estado": [
            "Solo se pueden editar órdenes en estado borrador o pendiente"
        ]
    }
}
```

---

## 7. Crear una Sucursal Principal (StoreSucursalRequest)

### Request HTTP
```http
POST /api/sucursales
Content-Type: application/json

{
    "empresa_id": 1,
    "nombre": "Casa Matriz",
    "codigo": "CM-01",
    "telefono": "2222-3333",
    "email": "matriz@miempresa.com",
    "direccion": "San José Centro",
    "provincia": "San José",
    "canton": "San José",
    "distrito": "Carmen",
    "es_principal": true,
    "activo": true
}
```

### Validaciones Aplicadas
- ✅ **Callback withValidator** verifica solo 1 sucursal principal por empresa
- ✅ Si `es_principal` = true y ya existe otra, retorna error

### Respuesta Error Sucursal Principal
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "es_principal": [
            "Ya existe una sucursal principal para esta empresa"
        ]
    }
}
```

---

## 8. Patrones de Uso en Controladores

### Antes (sin FormRequest)
```php
public function store(Request $request)
{
    // 20-30 líneas de validación
    $validator = Validator::make($request->all(), [...]);
    
    if ($validator->fails()) {
        return response()->json([...], 422);
    }
    
    // Lógica de negocio
    $model = Model::create($request->all());
    
    return response()->json([...], 201);
}
```

### Después (con FormRequest)
```php
public function store(StoreModelRequest $request)
{
    // Validación automática ✅
    // Solo lógica de negocio
    $model = Model::create($request->validated());
    
    return response()->json([
        'message' => 'Creado exitosamente',
        'data' => $model
    ], 201);
}
```

**Beneficios:**
- 🎯 Controlador más limpio (5-10 líneas vs 30-40)
- ✅ Validación automática antes de ejecutar método
- 🔒 Type-safe con type-hinting
- ♻️ Reutilizable en tests y otros lugares

---

## 9. Testing con FormRequests

### PHPUnit Example
```php
use App\Http\Requests\StoreEmpresaRequest;
use Illuminate\Support\Facades\Validator;

public function test_store_empresa_validation()
{
    $request = new StoreEmpresaRequest();
    
    $validator = Validator::make([
        'nombre' => '',
        'nit_ruc' => '123',
    ], $request->rules());
    
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
}
```

---

## 10. Extensiones Futuras

### Custom Rule Class
```php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidNitRuc implements Rule
{
    public function passes($attribute, $value)
    {
        // Validación personalizada de NIT/RUC
        return preg_match('/^\d-\d{3}-\d{6}$/', $value);
    }
    
    public function message()
    {
        return 'El formato del NIT/RUC no es válido (ej: 3-101-123456)';
    }
}
```

### Uso en FormRequest
```php
use App\Rules\ValidNitRuc;

public function rules(): array
{
    return [
        'nit_ruc' => ['required', new ValidNitRuc(), 'unique:empresas'],
    ];
}
```

---

## 📊 Mensajes de Error Personalizados

### StoreClienteRequest
```php
public function messages(): array
{
    return [
        'tipo_identificacion.in' => 'Tipo de identificación inválido',
        'nombre.required' => 'El nombre es obligatorio',
        'email.email' => 'El formato del email no es válido',
        'limite_credito.min' => 'El límite de crédito debe ser mayor o igual a 0',
    ];
}
```

### Respuesta con Mensajes Personalizados
```json
{
    "message": "Los datos proporcionados no son válidos",
    "errors": {
        "nombre": ["El nombre es obligatorio"],
        "email": ["El formato del email no es válido"]
    }
}
```

---

## 🎓 Mejores Prácticas

### 1. Usar `validated()` en lugar de `all()`
```php
// ❌ Incorrecto
$model = Model::create($request->all());

// ✅ Correcto
$model = Model::create($request->validated());
```

### 2. Separar Store y Update
```php
// ✅ Correcto - FormRequests separados
public function store(StoreModelRequest $request) { ... }
public function update(UpdateModelRequest $request, $id) { ... }

// ❌ Incorrecto - Un solo FormRequest para ambos
public function store(ModelRequest $request) { ... }
public function update(ModelRequest $request, $id) { ... }
```

### 3. Usar `withValidator()` para lógica compleja
```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // Validaciones que requieren consultas a BD
        // o lógica de negocio compleja
    });
}
```

### 4. Mensajes y Atributos Personalizados
```php
public function messages(): array
{
    return [
        'nit_ruc.unique' => 'El NIT/RUC ya ha sido registrado',
    ];
}

public function attributes(): array
{
    return [
        'nit_ruc' => 'NIT/RUC',
    ];
}
```

---

## 📝 Resumen de Endpoints

| Método | Endpoint | FormRequest |
|--------|----------|-------------|
| POST | `/api/empresas` | `StoreEmpresaRequest` |
| PUT | `/api/empresas/{id}` | `UpdateEmpresaRequest` |
| POST | `/api/productos` | `StoreProductoRequest` |
| PUT | `/api/productos/{id}` | `UpdateProductoRequest` |
| POST | `/api/clientes` | `StoreClienteRequest` |
| PUT | `/api/clientes/{id}` | `UpdateClienteRequest` |
| POST | `/api/proveedores` | `StoreProveedorRequest` |
| PUT | `/api/proveedores/{id}` | `UpdateProveedorRequest` |
| POST | `/api/sucursales` | `StoreSucursalRequest` |
| PUT | `/api/sucursales/{id}` | `UpdateSucursalRequest` |
| POST | `/api/almacenes` | `StoreAlmacenRequest` |
| PUT | `/api/almacenes/{id}` | `UpdateAlmacenRequest` |
| POST | `/api/ventas` | `StoreVentaRequest` |
| PUT | `/api/ventas/{id}` | `UpdateVentaRequest` |
| POST | `/api/ordenes-compra` | `StoreOrdenCompraRequest` |
| PUT | `/api/ordenes-compra/{id}` | `UpdateOrdenCompraRequest` |

---

**Desarrollado por Sistemas Ursol S.A. - 30 años de experiencia**

*Última actualización: 19 de Noviembre de 2025*
