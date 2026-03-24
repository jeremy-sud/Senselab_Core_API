# Guía de Testing - Ursol CAST API

**Desarrollado por Sistemas Ursol S.A.**  
*Suite de 997 tests automatizados con 4 capas de verificación*  
**Última actualización:** 24 de marzo de 2026 — v3.3.0

---

## 📊 Resumen

**Estado Actual:** ✅ **997/997 tests passing (100%)** — 0 failures, 5 skipped (requieren certificado Hacienda)

| Capa de Testing | Archivos | Tests | Herramienta |
|-----------------|----------|-------|-------------|
| **Feature (integración)** | 75 | ~600 | PHPUnit 11.5 |
| **Unit (unitarios)** | 58 | ~350 | PHPUnit 11.5 |
| **Contract (contratos)** | 9 | ~47 | Pact PHP |
| **Load (carga)** | 6 | N/A | k6 |
| **Mutation (mutación)** | — | — | Infection PHP |
| **Total** | **148 archivos** | **997 tests** | — |

| Métrica | Valor |
|---------|-------|
| PHPStan | Level 8, **0 errores** |
| Assertions | ~3,500+ |
| Ratio tests/controller | ~10.8 |
| Tiempo ejecución (SQLite) | < 30s |

---

## 🚀 Inicio Rápido

### Ejecutar Todos los Tests

```bash
php artisan test
```

**Salida esperada:**
```
Tests:    997 passed (5 skipped)
Duration: ~25s
```

### Por Suite

```bash
# Feature tests (integración)
php artisan test --testsuite=Feature

# Unit tests (unitarios)
php artisan test --testsuite=Unit

# Contract tests (Pact)
php artisan test --testsuite=Contract-Consumer
php artisan test --testsuite=Contract-Provider
```

### Por Dominio

```bash
# Autenticación y autorización
php artisan test --filter "AuthTest|PermissionTest|RolTest"

# Facturación electrónica / Hacienda
php artisan test --filter "Hacienda|FacturacionElectronica|ComprobanteElectronico"

# Módulos financieros
php artisan test --filter "VentaTest|CuentaContable|CuentaPorCobrar|CuentaPorPagar"

# Inventario y almacén
php artisan test --filter "InventarioTest|AlmacenTest|ProductoTest"

# Nómina y RRHH
php artisan test --filter "NominaTest|EmpleadoTest|PeriodoNomina|PagoNomina"

# Multi-tenancy y seguridad
php artisan test --filter "MultiTenant|Encryption|RateLimit|SecurityHeader|Cors"

# Servicios (unit)
php artisan test tests/Unit/Services/
```

---

## 📁 Estructura de Tests

```
tests/
├── TestCase.php                          # Base con helpers (auth, empresa, seeders)
│
├── Feature/                              # 🌐 75 archivos — integración y E2E
│   ├── AuthTest.php                      # Autenticación Sanctum
│   ├── ProductoTest.php                  # CRUD productos
│   ├── VentaTest.php                     # Ventas con Service Layer
│   ├── ClienteTest.php                   # Clientes
│   ├── EmpresaTest.php                   # Multi-tenant empresas
│   ├── InventarioTest.php                # Inventario
│   ├── AlmacenTest.php                   # Almacenes
│   ├── CuentaContableTest.php            # Contabilidad
│   ├── NominaTest.php                    # Nómina
│   ├── MultiTenantIsolationTest.php      # Aislamiento cross-tenant
│   ├── RateLimitingGranularTest.php      # Rate limiting por contexto
│   ├── EncryptionGranularTest.php        # Encriptación AES-256
│   ├── StructuredLoggingTest.php         # Logging + trace_id
│   ├── SentryErrorTrackingTest.php       # Sentry integration
│   ├── FacturacionElectronicaE2ETest.php # E2E facturación
│   ├── FacturacionElectronicaE2ECasosEdgeTest.php
│   ├── Hacienda/                         # Tests Hacienda module
│   │   └── *.php
│   └── ... (60+ archivos más)
│
├── Unit/                                 # 🔬 58 archivos — lógica de negocio
│   ├── Services/                         # 36 archivos — unit tests de servicios
│   │   ├── AlmacenServiceTest.php
│   │   ├── VentaServiceTest.php
│   │   ├── UsuarioServiceTest.php
│   │   ├── XmlComprobanteBuilderTest.php
│   │   ├── Hacienda/                     # 3 archivos Hacienda
│   │   │   ├── HaciendaApiClientTest.php
│   │   │   ├── OAuthTokenManagerTest.php
│   │   │   └── RateLimiterTest.php
│   │   └── ... (30+ archivos más)
│   ├── Models/ModelRelationsTest.php     # Relaciones entre modelos
│   ├── Exceptions/                       # 2 archivos — domain exceptions
│   ├── Helpers/                          # 2 archivos — utilidades
│   ├── Jobs/                             # 5 archivos — queue jobs
│   ├── Traits/ApiResponseTest.php        # Trait ApiResponse
│   ├── Validation/                       # 4 archivos — reglas custom
│   ├── RoleTest.php
│   ├── UsuarioTest.php
│   ├── HasActiveScopeTest.php
│   ├── HasAuditFieldsTest.php
│   └── HasCustomSoftDeletesTest.php
│
├── Contract/                             # 📋 9 archivos — Pact contract testing
│   ├── PactTestCase.php                  # Base class
│   ├── Consumer/                         # 6 consumers
│   │   ├── AuthApiTest.php
│   │   ├── ClienteApiTest.php
│   │   ├── ComprobanteFeApiTest.php
│   │   ├── InventarioApiTest.php
│   │   ├── ProductoApiTest.php
│   │   └── VentaApiTest.php
│   ├── Provider/
│   │   └── UrsolCastApiVerificationTest.php
│   └── pacts/                            # Contratos generados
│
└── Load/                                 # ⚡ 6 archivos — k6 load testing
    ├── smoke-test.js                     # 1 VU, 30s
    ├── load-ventas-facturacion.js        # 10 VUs, 2min
    ├── load-n1-detection.js              # Detección N+1
    ├── helpers.js
    ├── k6-config.js
    └── README.md
```

---

## 🧪 Capas de Testing

### 1. PHPUnit — Feature + Unit (997 tests)

Tests de integración (Feature) y unitarios (Unit) con PHPUnit 11.5.

**Cobertura por dominio:**

| Dominio | Feature | Unit | Total |
|---------|---------|------|-------|
| Auth y RBAC | 4+ archivos | 2+ archivos | 25+ tests |
| Core CRUD (empresa, usuario, sucursal) | 8 archivos | — | 60+ tests |
| Multi-tenancy (aislamiento) | 1 archivo dedicado | — | 7+ tests |
| Facturación electrónica | E2E + edge cases | 3 Hacienda | 55+ tests |
| Seguridad (CORS, headers, rate limit, encryption) | 6 archivos | — | 40+ tests |
| Inventario/Almacén | 6 archivos | 2+ servicios | 50+ tests |
| Contabilidad/Finanzas | 15 archivos | 5+ servicios | 80+ tests |
| Nómina/RRHH | 8 archivos | 3+ servicios | 50+ tests |
| Jobs y queue | — | 5 archivos | 25+ tests |
| Traits | — | 3 archivos | 36+ tests |
| Validation | — | 4 archivos | 20+ tests |
| Exceptions | — | 2 archivos | 15+ tests |

### 2. Pact — Contract Testing (6 consumers)

Garantiza compatibilidad entre API y consumidores frontend/mobile.

```bash
# Generar contratos (consumer-side)
php artisan test --testsuite=Contract-Consumer

# Verificar contratos (provider-side)
php artisan test --testsuite=Contract-Provider
```

**Contratos cubiertos:** Auth, Cliente, ComprobanteFe, Inventario, Producto, Venta

### 3. Infection — Mutation Testing

Verifica la calidad de los tests detectando mutantes no capturados.

```bash
vendor/bin/infection --threads=4
```

**Configuración:** `infection.json5`
- **Directorios:** `app/Services/`, `app/Rules/`, `app/Exceptions/`
- **Excluidos:** `app/Services/AI/`
- **Quality gates:** MSI ≥ 50%, Covered MSI ≥ 70%
- **Mutadores:** arithmetic, boolean, conditional, return value, regex, removal, loop, sort, unwrap, cast, function signature

### 4. k6 — Load Testing

```bash
# Smoke test (1 usuario virtual, 30s)
k6 run tests/Load/smoke-test.js

# Carga en ventas y facturación (10 VUs, 2min)
k6 run tests/Load/load-ventas-facturacion.js

# Detección de N+1 queries (10 VUs, 2min)
k6 run tests/Load/load-n1-detection.js
```

---

## ⚙️ Configuración

### phpunit.xml

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Contract-Consumer">
        <directory>tests/Contract/Consumer</directory>
    </testsuite>
    <testsuite name="Contract-Provider">
        <directory>tests/Contract/Provider</directory>
    </testsuite>
</testsuites>

<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="testing"/>  <!-- SQLite in-memory -->
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="LOG_CHANNEL" value="null"/>
    <env name="MAIL_MAILER" value="array"/>
</php>
```

### TestCase.php — Helpers disponibles

```php
// Empresa y sucursal
$empresa = $this->createEmpresa(['nombre' => 'Mi Empresa']);
$sucursal = $this->createSucursal($empresa);

// Usuarios con roles
$usuario = $this->createUsuario([], ['Vendedor']);
$admin = $this->createAdminUsuario();

// Request autenticado (genera Bearer token Sanctum)
$response = $this->authenticatedJson('GET', '/api/productos', [], $usuario);

// Seeders RBAC
$this->seedRoles();       // 4 roles: Administrador, Gerente, Vendedor, Bodeguero
$this->seedPermisos();    // 68 permisos por módulo

// Catálogos
$formaPago = $this->getFormaPago();  // Efectivo
```

### Ejemplo de Feature Test (patrón actual)

```php
public function test_puede_crear_producto_con_service_layer(): void
{
    $admin = $this->createAdminUsuario();
    
    $response = $this->authenticatedJson('POST', '/api/productos', [
        'nombre' => 'Laptop HP',
        'codigo' => 'LAPTOP-001',
        'precio' => 500000,
        'categoria_id' => $this->getCategoriaProducto()->id,
        'unidad_medida_id' => $this->getUnidadMedida()->id,
    ], $admin);
    
    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'message', 'data']);
    
    $this->assertDatabaseHas('productos', [
        'nombre' => 'Laptop HP',
        'codigo' => 'LAPTOP-001',
    ]);
}
```

---

## 🐛 Troubleshooting

### Tests lentos

```bash
# SQLite in-memory es el default (rápido)
# Si necesitas MySQL real:
php artisan test --env=testing-mysql

# Ejecutar en paralelo
php artisan test --parallel
```

### Error: Foreign key constraint fails

```bash
php artisan config:clear
php artisan test
```

### Ver queries ejecutados

```php
DB::enableQueryLog();
// ... código del test ...
dd(DB::getQueryLog());
```

### Tests skipped (5)

Los 5 tests skipped en `FacturacionElectronicaE2ETest` requieren certificado real de Hacienda Costa Rica. Se resolverán con FASE 19.6 (E2E sandbox).

---

## 📈 Comandos Útiles

```bash
# Ejecutar todo
php artisan test

# Con cobertura
php artisan test --coverage

# Solo failures
php artisan test --failed

# Filtrar por nombre
php artisan test --filter test_usuario_puede_hacer_login

# Con profiling (ver tests lentos)
php artisan test --profile

# PHPStan (análisis estático)
vendor/bin/phpstan analyse app/ --level=8

# Mutation testing
vendor/bin/infection --threads=4 --show-mutations

# Load testing
k6 run tests/Load/smoke-test.js
```

---

## 📅 Historial

| Fecha | Tests | Estado | Hito |
|-------|-------|--------|------|
| Nov 2025 | 218 (186 passing) | ⚠️ 85% | Sprint 5 — suite inicial |
| Ene 2026 | 405 (100%) | ✅ | Post-Phase 10 |
| Feb 2026 | 762 (100%) | ✅ | FASE 4 — PHPStan resuelto |
| Mar 9, 2026 | 959 (100%) | ✅ | FASE 14 — Contract + Mutation |
| Mar 24, 2026 | **997 (100%)** | ✅ | **FASE 16 — Service Layer** |

