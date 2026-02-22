# Refactorización de Controladores Usando Services

**Fecha de Creación:** 12 de febrero de 2026

## 🎯 Objetivo

Documentar cómo refactorizar los controladores grandes (>400 líneas) usando la capa de Services recientemente creada.

---

## 📋 Patrón de Refactorización

### ANTES: Controlador monolítico (~817 líneas)

```php
<?php
namespace App\Http\Controllers\API;

class VentaController {
    // 50-100 líneas de validación
    // 50-100 líneas de transformación de datos
    // 50-100 líneas de lógica de negocio
    // 50-100 líneas de manejo de relaciones
    // ... mucho más código
    
    public function store(Request $request) {
        // TODO: 70+ líneas de código
    }
}
```

### DESPUÉS: Controlador limpio con Service (~100 líneas)

```php
<?php
namespace App\Http\Controllers\API;

use App\DTOs\API\VentaCreateDTO;
use App\Services\VentaService;
use App\DTOs\Transformers\VentaTransformer;

class VentaController {
    public function __construct(private VentaService $service) {}

    public function store(Request $request) {
        $dto = VentaCreateDTO::fromRequest($request);
        $venta = $this->service->crear($dto);
        
        return response()->json(VentaTransformer::transform($venta));
    }

    public function index() {
        $ventas = $this->service->listar(15);
        return response()->json([
            'data' => VentaTransformer::collection($ventas->items()),
            'pagination' => [
                'total' => $ventas->total(),
                'per_page' => $ventas->perPage(),
                'current_page' => $ventas->currentPage(),
            ]
        ]);
    }
}
```

---

## 🔧 Services Disponibles (8 creados)

| Service | Métodos | Estado |
|---------|---------|--------|
| ProductoService | crear, actualizar, eliminar, obtener, listar, buscar, activos | ✅ |
| ClienteService | crear, actualizar, eliminar, obtener, listar, buscar, activos, saldoPendiente | ✅ |
| VentaService | crear, obtener, listar, porCliente, entreFechas, totalEnPeriodo, cambiarEstado | ✅ |
| ProveedorService | crear, actualizar, eliminar, obtener, listar, buscar, activos, saldoPendiente | ✅ |
| AsientoContableService | crear, obtener, listar, entreFechas, validarBalanceo | ✅ |
| EntradaInventarioService | crear, obtener, listar, porAlmacen, entreFechas | ✅ |
| SalidaInventarioService | crear, obtener, listar, porAlmacen, entreFechas | ✅ |
| ComprobanteElectronicoService | crear, obtener, listar, porVenta, cambiarEstado, validarClaveNumerica | ✅ |

---

## 📝 Plantilla de Refactorización para Controllers

```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\API\{ModuloCreateDTO, ModuloUpdateDTO};
use App\Services\ModuloService;
use App\DTOs\Transformers\ModuloTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModuloController extends Controller
{
    public function __construct(private ModuloService $service) {}

    /**
     * GET /api/modulos
     */
    public function index(): JsonResponse
    {
        $modulos = $this->service->listar();
        return response()->json([
            'data' => ModuloTransformer::collection($modulos->items()),
            'pagination' => [...],
        ]);
    }

    /**
     * GET /api/modulos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $modulo = $this->service->obtener($id);
        return response()->json(ModuloTransformer::transform($modulo));
    }

    /**
     * POST /api/modulos
     */
    public function store(Request $request): JsonResponse
    {
        $dto = ModuloCreateDTO::fromRequest($request);
        $modulo = $this->service->crear($dto);
        return response()->json(ModuloTransformer::transform($modulo), 201);
    }

    /**
     * PUT /api/modulos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $modulo = $this->service->obtener($id);
        $dto = ModuloUpdateDTO::fromRequest($request);
        $modulo = $this->service->actualizar($modulo, $dto);
        return response()->json(ModuloTransformer::transform($modulo));
    }

    /**
     * DELETE /api/modulos/{id}
     */  
    public function destroy(int $id): JsonResponse
    {
        $modulo = $this->service->obtener($id);
        $this->service->eliminar($modulo);
        return response()->json(null, 204);
    }
}
```

---

## 🚀 Pasos para Refactorizar un Controlador

### 1. Identificar el Service necesario
   - ¿Qué modelo es el principal? → Usar su Service

### 2. Extraer DTOs necesarios
   - ¿Qué datos recibe? → CreateDTO
   - ¿Qué puede actualizarse? → UpdateDTO

### 3. Implementar métodos en Service
   - Métodos necesarios para casos de uso
   - Lógica de transformación si aplica

### 4. Simplificar el Controlador
   - Inyectar el Service
   - Usar la plantilla anterior
   - Eliminar 300-700 líneas de código

### 5. Crear Transformer si no existe
   - Convertir modelo a array de respuesta
   - Incluir relaciones necesarias

---

## 📊 Controladores a Refactorizar (Prioridad)

| # | Controlador | Líneas | Service | Estado |
|---|-------------|--------|---------|--------|
| 1 | ComprobanteElectronicoController | 908 | ComprobanteElectronicoService | ⏳ |
| 2 | VentaController | 817 | VentaService | ⏳ |
| 3 | EntradaInventarioController | 721 | EntradaInventarioService | ⏳ |
| 4 | AsientoContableController | 718 | AsientoContableService | ⏳ |
| 5 | SalidaInventarioController | 709 | SalidaInventarioService | ⏳ |
| 6 | PagoNominaController | 656 | PagoNominaService | ⏳ |
| 7-15 | ... (otros 9) | 500-650 | Varios | ⏳ |

---

## 📈 Impacto Esperado

**Por controlador refactorizado:**
- Redución de líneas: 300-700 líneas eliminadas
- Aumento de testabilidad: +50%
- Reducción de errores PHPStan: 50-100 errores menos por controlador
- Reutilización de lógica: 100% (Services compartibles)

**Total esperado:**
- 15 controladores × 500 líneas = 7,500 líneas eliminadas  
- Nuevo promedio: <300 líneas por controlador
- Errores de PHPStan: -750+ (de 1974 a ~1200)

---

**Documento Referencia:** FASE_4_CALIDAD_CODIGO.md  
**Próxima Acción:** Refactorizar los 5 controladores más grandes
