# 📋 FASE 4.1: ESTRATEGIA DE REDUCCIÓN PHPSTAN

**Objetivo:** Reducir 1974 errores PHPStan nivel 8 → <30

**Tiempo Estimado:** 8-10 horas

---

## 📊 Categorización Esperada de Errores

| Tipo | % | Cantidad | Acción |
|------|---|----------|--------|
| Return types faltantes | 40% | ~790 | Agregar `: void\|Type` |
| Parameter types faltantes | 30% | ~592 | Agregar tipos a parámetros |
| Undefined properties | 15% | ~296 | Declarar propiedades |
| Type mismatches | 10% | ~197 | Corregir tipos |
| Access on null | 5% | ~99 | Validaciones/null checks |

---

## 🎯 Plan de Acción Iterativa

### Fase 1: Return Types (Controladores) - ~3 horas
**Meta:** -700 errores (de 1974 → 1274)

```
Estrategia:
1. Agregar return types a 95 controllers
   - index() → Collection
   - show() → Model
   - store() → Created
   - update() → Model
   - destroy() → Response
```

**Archivos Prioritarios:**
- app/Http/Controllers/**/*.php (95 archivos)
- Patrón genérico: `public function index(): Collection|Response`

### Fase 2: Parameter Types (Services) - ~2-3 horas
**Meta:** -500 errores (de 1274 → 774)

```
Estrategia:
1. Agregar tipos a 31 services
2. Usar type hints completos
3. Usar collection types: array<Model>, Collection, etc
```

**Archivos Prioritarios:**
- app/Services/**/*.php (31 archivos)
- Patrón: `public function create(CreateDTO $dto): Model`

### Fase 3: Declarar Propiedades Models - ~2 horas
**Meta:** -200 errores (de 774 → 574)

```
Estrategia:
1. Agregar #[Property] o typed properties a Models
2. Usar cast attributes
3. Declarar relations con tipos
```

**Archivos:** app/Models/**/*.php (87 modelos)

### Fase 4: Corregir Type Mismatches - ~1.5 horas
**Meta:** -100 errores (de 574 → 474)

```
Estrategia:
1. Revisar type casting en operaciones
2. Corregir mixed types
3. Agregar null checks
```

### Fase 5: Access on Null - ~1 hora
**Meta:** -50 errores (de 474 → 424)

```
Estrategia:
1. Agregar null checks antes de accesos
2. Usar optional chaining
3. Validaciones explícitas
```

### Fase 6: Revisión Baseline PHPStan - ~0.5 horas
**Meta:** <30 errores finales

```
Estrategia:
1. Consolidar phpstan-baseline.neon
2. Ignorar errores legítimos pero no mejorables
3. Fin de mitigación
```

---

## 🛠️ Herramientas Disponibles

### Opción 1: Refactoring Automático (PHPStan suggest)
```bash
./vendor/bin/phpstan analyze --level=8 app/ --generate-baseline=phpstan-baseline.neon
```

### Opción 2: IDE Refactoring (VS Code + Inlay Hints)
```json
"inlayHints": true,
"php.inlayHints.by": true
```

### Opción 3: Script Personalizado
- Parsear errores
- Aplicar cambios por patrón
- Validar cambios

---

## 📝 Ejecución Paso a Paso

### PASO 1: Controllers - Return Types

**Patrón a aplicar:**

```php
// ANTES
public function index()
{
    return Product::all();
}

// DESPUÉS  
public function index(): Collection
{
    return Product::all();
}
```

**Acciones:**
1. Revisar cada método de controller
2. Identificar return type
3. Agregar type hint
4. Validar con PHPStan

### PASO 2: Services - Parameter & Return Types

```php
// ANTES
public function create($data)
{
    return Product::create($data);
}

// DESPUÉS
public function create(ProductCreateDTO $dto): Product
{
    return Product::create($dto->toModelData());
}
```

### PASO 3: Models - Property Types

```php
// ANTES
class Product extends Model
{
    protected $fillable = ['name', 'price'];
}

// DESPUÉS
class Product extends Model
{
    protected $fillable = ['name', 'price'];
    
    public string $name;
    public float $price;
    
    /** @var Collection */
    public $items;
}
```

---

## 🔍 Verificación

Después de cada fase:
```bash
./vendor/bin/phpstan analyze --level=8 app/ --no-progress -q | wc -l
```

Resultado esperado:
- Fase 1: 1274 errores
- Fase 2: 774 errores
- Fase 3: 574 errores
- Fase 4: 474 errores
- Fase 5: 424 errores
- Fase 6: <30 errores

---

## ⚠️ Notas Importantes

- No modificar código lógico, solo tipos
- Mantener compatibilidad con versión actual
- Preservar funcionalidad existente
- Crear commits pequeños por fase

**Próximo Paso:** Ejecutar Fase 1 - Return types Controllers
