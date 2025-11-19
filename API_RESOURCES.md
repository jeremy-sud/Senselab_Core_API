# API Resources - Documentación

## 📋 Descripción General

Las **API Resources** son clases que transforman los modelos Eloquent en respuestas JSON consistentes y controladas. Proporcionan una capa de presentación que separa la lógica de negocio de la representación de datos.

## 🎯 Ventajas de Usar API Resources

### ✅ **Separación de Responsabilidades**
- Desacopla la estructura interna del modelo de la respuesta de la API
- Facilita cambios en la base de datos sin afectar contratos de API

### ✅ **Consistencia**
- Respuestas uniformes en toda la API
- Control total sobre qué datos se exponen

### ✅ **Rendimiento**
- Carga condicional de relaciones (`whenLoaded`)
- Transformación eficiente de datos
- Prevención de exposición accidental de datos sensibles

### ✅ **Mantenibilidad**
- Código más limpio en controladores
- Fácil de actualizar y extender
- Mejor para testing

---

## 📁 Resources Implementadas

### 1. **EmpresaResource**

**Ubicación:** `app/Http/Resources/EmpresaResource.php`

**Campos Expuestos:**
```json
{
  "id": 1,
  "nombre_comercial": "Mi Empresa S.A.",
  "razon_social": "Mi Empresa Sociedad Anónima",
  "identificacion": "3-101-123456",
  "tipo_identificacion": "juridica",
  "regimen_tributario_id": 1,
  "regimen_tributario": {
    "id": 1,
    "nombre": "Tradicional",
    "codigo": "01"
  },
  "telefono": "2222-3333",
  "email": "info@miempresa.com",
  "sitio_web": "https://miempresa.com",
  "direccion": "San José, Costa Rica",
  "provincia": "San José",
  "canton": "Central",
  "distrito": "Carmen",
  "logo_url": "/logos/empresa.png",
  "activo": true,
  "configuracion": {},
  "sucursales_count": 3,
  "usuarios_count": 10,
  "productos_count": 150,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

**Relaciones Condicionales:**
- `regimen_tributario`: Solo si está cargada
- `sucursales_count`, `usuarios_count`, `productos_count`: Solo si están contadas

---

### 2. **ProductoResource**

**Ubicación:** `app/Http/Resources/ProductoResource.php`

**Campos Expuestos:**
```json
{
  "id": 100,
  "empresa_id": 1,
  "codigo": "PROD-001",
  "codigo_barras": "7501234567890",
  "nombre": "Producto de Ejemplo",
  "descripcion": "Descripción del producto",
  "categoria_id": 5,
  "categoria": {
    "id": 5,
    "nombre": "Electrónica"
  },
  "marca_id": 10,
  "marca": {
    "id": 10,
    "nombre": "Samsung"
  },
  "unidad_medida_id": 1,
  "unidad_medida": {
    "id": 1,
    "nombre": "Unidad",
    "simbolo": "UND"
  },
  "precio_costo": 50000.00,
  "precio_venta": 75000.00,
  "precio_mayoreo": 65000.00,
  "stock_minimo": 10,
  "stock_maximo": 100,
  "tipo_impuesto_id": 1,
  "tipo_impuesto": {
    "id": 1,
    "nombre": "IVA 13%",
    "porcentaje": 13.00
  },
  "exento_impuesto": false,
  "tipo_producto": "producto",
  "imagen_url": "/productos/prod-001.jpg",
  "activo": true,
  "gestionado_inventario": true,
  "stock_total": 85,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

**Cálculos Especiales:**
- `stock_total`: Suma de `cantidad_disponible` de todos los inventarios (si están cargados)

---

### 3. **ClienteResource**

**Ubicación:** `app/Http/Resources/ClienteResource.php`

**Campos Expuestos:**
```json
{
  "id": 50,
  "empresa_id": 1,
  "tipo_identificacion": "fisica",
  "identificacion": "1-1234-5678",
  "nombre": "Juan Pérez González",
  "nombre_comercial": null,
  "email": "juan.perez@example.com",
  "telefono": "2222-3333",
  "celular": "8888-9999",
  "direccion": "San José, 100m norte de...",
  "provincia": "San José",
  "canton": "Central",
  "distrito": "Carmen",
  "codigo_postal": "10101",
  "tipo_cliente": "contado",
  "limite_credito": null,
  "dias_credito": 0,
  "descuento_general": 0.00,
  "activo": true,
  "observaciones": null,
  "ventas_count": 15,
  "cuentas_por_cobrar_count": 3,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

---

### 4. **ProveedorResource**

**Ubicación:** `app/Http/Resources/ProveedorResource.php`

**Campos Expuestos:**
```json
{
  "id": 25,
  "empresa_id": 1,
  "tipo_identificacion": "juridica",
  "identificacion": "3-101-987654",
  "nombre": "Proveedor XYZ S.A.",
  "nombre_comercial": "XYZ",
  "email": "ventas@xyz.com",
  "telefono": "2222-4444",
  "celular": "8888-7777",
  "direccion": "Heredia, Costa Rica",
  "provincia": "Heredia",
  "canton": "Central",
  "distrito": "Mercedes",
  "codigo_postal": "40101",
  "contacto_nombre": "María Rodríguez",
  "contacto_email": "maria@xyz.com",
  "contacto_telefono": "8888-6666",
  "dias_credito": 30,
  "activo": true,
  "observaciones": null,
  "productos": [...],
  "ordenes_compra_count": 8,
  "cuentas_por_pagar_count": 2,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

**Nota:** `productos` incluye collection completa de `ProductoResource` si está cargada.

---

### 5. **SucursalResource**

**Ubicación:** `app/Http/Resources/SucursalResource.php`

**Campos Expuestos:**
```json
{
  "id": 3,
  "empresa_id": 1,
  "codigo": "SUC-001",
  "nombre": "Sucursal Central",
  "telefono": "2222-5555",
  "email": "central@empresa.com",
  "direccion": "San José, Avenida Central",
  "provincia": "San José",
  "canton": "Central",
  "distrito": "Carmen",
  "es_principal": true,
  "activo": true,
  "empresa": {
    "id": 1,
    "nombre_comercial": "Mi Empresa S.A.",
    "razon_social": "Mi Empresa Sociedad Anónima"
  },
  "almacenes_count": 2,
  "cajas_count": 3,
  "ventas_count": 450,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

---

### 6. **AlmacenResource**

**Ubicación:** `app/Http/Resources/AlmacenResource.php`

**Campos Expuestos:**
```json
{
  "id": 5,
  "empresa_id": 1,
  "sucursal_id": 3,
  "codigo": "ALM-001",
  "nombre": "Almacén Principal",
  "descripcion": "Almacén general de productos",
  "ubicacion": "Bodega A, Estante 1",
  "tipo_almacen": "general",
  "activo": true,
  "sucursal": {
    "id": 3,
    "nombre": "Sucursal Central",
    "codigo": "SUC-001"
  },
  "inventarios_count": 120,
  "valor_total_inventario": 5000000.00,
  "creado_en": "2024-01-15T10:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:25:00.000000Z"
}
```

**Cálculos Especiales:**
- `valor_total_inventario`: Suma de (cantidad_disponible × precio_costo) de todos los inventarios

---

### 7. **VentaResource**

**Ubicación:** `app/Http/Resources/VentaResource.php`

**Campos Expuestos:**
```json
{
  "id": 200,
  "empresa_id": 1,
  "sucursal_id": 3,
  "numero_venta": "FAC-00000125",
  "fecha_venta": "2024-01-20T14:30:00.000000Z",
  "cliente_id": 50,
  "cliente": {
    "id": 50,
    "nombre": "Juan Pérez González",
    "identificacion": "1-1234-5678",
    "email": "juan.perez@example.com"
  },
  "usuario_id": 5,
  "usuario": {
    "id": 5,
    "nombre": "Carlos Vendedor",
    "email": "carlos@empresa.com"
  },
  "tipo_venta": "contado",
  "forma_pago_id": 1,
  "forma_pago": {
    "id": 1,
    "nombre": "Efectivo"
  },
  "subtotal": 100000.00,
  "descuento": 5000.00,
  "impuestos": 12350.00,
  "total": 107350.00,
  "estado": "completada",
  "observaciones": null,
  "detalles": [
    {
      "id": 500,
      "producto_id": 100,
      "producto_nombre": "Producto de Ejemplo",
      "producto_codigo": "PROD-001",
      "cantidad": 2.00,
      "precio_unitario": 50000.00,
      "descuento": 5000.00,
      "impuesto": 12350.00,
      "subtotal": 100000.00,
      "total": 107350.00
    }
  ],
  "factura_electronica": {
    "id": 150,
    "clave_numerica": "50621012400010112340001001234567891234567891",
    "estado": "aceptado"
  },
  "creado_en": "2024-01-20T14:30:00.000000Z",
  "actualizado_en": "2024-01-20T14:35:00.000000Z"
}
```

---

### 8. **OrdenCompraResource**

**Ubicación:** `app/Http/Resources/OrdenCompraResource.php`

**Campos Expuestos:**
```json
{
  "id": 75,
  "empresa_id": 1,
  "numero_orden": "OC-000050",
  "fecha_orden": "2024-01-18T09:00:00.000000Z",
  "fecha_entrega_estimada": "2024-01-25T00:00:00.000000Z",
  "proveedor_id": 25,
  "proveedor": {
    "id": 25,
    "nombre": "Proveedor XYZ S.A.",
    "identificacion": "3-101-987654",
    "email": "ventas@xyz.com"
  },
  "almacen_id": 5,
  "almacen": {
    "id": 5,
    "nombre": "Almacén Principal",
    "codigo": "ALM-001"
  },
  "usuario_id": 5,
  "usuario": {
    "id": 5,
    "nombre": "Carlos Comprador",
    "email": "carlos@empresa.com"
  },
  "subtotal": 500000.00,
  "descuento": 25000.00,
  "impuestos": 61750.00,
  "total": 536750.00,
  "estado": "aprobada",
  "observaciones": "Urgente para producción",
  "detalles": [
    {
      "id": 200,
      "producto_id": 100,
      "producto_nombre": "Producto de Ejemplo",
      "producto_codigo": "PROD-001",
      "cantidad": 10.00,
      "precio_unitario": 50000.00,
      "descuento": 25000.00,
      "impuesto": 61750.00,
      "subtotal": 475000.00,
      "total": 536750.00
    }
  ],
  "creado_en": "2024-01-18T09:00:00.000000Z",
  "actualizado_en": "2024-01-19T10:30:00.000000Z",
  "aprobado_en": "2024-01-19T10:30:00.000000Z",
  "recibido_en": null
}
```

---

## 🔧 Uso en Controladores

### **Respuesta Individual**
```php
public function show($id)
{
    $empresa = Empresa::with('regimenTributario')->findOrFail($id);
    
    return new EmpresaResource($empresa);
}
```

### **Colección Paginada**
```php
public function index()
{
    $empresas = Empresa::paginate(15);
    
    return EmpresaResource::collection($empresas);
}
```

### **Con Mensaje Adicional**
```php
public function store(Request $request)
{
    $empresa = Empresa::create($request->validated());
    
    return (new EmpresaResource($empresa))
        ->additional(['message' => 'Empresa creada exitosamente'])
        ->response()
        ->setStatusCode(201);
}
```

---

## 📊 Carga Condicional de Relaciones

Las Resources utilizan métodos condicionales para optimizar rendimiento:

### **whenLoaded()**
Solo incluye la relación si ya fue cargada con `->with()`:
```php
'regimen_tributario' => $this->whenLoaded('regimenTributario', function () {
    return [
        'id' => $this->regimenTributario->id,
        'nombre' => $this->regimenTributario->nombre,
    ];
}),
```

### **whenCounted()**
Solo incluye el conteo si se usó `->withCount()`:
```php
'sucursales_count' => $this->whenCounted('sucursales'),
```

### **when()**
Condición personalizada:
```php
'stock_total' => $this->when(
    $this->relationLoaded('inventarios'),
    fn() => $this->inventarios->sum('cantidad_disponible')
),
```

---

## 🎨 Personalización de Respuestas

### **Respuesta de Colección con Metadata**
```php
return ProductoResource::collection($productos)->additional([
    'meta' => [
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]
]);
```

### **Respuesta Wrapper Personalizada**
En `app/Http/Resources/EmpresaResource.php`:
```php
public function with($request)
{
    return [
        'success' => true,
        'timestamp' => now()->toISOString(),
    ];
}
```

---

## ✅ Buenas Prácticas

### ✅ **DO - Hacer**
- ✅ Usar Resources para todas las respuestas de API
- ✅ Cargar relaciones antes con `->with()` y usar `whenLoaded()`
- ✅ Convertir tipos explícitamente: `(float)`, `(int)`, `(bool)`
- ✅ Usar `toISOString()` para fechas
- ✅ Documentar campos especiales en docblocks

### ❌ **DON'T - No Hacer**
- ❌ Devolver modelos directamente con `response()->json($model)`
- ❌ Exponer campos sensibles (passwords, tokens)
- ❌ Hacer queries adicionales dentro de Resources (N+1 problem)
- ❌ Mezclar lógica de negocio en Resources

---

## 🚀 Próximos Pasos

### 1. **Crear Resources para Controladores Faltantes**
   - EmpleadoResource
   - RolResource / PermisoResource
   - CategoriaProductoResource
   - MarcaResource
   - FormaPagoResource
   - UnidadMedidaResource
   - InventarioResource
   - CuentaPorCobrarResource
   - CuentaPorPagarResource

### 2. **Implementar Resource Collections Personalizadas**
```php
php artisan make:resource --collection ProductoCollection
```

### 3. **Agregar Paginación Personalizada**
```php
class ProductoResource extends JsonResource
{
    public static function customPagination($paginator)
    {
        return [
            'data' => static::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
```

---

## 📚 Referencias

- [Laravel Resources Documentation](https://laravel.com/docs/11.x/eloquent-resources)
- [API Resource Responses](https://laravel.com/docs/11.x/eloquent-resources#resource-responses)
- [Conditional Attributes](https://laravel.com/docs/11.x/eloquent-resources#conditional-attributes)

---

**Desarrollado por Sistemas Ursol S.A.**  
**Autor:** Jeremy Arias Solano  
**Fecha:** Noviembre 2024  
**Versión:** 1.0.0
