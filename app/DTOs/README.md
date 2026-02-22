# DTOs (Data Transfer Objects) - Ursol CAST API

**Creado:** 12 de febrero de 2026  
**Propósito:** Separar las capas de presentación, validación y lógica de negocio  

## 📁 Estructura

```
app/DTOs/
├── API/                         # DTOs para endpoints API
│   ├── ProductoCreateDTO.php
│   ├── ProductoUpdateDTO.php
│   ├── ClienteCreateDTO.php
│   └── (20+ más)
├── Responses/                   # DTOs para respuestas estandarizadas
│   ├── PaginatedResponseDTO.php
│   └── ErrorResponseDTO.php
└── Transformers/                # Transformadores de datos
    └── (DTOs para transformar modelos a arrays)
```

## ✨ Ventajas de Usar DTOs

### 1. **Separación de Responsabilidades**
```php
// SIN DTO: Request mesclado con lógica
$producto = Producto::create($request->validated());

// CON DTO: Validación y transformación clara
$dto = ProductoCreateDTO::fromRequest($request);
$producto = $this->productoService->crear($dto);
```

### 2. **Type Safety**
```php
// El IDE sabe exactamente qué propiedades puede acceder
public readonly string $nombre;
public readonly float $precio;
```

### 3. **Reutilización**
```php
// Mismo DTO usado en múltiples controladores
$dto = ProductoCreateDTO::fromRequest($request);
// Tanto en HTTP como en API
```

### 4. **Validación Centralizada**
```php
// Las reglas de validación están en el FormRequest
// El DTO solo transforma datos válidos
```

## 📝 Cómo Crear un DTO

### Paso 1: Crear el archivo
```php
<?php
namespace App\DTOs\API;

final class MiDTO
{
    public function __construct(
        public readonly string $campo1,
        public readonly int $campo2,
        public readonly ?string $opcional = null,
    ) {}
}
```

### Paso 2: Agregar método fromRequest()
```php
public static function fromRequest(Request $request): self
{
    return new self(
        campo1: $request->string('campo1')->trim(),
        campo2: $request->integer('campo2'),
        opcional: $request->string('opcional')?->trim(),
    );
}
```

### Paso 3: Agregar método toArray()
```php
public function toArray(): array
{
    return [
        'campo1' => $this->campo1,
        'campo2' => $this->campo2,
        'opcional' => $this->opcional,
    ];
}
```

## 🚀 Uso en Controladores

```php
<?php
namespace App\Http\Controllers\API;

use App\DTOs\API\ProductoCreateDTO;
use App\Services\ProductoService;

class ProductoController extends Controller
{
    public function __construct(private ProductoService $service) {}

    public function store(Request $request)
    {
        // 1. Crear DTO desde Request validado
        $dto = ProductoCreateDTO::fromRequest($request);
        
        // 2. Pasar al servicio
        $producto = $this->service->crear($dto);
        
        // 3. Retornar respuesta
        return response()->json($producto);
    }
}
```

## 🛠️ Uso en Services

```php
<?php
namespace App\Services;

use App\DTOs\API\ProductoCreateDTO;
use App\Models\Producto;

class ProductoService
{
    public function crear(ProductoCreateDTO $dto): Producto
    {
        // La lógica de negocio es clara y testeable
        return Producto::create($dto->toArray());
    }
}
```

## 📋 DTOs a Implementar (Prioritarios)

**Fase 4.3 (12 feb - 26 feb 2026):**

### Módulo Ventas (3-4 DTOs)
- [x] VentaCreateDTO
- [ ] VentaUpdateDTO
- [ ] VentaDetalleDTO

### Módulo Inventario (3-4 DTOs)
- [ ] EntradaInventarioDTO
- [ ] SalidaInventarioDTO
- [ ] AjusteInventarioDTO

### Módulo Maestros (4-5 DTOs)
- [x] ProductoCreateDTO
- [x] ProductoUpdateDTO
- [x] ClienteCreateDTO
- [ ] ClienteUpdateDTO
- [ ] ProveedorCreateDTO

### Módulo Contabilidad (2-3 DTOs)
- [ ] AsientoContableDTO
- [ ] DetalleAsientoDTO

### Módulo Comprobantes FE (2-3 DTOs)
- [ ] ComprobanteElectronicoDTO
- [ ] ComprobanteRecibidoDTO

### Respuestas (2 DTOs)
- [x] PaginatedResponseDTO
- [x] ErrorResponseDTO

## ✅ Checklist para Nuevos DTOs

- [ ] Cambios todas propiedades con `public readonly`
- [ ] Propiedades opcionales con valor por defecto `null`
- [ ] Implementar método `fromRequest()` 
- [ ] Implementar método `toArray()`
- [ ] Agregar docblock/comentarios
- [ ] Validaciones en FormRequest correspondiente
- [ ] Tests unitarios para DTO

## 📚 Referencias

- [Laravel Best Practices - DTOs](https://laravel.com/docs)
- [FASE_4_CALIDAD_CODIGO.md](../../docs/archive/FASE_4_CALIDAD_CODIGO.md)

---

**Documentación Actualizada:** 12 de febrero de 2026
