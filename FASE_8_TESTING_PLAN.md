# FASE 8: Plan de Testing Automatizado

**Fecha de Inicio:** 21 de noviembre de 2025  
**Objetivo:** Implementar testing automatizado completo para garantizar calidad y estabilidad

---

## 🎯 Objetivos de la Fase

1. ✅ Crear tests de Feature para endpoints críticos
2. ✅ Crear tests de Unit para lógica de negocio
3. ✅ Alcanzar cobertura mínima del 70%
4. ✅ Configurar CI/CD básico (opcional)
5. ✅ Documentar prácticas de testing

---

## 📋 Estrategia de Testing

### Tests Feature (API Endpoints)
Probar flujos completos de usuario a través de la API

**Prioridad Alta:**
- ✅ Autenticación (login, logout, registro)
- ⏳ Empresas (CRUD + multi-tenancy)
- ⏳ Productos (CRUD + búsqueda + inventario)
- ⏳ Ventas (CRUD + anular + estadísticas)
- ⏳ Clientes (CRUD)
- ⏳ Inventario (entradas/salidas + kardex)
- ⏳ Cuentas por Cobrar (CRUD + abonar)
- ⏳ Asientos Contables (CRUD + mayorizar)

**Prioridad Media:**
- ⏳ Proveedores
- ⏳ Órdenes de Compra
- ⏳ Almacenes
- ⏳ Sucursales
- ⏳ Empleados
- ⏳ Usuarios y RBAC

**Prioridad Baja:**
- ⏳ Catálogos (Marcas, Unidades, etc.)
- ⏳ Configuraciones
- ⏳ Transporte

### Tests Unit (Lógica de Negocio)
Probar métodos específicos de modelos y servicios

**Prioridad Alta:**
- ✅ Usuario (roles, permisos)
- ⏳ Producto (cálculos de precio, validaciones)
- ⏳ Venta (cálculo de totales, impuestos)
- ⏳ AsientoContable (validación debe=haber)
- ⏳ CuentaContable (estructura jerárquica)
- ⏳ Inventario (cálculo de stock)

**Prioridad Media:**
- ⏳ Cliente (validaciones)
- ⏳ Empresa (multi-tenancy)
- ⏳ CuentaPorCobrar (cálculo de saldos)

---

## 🏗️ Estructura de Tests

```
tests/
├── Feature/
│   ├── Auth/
│   │   ✅ AuthTest.php (existente)
│   ├── Empresas/
│   │   └── EmpresaTest.php (nuevo)
│   ├── Productos/
│   │   ✅ ProductoTest.php (existente)
│   │   └── InventarioTest.php (nuevo)
│   ├── Ventas/
│   │   └── VentaTest.php (nuevo)
│   ├── Clientes/
│   │   └── ClienteTest.php (nuevo)
│   ├── Finanzas/
│   │   ├── CuentaPorCobrarTest.php (nuevo)
│   │   └── PagoTest.php (nuevo)
│   ├── Contabilidad/
│   │   ├── AsientoContableTest.php (nuevo)
│   │   └── CuentaContableTest.php (nuevo)
│   └── RBAC/
│       ✅ PermissionTest.php (existente)
│       └── RolTest.php (nuevo)
│
├── Unit/
│   ├── Models/
│   │   ✅ UsuarioTest.php (existente)
│   │   ✅ RoleTest.php (existente)
│   │   ├── ProductoTest.php (nuevo)
│   │   ├── VentaTest.php (nuevo)
│   │   ├── AsientoContableTest.php (nuevo)
│   │   └── InventarioTest.php (nuevo)
│   └── Services/
│       └── (futuro)
│
└── TestCase.php ✅ (configurado)
```

---

## ✅ Tests Existentes (Revisión)

### Feature Tests
1. **AuthTest.php** ✅
   - Login exitoso
   - Login fallido
   - Logout
   - Permisos

2. **ProductoTest.php** ✅
   - CRUD de productos

3. **PermissionTest.php** ✅
   - Gestión de permisos

### Unit Tests
1. **RoleTest.php** ✅
   - Relaciones de roles

2. **UsuarioTest.php** ✅
   - Validaciones de usuario

---

## 🎯 Tests a Crear (Prioridad Alta)

### 1. EmpresaTest.php (Feature)
```php
- test_puede_listar_empresas()
- test_puede_crear_empresa()
- test_puede_actualizar_empresa()
- test_puede_eliminar_empresa()
- test_multitenancy_solo_ve_su_empresa()
- test_validaciones_cedula_juridica()
```

### 2. VentaTest.php (Feature)
```php
- test_puede_crear_venta()
- test_calcula_correctamente_totales()
- test_calcula_correctamente_impuestos()
- test_puede_anular_venta()
- test_no_puede_anular_venta_antigua()
- test_actualiza_inventario_al_vender()
- test_estadisticas_ventas()
```

### 3. InventarioTest.php (Feature)
```php
- test_puede_registrar_entrada()
- test_puede_registrar_salida()
- test_actualiza_stock_correctamente()
- test_genera_kardex_correcto()
- test_alertas_stock_minimo()
- test_no_permite_salida_sin_stock()
```

### 4. CuentaPorCobrarTest.php (Feature)
```php
- test_puede_crear_cuenta_por_cobrar()
- test_puede_abonar_cuenta()
- test_calcula_saldo_correctamente()
- test_marca_como_pagada()
- test_lista_cuentas_vencidas()
```

### 5. AsientoContableTest.php (Feature)
```php
- test_puede_crear_asiento()
- test_valida_debe_igual_haber()
- test_puede_mayorizar_asiento()
- test_no_puede_modificar_asiento_mayorizado()
- test_genera_numero_asiento_correcto()
```

### 6. ProductoTest.php (Unit)
```php
- test_calcula_precio_con_impuesto()
- test_calcula_precio_con_descuento()
- test_valida_codigo_unico()
- test_relaciones_correctas()
```

### 7. VentaTest.php (Unit)
```php
- test_calcula_subtotal()
- test_calcula_total_impuestos()
- test_calcula_total_final()
- test_valida_estado_transicion()
```

### 8. AsientoContableTest.php (Unit)
```php
- test_valida_balance_debe_haber()
- test_calcula_totales()
- test_estados_permitidos()
```

---

## 🛠️ Configuración de Testing

### phpunit.xml (Actualizado)
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="api_db_testing"/>
```

### Base de Datos de Testing
- Nombre: `api_db_testing`
- Acceso: http://localhost/phpmyadmin/?db=api_db_testing
- Estrategia: RefreshDatabase en cada test

---

## 📊 Métricas de Éxito

### Cobertura de Código
- **Meta Mínima:** 70%
- **Meta Ideal:** 85%
- **Áreas Críticas:** 95%+ (Autenticación, Ventas, Contabilidad)

### Velocidad de Ejecución
- **Tests Feature:** < 30 segundos
- **Tests Unit:** < 5 segundos
- **Total Suite:** < 1 minuto

### Calidad
- ✅ 0 tests fallidos
- ✅ 0 warnings de deprecación
- ✅ Tests independientes (sin orden requerido)
- ✅ Tests aislados (sin efectos secundarios)

---

## 🔄 Proceso de Testing

### Desarrollo Local
```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=VentaTest

# Ejecutar con cobertura
php artisan test --coverage

# Tests por grupo
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Antes de Commit
```bash
# Verificar que pasen todos los tests
php artisan test

# Verificar estilo de código
./vendor/bin/phpcs

# Analizar código estático
./vendor/bin/phpstan analyse
```

---

## 📝 Convenciones de Testing

### Nomenclatura
```php
// Feature Tests
test_puede_[accion]_[entidad]()
test_no_puede_[accion]_sin_permiso()
test_valida_[campo]_requerido()

// Unit Tests
test_[metodo]_[comportamiento_esperado]()
test_[metodo]_lanza_excepcion_cuando_[condicion]()
```

### Estructura AAA (Arrange, Act, Assert)
```php
public function test_ejemplo()
{
    // Arrange - Preparar datos
    $usuario = $this->createUsuario();
    
    // Act - Ejecutar acción
    $response = $this->authenticatedJson('GET', '/api/usuarios', [], $usuario);
    
    // Assert - Verificar resultados
    $response->assertStatus(200);
}
```

---

## 🚀 Implementación por Etapas

### Etapa 1: Tests Críticos (Semana 1)
- [ ] EmpresaTest (Feature)
- [ ] VentaTest (Feature + Unit)
- [ ] ProductoTest (Unit)
- [ ] InventarioTest (Feature)

### Etapa 2: Tests Financieros (Semana 2)
- [ ] CuentaPorCobrarTest (Feature)
- [ ] PagoTest (Feature)
- [ ] AsientoContableTest (Feature + Unit)
- [ ] CuentaContableTest (Feature)

### Etapa 3: Tests Complementarios (Semana 3)
- [ ] ClienteTest (Feature)
- [ ] ProveedorTest (Feature)
- [ ] OrdenCompraTest (Feature)
- [ ] EmpleadoTest (Feature)

### Etapa 4: Optimización (Semana 4)
- [ ] Refactorizar tests duplicados
- [ ] Mejorar cobertura
- [ ] Optimizar velocidad
- [ ] Documentar casos edge

---

## 🎯 Próximos Pasos Inmediatos

1. ✅ Crear VentaTest.php (Feature)
2. ✅ Crear InventarioTest.php (Feature)
3. ✅ Crear EmpresaTest.php (Feature)
4. ✅ Crear CuentaPorCobrarTest.php (Feature)
5. ✅ Ejecutar suite completa
6. ✅ Verificar cobertura inicial

---

**Inicio de Implementación:** Ahora  
**Meta:** Tests críticos funcionando en esta sesión
