# Guía de Testing - Ursol CAST API

**Desarrollado por Sistemas Ursol S.A.**  
*Suite de 81 tests automatizados para garantizar calidad del código*  
**Última actualización:** 21 de noviembre de 2025

---

## 📊 Resumen

El proyecto incluye una **suite de 81 tests** que cubren los componentes críticos del sistema:

| Tipo | Archivo | Tests | Estado | Descripción |
|------|---------|-------|--------|-------------|
| Feature | AuthTest.php | 11 | ✅ 100% | Autenticación, login, logout, tokens |
| Feature | EmpresaTest.php | 8 | ✅ 100% | CRUD empresas, multi-tenancy |
| Feature | ProductoTest.php | 12 | ⚠️ 25% | CRUD productos, búsqueda, filtros |
| Feature | VentaTest.php | 7 | ❌ 0% | CRUD ventas, anular, estadísticas |
| Feature | PermissionTest.php | 17 | ✅ 100% | Sistema RBAC completo |
| Unit | RoleTest.php | 10 | ✅ 100% | Modelo Rol y relaciones |
| Unit | UsuarioTest.php | 16 | ✅ 100% | Modelo Usuario y autenticación |
| **TOTAL** | **7 archivos** | **81** | **54%** | **44 pasando / 37 fallando** |

---

## 🚀 Inicio Rápido

### 1. Crear Base de Datos de Testing

```bash
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS api_db_testing;"
```

### 2. Ejecutar Todos los Tests

```bash
cd /home/dawnweaber/Workspace/Ursol-CAST-API
php artisan test
```

**Salida esperada:**
```
PASS  Tests\Feature\AuthTest
✓ usuario puede hacer login
✓ usuario puede hacer logout
✓ usuario puede obtener su informacion
... (8 tests más)

PASS  Tests\Feature\ProductoTest
✓ puede listar productos paginados
✓ puede crear producto valido
... (10 tests más)

PASS  Tests\Feature\PermissionTest
✓ usuario sin permisos no puede acceder a endpoint protegido
... (16 tests más)

PASS  Tests\Unit\RoleTest
✓ rol tiene relacion con permisos
... (9 tests más)

PASS  Tests\Unit\UsuarioTest
✓ usuario tiene relacion con roles
... (15 tests más)

Tests:    66 passed (184 assertions)
Duration: 12.34s
```

---

## 🎯 Ejecutar Tests Específicos

### Por Clase

```bash
# Tests de autenticación (11 tests)
php artisan test --filter AuthTest

# Tests de productos (12 tests)
php artisan test --filter ProductoTest

# Tests de permisos (17 tests)
php artisan test --filter PermissionTest

# Tests de roles (10 tests)
php artisan test --filter RoleTest

# Tests de usuarios (16 tests)
php artisan test --filter UsuarioTest
```

### Por Método Individual

```bash
# Test específico de login
php artisan test --filter test_usuario_puede_hacer_login

# Test de creación de producto
php artisan test --filter test_puede_crear_producto_valido

# Test de permisos
php artisan test --filter test_usuario_sin_permisos_no_puede_acceder
```

### Por Tipo

```bash
# Solo tests Feature (40 tests)
php artisan test tests/Feature

# Solo tests Unit (26 tests)
php artisan test tests/Unit
```

---

## 📁 Estructura de Tests

```
tests/
├── TestCase.php                    # ⚙️ Base con helpers
│   ├── RefreshDatabase trait       # Resetea BD antes de cada test
│   ├── createEmpresa()             # Helper para crear empresa
│   ├── createUsuario()             # Helper para crear usuario
│   ├── createAdminUsuario()        # Helper para crear admin
│   ├── authenticatedJson()         # Helper para requests autenticados
│   ├── seedRoles()                 # Seed de 8 roles
│   ├── seedPermisos()              # Seed de 68 permisos
│   └── assignAllPermissionsToRole()# Asignar permisos a rol
│
├── Feature/                        # 🌐 Tests de integración (40 tests)
│   ├── AuthTest.php               # 11 tests de autenticación
│   ├── ProductoTest.php           # 12 tests CRUD productos
│   └── PermissionTest.php         # 17 tests sistema RBAC
│
└── Unit/                          # 🔬 Tests unitarios (26 tests)
    ├── RoleTest.php               # 10 tests modelo Rol
    └── UsuarioTest.php            # 16 tests modelo Usuario
```

---

## 🧪 Tests Detallados

### AuthTest.php (11 tests)

**Autenticación y tokens:**

1. ✅ `test_usuario_puede_hacer_login` - Login exitoso con credenciales válidas
2. ✅ `test_login_falla_con_credenciales_invalidas` - Credenciales incorrectas retorna 422
3. ✅ `test_usuario_inactivo_no_puede_hacer_login` - Usuario inactivo no puede autenticarse
4. ✅ `test_usuario_puede_hacer_logout` - Logout revoca token actual
5. ✅ `test_usuario_puede_obtener_su_informacion` - GET /api/user retorna datos del usuario
6. ✅ `test_login_revoca_tokens_anteriores` - Login automáticamente revoca tokens previos
7. ✅ `test_usuario_puede_tener_multiples_tokens` - Múltiples tokens de diferentes dispositivos
8. ✅ `test_token_invalido_retorna_401` - Token inválido retorna Unauthenticated
9. ✅ `test_login_retorna_permisos_del_usuario` - Login incluye lista de permisos
10. ✅ `test_usuario_eliminado_no_puede_hacer_login` - Usuario eliminado no puede autenticarse
11. ✅ `test_endpoint_protegido_requiere_autenticacion` - Endpoints protegidos requieren Bearer token

### ProductoTest.php (12 tests)

**CRUD y operaciones de productos:**

1. ✅ `test_puede_listar_productos_paginados` - Listado con paginación
2. ✅ `test_puede_crear_producto_valido` - Creación con datos válidos
3. ✅ `test_puede_obtener_producto_por_id` - GET /api/productos/{id}
4. ✅ `test_puede_actualizar_producto` - PUT /api/productos/{id}
5. ✅ `test_puede_eliminar_producto` - DELETE (soft delete)
6. ✅ `test_no_puede_crear_producto_sin_datos_requeridos` - Validación de campos
7. ✅ `test_puede_buscar_productos_por_nombre` - Búsqueda por texto
8. ✅ `test_puede_filtrar_productos_por_empresa` - Multi-tenancy
9. ✅ `test_puede_filtrar_productos_activos` - Filtro activo=true
10. ✅ `test_puede_filtrar_productos_por_categoria` - Filtro por categoría
11. ✅ `test_producto_eliminado_no_aparece_en_listado` - Soft delete funciona
12. ✅ `test_paginacion_funciona_correctamente` - Paginación con per_page

### PermissionTest.php (17 tests)

**Sistema RBAC (Role-Based Access Control):**

1. ✅ `test_usuario_sin_permisos_no_puede_acceder_a_endpoint_protegido` - Middleware bloquea sin permiso
2. ✅ `test_usuario_con_permiso_puede_acceder_a_endpoint_protegido` - Middleware permite con permiso
3. ✅ `test_usuario_puede_tener_multiples_roles` - Usuario con varios roles
4. ✅ `test_usuario_hereda_permisos_de_sus_roles` - Permisos heredados de roles
5. ✅ `test_rol_puede_tener_multiples_permisos` - Rol con varios permisos
6. ✅ `test_puede_verificar_si_usuario_tiene_permiso_especifico` - `hasPermission()`
7. ✅ `test_puede_verificar_si_usuario_tiene_rol_especifico` - `hasRole()`
8. ✅ `test_puede_verificar_si_usuario_tiene_alguno_de_varios_roles` - `hasAnyRole()`
9. ✅ `test_puede_obtener_todos_los_permisos_del_usuario` - `getAllPermissions()`
10. ✅ `test_puede_listar_permisos` - GET /api/permisos
11. ✅ `test_puede_listar_roles` - GET /api/roles
12. ✅ `test_puede_asignar_permiso_a_rol` - POST relación rol-permiso
13. ✅ `test_puede_remover_permiso_de_rol` - DELETE relación
14. ✅ `test_middleware_permission_funciona_correctamente` - Middleware CheckPermission
15. ✅ `test_usuario_sin_rol_no_tiene_permisos` - Usuario sin roles = sin permisos
16. ✅ `test_usuario_con_rol_administrador_tiene_todos_los_permisos` - Admin tiene acceso total
17. ✅ `test_permisos_se_filtran_por_modulo` - Filtrado por módulo funciona

### RoleTest.php (10 tests)

**Tests unitarios del modelo Rol:**

1. ✅ `test_rol_tiene_relacion_con_permisos` - Relación BelongsToMany
2. ✅ `test_rol_tiene_relacion_con_usuarios` - Relación BelongsToMany
3. ✅ `test_rol_puede_verificar_si_tiene_permiso` - `hasPermission()` funciona
4. ✅ `test_scope_activos_filtra_roles_activos` - Scope `activos()` filtra
5. ✅ `test_nombre_se_normaliza_automaticamente` - Nombres se capitalizan
6. ✅ `test_slug_se_genera_automaticamente` - Slug desde nombre
7. ✅ `test_puede_sincronizar_permisos` - `syncPermissions()` funciona
8. ✅ `test_rol_puede_tener_permisos_multiples` - Múltiples permisos
9. ✅ `test_rol_activo_false_no_aparece_en_scope` - Inactivos no filtran
10. ✅ `test_puede_obtener_permisos_por_modulo` - Filtrado de permisos

### UsuarioTest.php (16 tests)

**Tests unitarios del modelo Usuario:**

1. ✅ `test_usuario_tiene_relacion_con_roles` - Relación BelongsToMany
2. ✅ `test_usuario_tiene_relacion_con_empresa` - Relación BelongsTo
3. ✅ `test_usuario_tiene_relacion_con_cargo` - Relación BelongsTo
4. ✅ `test_usuario_puede_verificar_si_tiene_rol` - `hasRole()` funciona
5. ✅ `test_usuario_puede_verificar_si_tiene_permiso` - `hasPermission()` funciona
6. ✅ `test_usuario_puede_verificar_si_tiene_alguno_de_varios_roles` - `hasAnyRole()`
7. ✅ `test_usuario_puede_obtener_todos_sus_permisos` - `getAllPermissions()`
8. ✅ `test_usuario_puede_crear_token_sanctum` - `createToken()` funciona
9. ✅ `test_usuario_password_se_hashea_automaticamente` - Password hash automático
10. ✅ `test_email_debe_ser_unico` - Email único en BD
11. ✅ `test_scope_activos_filtra_usuarios_activos` - Scope `activos()` filtra
12. ✅ `test_usuario_puede_tener_multiples_tokens` - Múltiples tokens Sanctum
13. ✅ `test_usuario_con_multiples_roles_tiene_todos_los_permisos` - Herencia correcta
14. ✅ `test_password_no_visible_en_json` - Password oculto en JSON
15. ✅ `test_usuario_puede_ser_autenticado` - Auth::attempt() funciona
16. ✅ `test_usuario_eliminado_no_puede_autenticarse` - Eliminado no login

---

## ⚙️ Configuración

### phpunit.xml

```xml
<env name="DB_DATABASE" value="api_db_testing"/>
<env name="APP_ENV" value="testing"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_DRIVER" value="sync"/>
```

### TestCase.php Helpers

El archivo `tests/TestCase.php` incluye helpers reutilizables para simplificar la escritura de tests:

**Helpers de Empresas:**

```php
// Crear empresa con régimen tributario válido
$empresa = $this->createEmpresa([
    'nombre' => 'Mi Empresa',
    'cedula_juridica' => '3-101-123456'
]);
```

**Helpers de Usuarios:**

```php
// Crear usuario normal
$usuario = $this->createUsuario([
    'nombre' => 'Juan Pérez',
    'email' => 'juan@example.com'
]);

// Crear usuario admin con todos los permisos
$admin = $this->createAdminUsuario();

// Usuario con roles específicos
$vendedor = $this->createUsuario([], ['Vendedor']);
```

**Helpers de Productos:**

```php
// Crear producto con todos los campos requeridos
$producto = $this->createProducto([
    'nombre' => 'Laptop HP',
    'precio' => 500000
], $empresa);

// Obtener o crear categoría de producto
$categoria = $this->getCategoriaProducto($empresa);

// Obtener o crear unidad de medida
$unidad = $this->getUnidadMedida();
```

**Helpers de Autenticación:**

```php
// Hacer request JSON autenticado
$response = $this->authenticatedJson('GET', '/api/productos', [], $usuario);

// Con datos en el body
$response = $this->authenticatedJson('POST', '/api/productos', [
    'nombre' => 'Nuevo Producto',
    'precio' => 100
], $usuario);
```

**Helpers de Seeders:**

```php
// Cargar 7 roles básicos
$this->seedRoles();

// Cargar 68 permisos
$this->seedPermisos();

// Asignar todos los permisos a un rol
$this->assignAllPermissionsToRole($rolAdmin);
```

### Ejemplo Completo de Test

```php
public function test_puede_crear_producto_con_helpers()
{
    // Arrange - Preparar datos
    $admin = $this->createAdminUsuario();
    $categoria = $this->getCategoriaProducto();
    $unidad = $this->getUnidadMedida();
    
    // Act - Ejecutar acción
    $response = $this->authenticatedJson('POST', '/api/productos', [
        'nombre' => 'Laptop HP',
        'codigo' => 'LAPTOP-001',
        'precio' => 500000,
        'categoria_id' => $categoria->id,
        'unidad_medida_id' => $unidad->id,
        'tipo' => 'producto'
    ], $admin);
    
    // Assert - Verificar resultados
    $response->assertStatus(201);
    $this->assertDatabaseHas('productos', [
        'nombre' => 'Laptop HP',
        'codigo' => 'LAPTOP-001'
    ]);
}
```

---

## 🐛 Troubleshooting

### Error: Base de datos no existe

```bash
# Crear base de datos de testing
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS api_db_testing;"
```

### Error: Foreign key constraint fails

```bash
# Limpiar caché de configuración
php artisan config:clear

# Ejecutar tests desde cero
php artisan test
```

### Tests lentos

```bash
# Usar SQLite en memoria (más rápido pero menos realista)
# Editar phpunit.xml:
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Ver queries ejecutados

```php
// En el test, activar query log
DB::enableQueryLog();

// Tu código de test...

// Ver queries
dd(DB::getQueryLog());
```

---

## 📈 Comandos Útiles

```bash
# Ejecutar con cobertura
php artisan test --coverage

# Ejecutar con detalles
php artisan test --verbose

# Ejecutar solo tests que fallaron
php artisan test --failed

# Ejecutar tests en paralelo (más rápido)
php artisan test --parallel

# Ejecutar con profiling
php artisan test --profile
```

---

## 📚 Recursos Adicionales

- **Documentación completa:** [FASE_4_TESTING_COMPLETADA.md](FASE_4_TESTING_COMPLETADA.md)
- **Laravel Testing:** https://laravel.com/docs/11.x/testing
- **PHPUnit:** https://phpunit.de/documentation.html
- **Factories:** https://laravel.com/docs/11.x/eloquent-factories

---

## 🎯 Mejores Prácticas

1. **Ejecutar tests antes de commit:**
   ```bash
   php artisan test && git commit -m "mensaje"
   ```

2. **Crear test antes de fix de bug:**
   - Escribir test que reproduzca el bug
   - Verificar que falla
   - Arreglar código
   - Verificar que test pasa

3. **Mantener tests independientes:**
   - Usar `RefreshDatabase` trait
   - No depender del orden de ejecución
   - Crear datos necesarios en cada test

4. **Tests descriptivos:**
   ```php
   // ✅ Bueno
   test_usuario_con_permiso_productos_leer_puede_listar_productos()
   
   // ❌ Malo
   test_productos()
   ```

---

**Desarrollado por Sistemas Ursol S.A.**  
*30 años de experiencia en soluciones tecnológicas*
