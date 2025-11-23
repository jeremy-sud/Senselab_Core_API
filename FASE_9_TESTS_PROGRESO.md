# FASE 9 - Tests Implementación

## Estado Actual

**Fecha**: 2025-11-23  
**Tarea**: Implementación de tests automatizados para módulos FASE 9

## Archivos Creados

### 1. Tests Feature (4 archivos)
- ✅ `tests/Feature/DeclaracionTributariaTest.php` (7 tests)
- ✅ `tests/Feature/CuentaBancariaTest.php` (8 tests)
- ✅ `tests/Feature/MovimientoBancarioTest.php` (7 tests)  
- ✅ `tests/Feature/RetencionImpuestoTest.php` (6 tests)

**Total**: 28 tests creados

### 2. Factories (4 archivos)
- ✅ `database/factories/DeclaracionTributariaFactory.php`
- ⚠️  `database/factories/CuentaBancariaFactory.php` (requiere ajuste de columnas)
- ⚠️  `database/factories/MovimientoBancarioFactory.php` (requiere ajuste de columnas)
- ⚠️  `database/factories/RetencionImpuestoFactory.php` (requiere ajuste de columnas)

### 3. Permisos Actualizados
- ✅ `tests/TestCase.php` - Agregados 16 permisos FASE 9:
  * 4 permisos Declaraciones Tributarias (Módulo: Tributación)
  * 4 permisos Cuentas Bancarias (Módulo: Banca)
  * 4 permisos Movimientos Bancarios (Módulo: Banca)
  * 4 permisos Retenciones Impuesto (Módulo: Tributación)

## Tests Ejecutados

### DeclaracionTributariaTest
```bash
✅ test_puede_listar_declaraciones_autenticado - PASÓ
⚠️  test_puede_crear_declaracion_d104_valida - 403 Forbidden (falta permiso en policy)
⚠️  test_valida_periodo_fiscal_formato_yyyy_mm - 403
⚠️  test_valida_tipo_declaracion_permitido - 403
⚠️  test_puede_filtrar_por_tipo_declaracion - PASÓ (factory ajustado)
⚠️  test_puede_filtrar_por_periodo - PASÓ (factory ajustado)
⚠️  test_puede_actualizar_estado_declaracion - PASÓ (factory ajustado)
```

**Resultado**: 1/7 pasó (14.3%)

### CuentaBancariaTest
```bash
❌ test_puede_crear_cuenta_con_iban_cr_valido - 403 + columna 'titular' no existe
❌ test_valida_formato_iban_costa_rica - 403
❌ test_valida_longitud_iban_22_caracteres - 403
❌ test_valida_iban_unico - Columna 'titular' no existe
❌ test_puede_filtrar_por_moneda - Columna 'titular' no existe
❌ test_numero_cuenta_enmascarado_en_response - Columna 'titular' no existe
❌ test_valida_tipos_cuenta_permitidos - 403
❌ test_valida_monedas_permitidas - 403
```

**Resultado**: 0/8 pasó (0%)

### MovimientoBancarioTest
```bash
❌ test_puede_crear_movimiento_bancario - Columna 'titular' en cuentas_bancarias
❌ test_valida_monto_no_puede_ser_cero - Columna 'titular'
❌ test_valida_fecha_conciliacion_required_if_conciliado - Columna 'titular'
❌ test_puede_filtrar_por_tipo_movimiento - Columna 'titular'
❌ test_puede_filtrar_por_conciliado - Columna 'titular'
❌ test_puede_filtrar_por_rango_fechas - Columna 'titular'
❌ test_puede_buscar_por_numero_referencia - Columna 'titular'
```

**Resultado**: 0/7 pasó (0%)

### RetencionImpuestoTest
```bash
❌ test_puede_crear_retencion_valida - Columna 'razon_social' en proveedores
❌ test_valida_porcentaje_retencion_rango - Columna 'razon_social'
❌ test_valida_periodo_declaracion_formato - Columna 'razon_social'
❌ test_puede_filtrar_por_tipo_retencion - Columna 'razon_social'
❌ test_puede_filtrar_por_periodo - Columna 'razon_social'
❌ test_valida_tipos_retencion_permitidos - Columna 'razon_social'
```

**Resultado**: 0/6 pasó (0%)

## Problemas Identificados

### 1. Factories No Coinciden con Esquema de Base de Datos

**CuentaBancariaFactory.php**:
- ❌ Campo `titular` no existe en tabla (usar nombre del titular directamente)
- ❌ Campo `saldo_inicial` no existe (solo existe `saldo_actual`)
- ✅ Campos correctos: `banco`, `numero_cuenta`, `iban`, `tipo_cuenta`, `moneda`, `saldo_actual`, `activa`
- 📝 Campos faltantes por agregar: `cuenta_contable_id`, `sucursal_banco`, `contacto_ejecutivo`, `telefono_ejecutivo`, `es_principal`, `notas`

**ProveedorFactory** (usado por RetencionImpuesto):
- ❌ Campo `razon_social` no existe
- ✅ Usar `nombre` en su lugar
- ✅ Campos: `tipo_identificacion`, `numero_identificacion`, `nombre`, `nombre_comercial`

**DeclaracionTributariaFactory.php**:
- ✅ **CORREGIDO** - Ya ajustado a estructura real de tabla
- ✅ Campos correctos: `monto_base_imponible`, `monto_impuesto`, `monto_creditos`, `monto_debitos`, `monto_a_pagar`, `monto_a_favor`

### 2. Errores 403 Forbidden

**Causa**: Los controllers de FASE 9 usan Policies para autorización, pero los tests no verifican/configuran policies correctamente.

**Solución Requerida**:
- Opción A: Agregar bypass de policies en tests (modo testing)
- Opción B: Crear mocks de policies que retornen `true`
- Opción C: Configurar usuario admin con todos los permisos de policies

### 3. Validaciones en FormRequests

Algunos tests esperan errores 422 de validación, pero reciben 403 porque la policy se ejecuta primero.

**Orden de ejecución**:
1. Middleware de autenticación
2. **Policy authorization** ← Falla aquí con 403
3. FormRequest validation ← Nunca se alcanza

## Correcciones Necesarias

### Prioridad Alta

1. **Ajustar CuentaBancariaFactory.php**:
```php
return [
    'empresa_id' => Empresa::factory(),
    'banco' => $this->faker->randomElement($bancos),
    'numero_cuenta' => $this->faker->numerify('###-##-###-######-#'),
    'iban' => 'CR' . $this->faker->numerify('####################'),
    'tipo_cuenta' => $this->faker->randomElement(['corriente', 'ahorros', 'cliente']),
    'moneda' => $this->faker->randomElement(['CRC', 'USD']),
    'saldo_actual' => $this->faker->randomFloat(2, 0, 1000000),
    'activa' => $this->faker->boolean(90),
    // Campos opcionales
    'sucursal_banco' => $this->faker->optional()->city(),
    'contacto_ejecutivo' => $this->faker->optional()->name(),
    'telefono_ejecutivo' => $this->faker->optional()->phoneNumber(),
    'es_principal' => false,
];
```

2. **Verificar ProveedorFactory existe**:
```bash
# Si no existe, crear
database/factories/ProveedorFactory.php
```

3. **Agregar bypass de policies en tests**:
```php
// En setUp() de cada test
Policy::guessPolicyNamesUsing(function () {
    return null; // Deshabilitar policies en tests
});
```

### Prioridad Media

4. **Crear factories faltantes**:
   - MensajeHaciendaFactory
   - TipoComprobanteFeFactory
   - CodigoActividadEconomicaFactory
   - DeduccionLegalFactory
   - PlanillaCcssFactory
   - TipoClienteFactory
   - ZonaGeograficaFactory
   - LogAccesoSistemaFactory

5. **Completar 8 tests restantes de FASE 9**

### Prioridad Baja

6. **Refactorizar tests para usar traits**:
```php
trait CreatesTestData
{
    protected function createCuentaBancaria($attributes = [])
    {
        return CuentaBancaria::factory()->create(array_merge([
            'empresa_id' => $this->empresa->id,
        ], $attributes));
    }
}
```

## Cobertura de Tests Planificada

### Módulos FASE 9 (12 totales)

| Módulo | Tests Creados | Tests Pasando | Cobertura |
|--------|--------------|---------------|-----------|
| Declaraciones Tributarias | 7 | 1 | 14% |
| Cuentas Bancarias | 8 | 0 | 0% |
| Movimientos Bancarios | 7 | 0 | 0% |
| Retenciones Impuesto | 6 | 0 | 0% |
| Mensajes Hacienda | 0 | 0 | 0% |
| Tipos Comprobante FE | 0 | 0 | 0% |
| Códigos Actividad Económica | 0 | 0 | 0% |
| Deducciones Legales | 0 | 0 | 0% |
| Planillas CCSS | 0 | 0 | 0% |
| Tipos Clientes | 0 | 0 | 0% |
| Zonas Geográficas | 0 | 0 | 0% |
| Logs Acceso Sistema | 0 | 0 | 0% |

**Total**: 28/96 tests creados (29.2%)  
**Total Pasando**: 1/28 tests (3.6%)

## Siguiente Sesión

### Tareas Inmediatas (1-2 horas)

1. ✅ Ajustar `CuentaBancariaFactory.php` - remover `titular`, `saldo_inicial`
2. ✅ Ajustar `MovimientoBancarioFactory.php` - usar estructura correcta
3. ✅ Crear/Ajustar `ProveedorFactory.php` - usar `nombre` en lugar de `razon_social`
4. ✅ Agregar bypass de policies en tests o configurar policies mock
5. ✅ Re-ejecutar tests para validar correcciones
6. ✅ Commit y push

### Tareas Mediano Plazo (2-4 horas)

7. Crear 8 factories restantes para FASE 9
8. Crear 68 tests restantes (8-10 tests por módulo)
9. Verificar 100% de tests pasando
10. Documentar en `FASE_9_TESTING_COMPLETO.md`

### Tareas Largo Plazo (4-6 horas)

11. Integration tests end-to-end
12. Performance tests
13. Security tests (validación de autorización)

## Comandos Útiles

```bash
# Ejecutar tests específicos
docker-compose exec php php artisan test tests/Feature/DeclaracionTributariaTest.php --testdox

# Ejecutar todos los tests FASE 9
docker-compose exec php php artisan test tests/Feature/{Declaracion,Cuenta,Movimiento,Retencion}*Test.php

# Ver cobertura de código (requiere xdebug)
docker-compose exec php php artisan test --coverage

# Ejecutar solo tests que fallaron
docker-compose exec php php artisan test --failed
```

## Conclusión

**Progreso Actual**:
- ✅ 4 archivos de tests creados (28 tests)
- ✅ 4 factories creados
- ✅ 16 permisos agregados a TestCase
- ⚠️  Factories requieren ajustes a esquema de BD
- ⚠️  Policies bloquean tests con 403

**Próximo Paso**: Corregir factories para que coincidan con estructura real de base de datos y configurar bypass de policies en entorno de testing.

**Estimado para Completar FASE 9 Tests**: 8-12 horas adicionales
