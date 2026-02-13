# 📊 FASE 4: CALIDAD DE CÓDIGO
## Ursol CAST API - Refactorización y Mejora de Arquitectura

**Fecha de Inicio:** 12 de febrero de 2026  
**Fecha de Actualización:** 13 de febrero de 2026  
**Semanas Estimadas:** 4-5 semanas  
**Horas Estimadas:** 45-55 horas de desarrollo  
**Equipo:** 1-2 desarrolladores  
**PRIORIDAD:** ALTA - Mejora fundamental de calidad  

---

## 📋 TABLA DE ENTREGABLES

| # | Entregable | Estado | Horas | Fecha |
|---|-----------|--------|-------|-------|
| 4.1 | Reducir PHPStan errores: 1974 → <30 | ⏳ | 8-10h | - |
| 4.2 | Refactorizar controladores > 400 líneas (15 archivos) | ⏳ | 15-18h | - |
| 4.3 | Implementar capa DTO explícita | ⏳ | 10-12h | - |
| 4.4 | Aumentar test coverage a >80% | ⏳ | 8-10h | - |
| 4.5 | Resolver todos los issues de SonarQube | ⏳ | 4-5h | - |
| | **TOTAL FASE 4** | | **45-55h** | |

---

# 4.1 REDUCIR PHPSTAN ERRORES (1974 → <30)

## Estado Actual (12 feb 2026)

```
Errores Totales: 1974
Baseline Errores Ignorados: 8
Meta: < 30 errores totales

Distribución de Errores por Tipo:
- 785  × missingType.iterableValue
- 292  × missingType.parameter
- 215  × missingType.return
-  97  × return.type
-  53  × property.phpDocType
-  48  × missingType.property
-  46  × class.notFound
-  41  × missingType.generics
-  35  × missingType.parameter (duplicado)
-  32  × missingType.return (duplicado)
-  23  × return.phpDocType
-  22  × class.nameCase
-  21  × class.notFound (duplicado)
-  20  × missingType.parameter (duplicado)
-  19  × missingType.return (duplicado)
-  18  × larastan.relationExistence
-  12  × parameter.phpDocType
-  12  × method.nonObject
-  11  × missingType.property (duplicado)
-  10  × assign.propertyType
- Otros: ~250 errores variados
```

## Estrategia de Resolución

**Enfoque:** Type hints progressivo + Baseline consolidado

### Paso 1-3: Agregar Type Hints a Nivel Global (6-7h)

Ejecutar refactoring automático para añadir type hints:

```php
// ANTES: Sin tipos
public function create($data) {
    $model = Model::create($data);
    return $model;
}

// DESPUÉS: Con tipos
public function create(array $data): Model {
    $model = Model::create($data);
    return $model;
}
```

**Tareas:**
- Añadir return types a todos los métodos públicos
- Añadir parameter types a todos los parámetros
- Typed properties en modelos
- PHPDoc completo para propiedades dinámicas

### Paso 4-5: Actualizar phpstan-baseline.neon (2-3h)

Consolidar los errores que no se pueden resolver rápidamente:

```neon
parameters:
    ignoreErrors:
        -
            message: '#^Class .* not found\\.$#'
            identifier: class.notFound
            # Solo para dependencias externas sin tipos
            path: vendor/
```

### Timeline: 8-10 horas

---

# 4.2 REFACTORIZAR CONTROLADORES > 400 LÍNEAS

## Controladores a Refactorizar (12 feb 2026)

| # | Controlador | Líneas Act. | Meta | Técnica |
|-|---|---|---|---|
| 1 | ComprobanteElectronicoController | 908 | <400 | Service + Query |
| 2 | VentaController | 817 | <400 | Service + DTO |
| 3 | EntradaInventarioController | 721 | <400 | Service |
| 4 | AsientoContableController | 718 | <400 | Service |
| 5 | SalidaInventarioController | 709 | <400 | Service |
| 6 | PagoNominaController | 656 | <400 | Service |
| 7 | HorarioRutaController | 645 | <400 | Service |
| 8 | ComprobanteRecibidoController | 630 | <400 | Service |
| 9 | PagoController | 626 | <400 | Service |
| 10 | TasaImpuestoController | 622 | <400 | Service |
| 11 | PeriodoNominaController | 615 | <400 | Service |
| 12 | InventarioController | 605 | <400 | Service |
| 13 | RutaController | 589 | <400 | Service |
| 14 | PresupuestoController | 580 | <400 | Service |
| 15 | CuentaContableController | 579 | <400 | Service |

## Patrones de Refactorización

### Patrón 1: Extract Service Class
```php
// ANTES: Todo en el controlador (50+ líneas de lógica)
class VentaController {
    public function store(Request $request) {
        $validaciones = [...];
        $cálculos = [...];
        $transacciones = [...];
        // 70 líneas total
    }
}

// DESPUÉS: Lógica en Service
class VentaService {
    public function crearVenta(array $datos): Venta {
        // Lógica de negocio separada
    }
}

class VentaController {
    public function __construct(private VentaService $service) {}
    
    public function store(Request $request) {
        return response()->json(
            $this->service->crearVenta($request->validated())
        );
    }
}
```

### Patrón 2: Extract Query Logic
```php
// Queries complejas en Query Builders
class VentaQueryBuilder {
    public function byPeriodo($inicio, $fin) {
        return Venta::whereBetween('created_at', [$inicio, $fin])
            ->with(['cliente', 'detalles'])
            ->orderByDesc('created_at');
    }
}
```

### Patrón 3: Extract Data Transformation
```php
// Transformers para respuestas
class VentaTransformer {
    public function transform(Venta $venta): array {
        return [
            'id' => $venta->id,
            'total' => $venta->calcularTotal(),
            'cliente' => $venta->cliente?->nombre,
        ];
    }
}
```

## Timeline: 15-18 horas

---

# 4.3 IMPLEMENTAR CAPA DTO EXPLÍCITA

## Estructura de DTOs a Crear

```
app/DTOs/
├── API/
│   ├── ProductoCreateDTO.php
│   ├── ProductoUpdateDTO.php
│   ├── VentaCreateDTO.php
│   ├── ClienteDTO.php
│   └── (20+ más)
├── Responses/
│   ├── PaginatedResponseDTO.php
│   └── ErrorResponseDTO.php
└── Transformers/
    └── (10+ transformers)
```

## Ejemplo: ProductoCreateDTO

```php
<?php
namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ProductoCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly float $precio,
        public readonly int $stock_inicial,
        public readonly int $categoria_id,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim(),
            descripcion: $request->string('descripcion')->trim(),
            precio: $request->float('precio'),
            stock_inicial: $request->integer('stock_inicial'),
            categoria_id: $request->integer('categoria_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'stock_inicial' => $this->stock_inicial,
            'categoria_id' => $this->categoria_id,
        ];
    }
}
```

## DTOs Prioritarios

1. **Módulo Ventas** (3-4 DTOs)
2. **Módulo Inventario** (3-4 DTOs)
3. **Módulo Maestros** (4-5 DTOs)
4. **Módulo Contabilidad** (2-3 DTOs)
5. **Módulo Comprobantes FE** (2-3 DTOs)

## Timeline: 10-12 horas

---

# 4.4 AUMENTAR TEST COVERAGE A >80%

## Estado Actual (12 feb 2026)

```
Archivos de Test: 47
Test Cases: ~150 (estimado)
Coverage: ~25-30% (estimado)

Meta: > 80% de cobertura
```

## Áreas Críticas sin Tests

1. **Controllers** - Validaciones, errores, autenticación
2. **Services** - Lógica de negocio, cálculos complejos
3. **Models** - Relationships, Scopes, Mutators

## Tests a Crear (Prioridad)

| Módulo | Tests Necesarios | Horas |
|--------|-----------------|-------|
| Ventas | 15-20 | 4-5h |
| Inventario | 12-15 | 3-4h |
| Maestros | 8-10 | 2-3h |
| Contabilidad | 10-12 | 2-3h |
| Comprobantes FE | 8-10 | 2-3h |
| Nómina | 6-8 | 1-2h |

## Timeline: 8-10 horas

---

# 4.5 RESOLVER TODOS LOS ISSUES DE SONARQUBE

## Categorías de Issues Comunes

1. **Code Smells** (~40-50 issues)
   - Métodos muy largos
   - Complejidad ciclomática alta
   - Duplicación de código

2. **Bugs** (~10-15 issues)
   - Posibles null pointers
   - Recursos no cerrados

3. **Security Issues** (~5-10 issues)
   - Validación incompleta
   - Logging inseguro

## Timeline: 4-5 horas

---

# 📅 CRONOGRAMA FASE 4

```
Semana 1-1.5: 4.1 PHPStan (8-10h)
             4.2 Refactorización Inicial (5-7h)

Semana 1.5-2: 4.2 Refactorización Continuación (10-11h)

Semana 2-2.5: 4.3 DTOs (10-12h)

Semana 2.5-3: 4.4 Tests (8-10h)

Semana 3-3.5: 4.5 SonarQube (4-5h)
             Testing & Validación (3-4h)

Semana 3.5-4: Revisión Final & Optimizaciones
```

---

**Documentación Completada:** 12 de febrero de 2026  
**Estado:** Listos para ejecutar tareas  
**Siguiente Paso:** Iniciar 4.1 - Reducir PHPStan errores
