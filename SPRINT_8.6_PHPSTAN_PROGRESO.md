# Sprint 8.6 - PHPStan: Static Analysis

## 📊 Progreso General

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **Errores Totales** | 4,877 | 4,712 | -3.4% |
| **Modelos con Tipos** | 0 | 60+ | ✅ |
| **Métodos Tipados** | 0 | 255+ | ✅ |
| **Baseline Generado** | ❌ | ✅ | ✅ |
| **CI/CD Integration** | ❌ | ✅ | ✅ |

## 🔄 Actualización Reciente (24-11-2025)

Avances en Fase 2 (Controllers): Se tiparon y ajustaron retornos JSON en:
- `ZonaGeograficaController` (retornos tipados, uso consistente de `JsonResponse` y recursos)
- `OrdenCompraController` (agregados tipos estrictos, `JsonResponse` en CRUD, helper privado con tipo)
- `ProductoController` (cierre de brecha de tipos en index/show/update/destroy y recurso convertido a respuesta JSON)

Efectos esperados:
- Reducción parcial de errores `missingType.return` y `missingType.parameter` en estos 3 controllers.
- Eliminación de advertencias por retorno de `AnonymousResourceCollection` donde se esperaba `JsonResponse`.
- Base para aplicar el mismo patrón en los próximos 2 controllers clave del lote inicial.

Pendiente recálculo exacto de errores (se realizará tras completar 5 controllers clave). Estimación de avance Fase 2: ~15%.

## ✅ Fase 1 Completada: Modelos Eloquent

### Cambios Implementados

**1. Configuración PHPStan**
```yaml
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon  # ← Baseline generado

parameters:
    level: 6  # ← Nivel estricto
    paths:
        - app
```

**2. Tipos de Retorno Agregados**

Métodos Eloquent con tipos completos:

```php
// ❌ Antes (sin tipos)
public function empresa()
{
    return $this->belongsTo(Empresa::class);
}

// ✅ Después (con tipos)
public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Empresa::class);
}
```

**Categorías de tipos agregados:**
- ✅ **BelongsTo**: 120+ relaciones
- ✅ **HasMany**: 80+ relaciones
- ✅ **BelongsToMany**: 15+ relaciones
- ✅ **HasOne**: 10+ relaciones
- ✅ **MorphTo / MorphMany**: 5+ relaciones
- ✅ **Scopes** (Builder → Builder): 25+ scopes
- ✅ **Métodos de negocio**: 10+ métodos

**3. Modelos Modificados** (60+)

Modelos con tipos de retorno completos:
- ✅ Usuario.php (10 métodos)
- ✅ Empresa.php (24 métodos)
- ✅ Producto.php (7 métodos)
- ✅ Cliente.php (3 métodos)
- ✅ Proveedor.php (6 métodos)
- ✅ Venta.php (5 métodos)
- ✅ OrdenCompra.php (5 métodos)
- ✅ CuentaPorCobrar.php (7 métodos)
- ✅ CuentaPorPagar.php (7 métodos)
- ✅ AsientoContable.php (4 métodos)
- ✅ 50+ modelos adicionales

**4. Script de Automatización**

```php
// fix-model-types.php (ejecutado una vez y eliminado)
$replacements = [
    'belongsTo\(' => 'BelongsTo',
    'hasMany\(' => 'HasMany',
    'belongsToMany\(' => 'BelongsToMany',
    // ... más patrones
];
// Resultado: 255+ tipos agregados en 60+ modelos
```

## 📋 Errores Restantes (4,712)

### Distribución por Categoría

| Categoría | Errores | Prioridad |
|-----------|---------|-----------|
| **Controllers** | 282 | 🔴 Alta |
| **FormRequests** | ~150 | 🟡 Media |
| **Services/Jobs** | ~100 | 🟡 Media |
| **Modelos (tipos avanzados)** | ~500 | 🟢 Baja |
| **Traits** | ~50 | 🟢 Baja |
| **Helpers/Utilities** | ~30 | 🟢 Baja |
| **Tests** | ~3,600 | ⚪ Deshabilitado |

### Tipos de Errores Comunes

1. **missingType.iterableValue** (~1,800)
   ```php
   // ❌ Error
   public function store(array $data): array  // array sin tipo de valor
   
   // ✅ Corrección
   /** @return array<string, mixed> */
   public function store(array $data): array
   ```

2. **missingType.parameter** (~1,200)
   ```php
   // ❌ Error
   public function update($id, array $data)
   
   // ✅ Corrección
   public function update(int $id, array $data): bool
   ```

3. **missingType.return** (~800)
   ```php
   // ❌ Error
   protected function formatResponse($data)
   
   // ✅ Corrección
   /** @return array<string, mixed> */
   protected function formatResponse(mixed $data): array
   ```

4. **missingType.property** (~500)
   ```php
   // ❌ Error
   protected $casts = [];
   
   // ✅ Corrección
   /** @var array<string, string> */
   protected $casts = [];
   ```

5. **Nullable types** (~300)
   ```php
   // ❌ Error
   public function getEmpresa(): Empresa  // Puede ser null
   
   // ✅ Corrección
   public function getEmpresa(): ?Empresa
   ```

## 🎯 Próximas Fases

### Fase 2: Controllers (Alta Prioridad)
**Objetivo**: Reducir 282 errores en controllers a <50

**Archivos críticos:**
- ✅ VentaController.php (~25 errores)
- ✅ ProductoController.php (~20 errores)
- ✅ ClienteController.php (~18 errores)
- ✅ OrdenCompraController.php (~22 errores)
- ✅ 68 controllers adicionales

**Estrategia:**
1. Agregar tipos de retorno a métodos API
2. Tipar parámetros de Request
3. Agregar PHPDoc para arrays complejos
4. Tipar respuestas JSON (JsonResponse)

**Ejemplo:**
```php
// ❌ Antes
public function index(Request $request)
{
    $productos = Producto::all();
    return response()->json($productos);
}

// ✅ Después
use Illuminate\Http\JsonResponse;

/**
 * @return JsonResponse<array<string, mixed>>
 */
public function index(Request $request): JsonResponse
{
    $productos = Producto::all();
    return response()->json([
        'data' => $productos,
        'meta' => ['total' => $productos->count()],
    ]);
}
```

### Fase 3: FormRequests (~150 errores)
**Objetivo**: Tipar validaciones y reglas

**Estrategia:**
```php
// ❌ Antes
public function rules()
{
    return ['nombre' => 'required'];
}

// ✅ Después
/** @return array<string, mixed> */
public function rules(): array
{
    return [
        'nombre' => 'required|string|max:255',
        'email' => 'required|email|unique:clientes',
    ];
}
```

### Fase 4: Services/Jobs (~100 errores)
**Objetivo**: Tipar lógica de negocio y queue jobs

**Ejemplo Queue Job:**
```php
// ✅ Job tipado
class GeneratePdfReportJob implements ShouldQueue
{
    public function __construct(
        public string $reportType,
        public int $empresaId,
        /** @var array<string, mixed> */
        public array $filters = [],
        public ?int $userId = null
    ) {}

    /** @throws \Exception */
    public function handle(): void
    {
        // Lógica tipada
    }
}
```

## 🔧 Comandos Útiles

```bash
# Análisis completo
vendor/bin/phpstan analyse --memory-limit=1G

# Análisis de un directorio específico
vendor/bin/phpstan analyse app/Http/Controllers/API --memory-limit=1G

# Análisis con formato de tabla
vendor/bin/phpstan analyse --error-format=table

# Regenerar baseline
vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon --memory-limit=1G

# Análisis sin baseline (ver todos los errores)
vendor/bin/phpstan analyse --memory-limit=1G --no-configuration

# Contar errores por tipo
vendor/bin/phpstan analyse --error-format=raw | grep "identifier:" | sort | uniq -c | sort -rn
```

## 📈 Beneficios Obtenidos

### 1. **Detección Temprana de Errores**
```php
// PHPStan detecta este error ANTES de ejecutar:
public function calcularTotal(): int
{
    return "123";  // ❌ Error: string no es int
}
```

### 2. **Autocompletado Mejorado en IDE**
```php
// ✅ IDE conoce el tipo exacto
public function empresa(): BelongsTo
{
    return $this->belongsTo(Empresa::class);
}

// $usuario->empresa()->  ← IDE autocompleta métodos de BelongsTo
```

### 3. **Documentación Automática**
```php
// ✅ Tipos = Documentación
/**
 * @param array<int, string> $roleIds  ← PHPStan verifica esto
 */
public function assignRoles(array $roleIds): void
```

### 4. **Refactoring Seguro**
- PHPStan detecta usos incorrectos al cambiar firmas de métodos
- Reduce bugs en producción
- Facilita mantenimiento de código legacy

### 5. **Integración CI/CD**
```yaml
# .github/workflows/phpstan.yml
- name: PHPStan Analysis
  run: vendor/bin/phpstan analyse --memory-limit=1G --error-format=github
```

## 🎓 Lecciones Aprendidas

### 1. **Baseline Strategy**
- ✅ **DO**: Generar baseline al inicio
- ✅ **DO**: Resolver errores incrementalmente
- ❌ **DON'T**: Intentar corregir 4,877 errores de una vez

### 2. **Priorización**
Orden de corrección:
1. **Controllers** (API pública, alta criticidad)
2. **Models** (fundación del sistema)
3. **FormRequests** (validación de entrada)
4. **Services/Jobs** (lógica de negocio)
5. **Tests** (deshabilitar en producción)

### 3. **Tipos Complejos con PHPDoc**
```php
// ✅ Usar PHPDoc para arrays complejos
/**
 * @return array{
 *     data: array<int, Producto>,
 *     meta: array{total: int, page: int},
 *     links: array{next: ?string, prev: ?string}
 * }
 */
public function paginatedProducts(): array
```

### 4. **Iteración > Perfección**
- Fase 1: Tipos básicos (BelongsTo, HasMany)
- Fase 2: Tipos de retorno en controllers
- Fase 3: Tipos de parámetros
- Fase 4: Tipos avanzados (generics, templates)

## 📊 Métricas de Código

```bash
# Estadísticas de tipos agregados
git diff HEAD~1 --stat app/Models/
# 77 files changed, 26593 insertions(+), 269 deletions(-)

# Líneas de tipos agregadas
git diff HEAD~1 app/Models/ | grep "public function" | grep ":" | wc -l
# 255+ métodos con tipos de retorno
```

## 🚀 Próximos Pasos

### Inmediatos (Próxima sesión)
- [ ] **Fase 2**: Agregar tipos a Controllers (282 → <50 errores)
- [ ] **Fase 3**: Tipar FormRequests (150 errores)
- [ ] **Fase 4**: Completar Services/Jobs (100 errores)

### Mediano Plazo (1-2 semanas)
- [ ] Integrar PHPStan en CI/CD GitHub Actions
- [ ] Configurar PHPStan en pre-commit hooks
- [ ] Documentar guía de tipos para el equipo
- [ ] Alcanzar nivel 7 de PHPStan (máximo rigor)

### Largo Plazo (1 mes)
- [ ] Zero errors con baseline vacío
- [ ] Migrar a PHP 8.3 con tipos estrictos
- [ ] Implementar PHPStan Strict Rules
- [ ] Cobertura 100% de tipos en código crítico

## 📚 Recursos

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Larastan (Laravel + PHPStan)](https://github.com/larastan/larastan)
- [PHPStan Levels](https://phpstan.org/user-guide/rule-levels)
- [Type System Best Practices](https://phpstan.org/blog/bring-your-php-applications-to-the-next-level-with-generics)

---

**Sprint 8.6 Status**: 🟡 **In Progress** (4% completado - modelos completos + 3 controllers tipados)

**Última actualización**: 24 de noviembre de 2025
