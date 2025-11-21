# Ursol CAST API

<p align="center">
  <img src="./public/assets/logos/ursol-cast-api-logo.png" width="600" alt="Ursol CAST API Logo">
</p>

<p align="center">
  <img src="./public/assets/logos/ursol-icon.webp" width="80" alt="Sistemas Ursol Icon">
</p>

<p align="center">
  <strong>Desarrollado por Sistemas Ursol S.A.</strong><br>
  <em>Soluciones Tecnológicas con más de 30 años de experiencia</em>
</p>

<p align="center">
  <a href="https://github.com/SistemasUrsol/Ursol-CAST-API"><img src="https://img.shields.io/badge/GitHub-SistemasUrsol-blue" alt="GitHub"></a>
  <a href="https://ursol.com"><img src="https://img.shields.io/badge/Web-ursol.com-green" alt="Web"></a>
  <a href="https://ursol.net"><img src="https://img.shields.io/badge/Web-ursol.net-green" alt="Web Alt"></a>
  <a href="https://wa.me/50688687765"><img src="https://img.shields.io/badge/WhatsApp-Soporte-25D366" alt="WhatsApp"></a>
</p>

## 📋 Tabla de Contenidos

- [Acerca del Proyecto](#-acerca-del-proyecto)
- [Características Principales](#-características-principales)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación](#-instalación)
- [Configuración](#️-configuración)
- [Arquitectura](#-arquitectura)
- [Módulos del Sistema](#-módulos-del-sistema)
- [API Reference](#-api-reference)
- [Testing](#-testing)
- [Documentación Swagger](#-documentación-swagger)
- [Despliegue](#-despliegue)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

## 🚀 Acerca del Proyecto

**Ursol CAST API** es un sistema ERP completo desarrollado por **Sistemas Ursol S.A.** con Laravel 11, diseñado específicamente para empresas costarricenses que requieren soluciones tecnológicas robustas y escalables.

### 📊 Estado del Proyecto

**✅ FASE 1 - Correcciones Críticas (COMPLETADA)**
- Corrección de campos en `AsientoContableController` (debe/haber)
- Sincronización de campo `comentario` en `TipoImpuesto` con base de datos
- Actualización de migraciones y FormRequests
- Commit: `0dd7c39`

**✅ FASE 2 - Datos Maestros (COMPLETADA)**
- Implementación de 6 seeders principales:
  - RegimenesTributariosSeeder (2 regímenes)
  - FormasPagoSeeder (6 formas de pago)
  - TiposCuentasSeeder (8 tipos de cuentas)
  - UnidadesMedidaSeeder (11 unidades)
  - PermisosSeeder (68 permisos = 17 módulos × 4 acciones)
  - RolesSeeder (7 roles: Administrador, Gerente, Contador, Vendedor, Comprador, Bodeguero, Usuario)
- Total: **96 registros** de datos maestros cargados
- Commit: `58e2055`

**✅ FASE 3 - Autenticación y Autorización (COMPLETADA)**
- Sistema RBAC (Role-Based Access Control) implementado
- Laravel Sanctum para autenticación por tokens
- CheckPermission middleware para protección de rutas
- Usuario model mejorado: Authenticatable + HasApiTokens + 5 métodos RBAC
- AuthController con endpoints: login, logout, me
- 3 seeders adicionales:
  - CargosSeeder (7 cargos)
  - EmpresaDemoSeeder (1 empresa + 1 sucursal)
  - UsuarioAdminSeeder (1 usuario admin con 68 permisos)
- Total adicional: **16 registros** (112 registros totales en BD)
- Sistema de autenticación **100% funcional y probado**
- Commit: `e668c64`

**✅ FASE 4 - Testing (COMPLETADA)**
- Suite completa de **66 tests** implementados
- Tests de autenticación y autorización (11 tests)
- Tests CRUD de productos (12 tests)
- Tests de sistema RBAC y permisos (17 tests)
- Tests unitarios de modelos Rol y Usuario (26 tests)
- Base de datos de testing configurada (MySQL)
- Helpers de testing en TestCase (RefreshDatabase, factories, seeds)
- Ver detalles: [FASE_4_TESTING_COMPLETADA.md](FASE_4_TESTING_COMPLETADA.md)

**✅ FASE 5 - Documentación API (COMPLETADA)**
- Swagger/OpenAPI instalado y configurado (L5-Swagger 9.0.1)
- Documentación interactiva en: `http://localhost:8000/api/documentation`
- AuthController documentado (3 endpoints: login, logout, user)
- ProductoController documentado (5 endpoints CRUD completos)
- 10 schemas OpenAPI creados (Usuario, Rol, Permiso, Empresa, Producto, etc.)
- Autenticación Bearer configurada en Swagger UI

### 🔑 Credenciales de Prueba

Después de ejecutar los seeders, puedes iniciar sesión con:

```
Email: admin@ursol.com
Password: admin123
```

Este usuario tiene acceso completo con **68 permisos** (todos los módulos del sistema).

### 🏢 Sobre Sistemas Ursol S.A.

Con **casi 30 años de experiencia** en el mercado costarricense, Sistemas Ursol S.A. es una empresa familiar que se distingue por su **ética inquebrantable**, **atención personalizada** y el **"Toque Humano"** en cada proyecto. Fundada y liderada por **Eduardo Alberto Ureña Solano**, quien aporta más de 35 años de experiencia en el sector tecnológico.

**Nuestros Servicios:**
- ✨ Soluciones Tecnológicas Empresariales
- 💻 Desarrollo de Software (ERP, CRM, Sistemas a Medida)
- 🎨 Diseño Web Profesional
- 🖼️ Diseño Gráfico Corporativo

### 🎯 Capacidades del Sistema

Este ERP proporciona gestión integral de:

- **Autenticación y Autorización** (Laravel Sanctum + RBAC con 68 permisos granulares)
- **Facturación Electrónica** (integración completa con DGT/Hacienda de Costa Rica)
- **Inventario Multi-Almacén** (control en tiempo real)
- **Contabilidad** (plan de cuentas, asientos, reportes financieros)
- **Recursos Humanos y Nómina** (gestión completa de empleados)
- **Gestión de Transporte** (módulo especializado para empresas de buses)
- **Multi-Tenancy** (soporte para múltiples empresas en una sola instalación)

El sistema está diseñado con las mejores prácticas de desarrollo, siguiendo los estándares de Laravel y con enfoque en escalabilidad, seguridad y facilidad de mantenimiento.

## ✨ Características Principales

### Autenticación y Autorización
- ✅ Laravel Sanctum para autenticación por tokens API
- ✅ Sistema RBAC (Role-Based Access Control) completo
- ✅ 68 permisos granulares (17 módulos × 4 acciones: crear, leer, actualizar, eliminar)
- ✅ 7 roles predefinidos con permisos configurables
- ✅ Middleware `CheckPermission` para protección de rutas
- ✅ Métodos helper en modelo Usuario: `hasPermission()`, `hasRole()`, `hasAnyRole()`, etc.

### Facturación Electrónica
- ✅ Emisión de facturas electrónicas según normativa DGT
- ✅ Recepción y procesamiento de comprobantes electrónicos
- ✅ Gestión de consecutivos de facturación
- ✅ Integración con códigos CAByS
- ✅ Soporte para múltiples tipos de documento (Factura, Tiquete, Nota de Crédito/Débito)

### Inventario
- ✅ Control multi-almacén
- ✅ Seguimiento de stock en tiempo real
- ✅ Gestión de entradas y salidas de inventario
- ✅ Transferencias entre almacenes
- ✅ Kardex detallado por producto
- ✅ Categorización y clasificación de productos

### Contabilidad
- ✅ Plan de cuentas configurable
- ✅ Asientos contables automáticos y manuales
- ✅ Cuentas por cobrar y por pagar
- ✅ Caja chica
- ✅ Reportes financieros

### Recursos Humanos
- ✅ Gestión de empleados
- ✅ Cálculo de nómina
- ✅ Deducciones y bonificaciones
- ✅ Periodos de pago configurables

### Transporte
- ✅ Gestión de rutas y horarios
- ✅ Control de flota de buses
- ✅ Asignación de unidades
- ✅ Ventas de boletos

### Multi-Tenancy
- ✅ Soporte para múltiples empresas
- ✅ Aislamiento completo de datos
- ✅ Configuraciones independientes por tenant

## 💻 Requisitos del Sistema

- **PHP**: >= 8.2
- **Composer**: >= 2.5
- **Node.js**: >= 18.x
- **NPM**: >= 9.x
- **MySQL**: >= 8.0 o **MariaDB**: >= 10.6
- **Redis** (opcional, recomendado para producción)
- **Extensiones PHP requeridas**:
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
  - Fileinfo
  - GD o Imagick (para procesamiento de imágenes)

## 🔧 Instalación

### 1. Clonar el Repositorio

```bash
# Desde GitHub Organization oficial
git clone https://github.com/SistemasUrsol/Ursol-CAST-API.git
cd Ursol-CAST-API

# O desde Ursol Reposit for Developers
# Consulta https://sites.google.com/view/repdevursol/home/repositorio para acceso
```

### 2. Instalar Dependencias

```bash
# Dependencias de PHP
composer install

# Dependencias de Node.js
npm install
```

### 3. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar key de aplicación
php artisan key:generate
```

### 4. Configurar Base de Datos

Edita el archivo `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=####
DB_USERNAME=####
DB_PASSWORD=####
```

### 5. Ejecutar Migraciones y Seeders

```bash
# Ejecutar migraciones
php artisan migrate

# Publicar migraciones de Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Ejecutar seeders (carga 112 registros: 96 datos maestros + 16 datos demo)
php artisan db:seed

# O todo junto (fresh install)
php artisan migrate:fresh --seed
```

**Seeders incluidos:**
- `RegimenesTributariosSeeder` - 2 regímenes tributarios
- `FormasPagoSeeder` - 6 formas de pago
- `TiposCuentasSeeder` - 8 tipos de cuentas contables
- `UnidadesMedidaSeeder` - 11 unidades de medida
- `PermisosSeeder` - 68 permisos del sistema (17 módulos)
- `RolesSeeder` - 7 roles (Administrador, Gerente, Contador, etc.)
- `CargosSeeder` - 7 cargos de empleados
- `EmpresaDemoSeeder` - Empresa demo "Sistemas Ursol S.A." + sucursal
- `UsuarioAdminSeeder` - Usuario admin con todos los permisos

**Total de registros:** 112 (96 datos maestros + 16 datos demo/test)

### 6. Credenciales de Acceso

Después de ejecutar los seeders, inicia sesión con:

```
Email: admin@ursol.com
Password: admin123
```

Este usuario tiene acceso completo a todos los módulos (68 permisos).

### 6. Compilar Assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

### 7. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`

### 8. Probar la API

```bash
# Login (obtener token)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@ursol.com",
    "password": "admin123"
  }'

# Respuesta incluye token y 68 permisos del usuario
{
  "user": {...},
  "token": "1|abc123...",
  "permisos": [
    "empresas.crear",
    "empresas.leer",
    ...
  ]
}

# Usar el token en requests protegidos
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

## ⚙️ Configuración

### Configuración de Multi-Tenancy

El sistema utiliza el paquete `spatie/laravel-multitenancy`. La configuración se encuentra en [config/multitenancy.php](config/multitenancy.php).

### Configuración de Base de Datos

Cada tenant (empresa) tiene su propia base de datos. El nombre se genera automáticamente:

```
tenant_{empresa_id}_{nombre_sanitizado}
```

### Configuración de Facturación Electrónica

Para habilitar la facturación electrónica, configura:

```env
HACIENDA_API_URL=https://api.comprobanteselectronicos.go.cr
HACIENDA_API_TOKEN=tu_token_de_hacienda
```

Cada empresa debe cargar su certificado de firma digital en el sistema.

## 🏗️ Arquitectura

### Estructura de Directorios

```
ursol-cast-api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── API/          # Controladores de API
│   ├── Models/               # Modelos Eloquent
│   ├── Providers/            # Service Providers
│   └── Traits/               # Traits reutilizables
│       └── BelongsToTenant.php
├── config/                   # Archivos de configuración
├── database/
│   ├── migrations/
│   │   ├── landlord/        # Migraciones del sistema central
│   │   └── tenant/          # Migraciones de tenants
│   └── factories/           # Factories para testing
├── routes/
│   ├── api.php              # Rutas de API
│   └── web.php              # Rutas web
└── tests/                   # Tests automatizados
```

### Patrón de Diseño

El proyecto sigue el patrón **MVC** (Model-View-Controller) con algunas extensiones:

- **Models**: Lógica de negocio y relaciones de datos
- **Controllers**: Manejo de requests HTTP
- **Traits**: Comportamiento compartido (ej: `BelongsToTenant`)
- **Service Providers**: Configuración de servicios

### Base de Datos

#### Arquitectura Multi-Tenant

- **Base de datos central (landlord)**: Almacena información de empresas/tenants
- **Bases de datos por tenant**: Cada empresa tiene su propia base de datos

#### Convenciones de Nomenclatura

- **Tablas**: plural, snake_case (ej: `ordenes_compra`)
- **Columnas de timestamps**: `creado_en`, `actualizado_en`
- **Soft deletes**: columna `eliminado` (boolean)
- **Estado activo**: columna `activo` (boolean)

## 📦 Módulos del Sistema

### 1. Gestión de Empresas
- **Modelos**: [`Empresa`](app/Models/Empresa.php), [`Sucursal`](app/Models/Sucursal.php), [`Configuracion`](app/Models/Configuracion.php)
- **Funcionalidades**: Registro de empresas, gestión de sucursales, configuraciones personalizadas

### 2. Inventario
- **Modelos**: [`Producto`](app/Models/Producto.php), [`Almacen`](app/Models/Almacen.php), [`EntradaInventario`](app/Models/EntradaInventario.php), [`SalidaInventario`](app/Models/SalidaInventario.php)
- **Funcionalidades**: Control de stock, movimientos de inventario, categorización

### 3. Ventas
- **Modelos**: [`Venta`](app/Models/Venta.php), [`Cliente`](app/Models/Cliente.php), [`CuentaPorCobrar`](app/Models/CuentaPorCobrar.php)
- **Funcionalidades**: Registro de ventas, gestión de clientes, cuentas por cobrar

### 4. Compras
- **Modelos**: [`OrdenCompra`](app/Models/OrdenCompra.php), [`CuentaPorPagar`](app/Models/CuentaPorPagar.php), [`ComprobanteRecibidoElectronico`](app/Models/ComprobanteRecibidoElectronico.php)
- **Funcionalidades**: Órdenes de compra, cuentas por pagar, recepción de comprobantes electrónicos

### 5. Contabilidad
- **Modelos**: [`CuentaContable`](app/Models/CuentaContable.php), [`AsientoContable`](app/Models/AsientoContable.php), [`CajaChica`](app/Models/CajaChica.php)
- **Funcionalidades**: Plan de cuentas, asientos contables, caja chica

### 6. Recursos Humanos
- **Modelos**: [`Empleado`](app/Models/Empleado.php), [`PeriodoNomina`](app/Models/PeriodoNomina.php)
- **Funcionalidades**: Gestión de empleados, cálculo de nómina

### 7. Transporte
- **Modelos**: [`BusUnidad`](app/Models/BusUnidad.php), [`HorarioRuta`](app/Models/HorarioRuta.php)
- **Funcionalidades**: Gestión de flota, rutas y horarios

### 8. Facturación Electrónica
- **Modelos**: [`ConsecutivoFe`](app/Models/ConsecutivoFe.php), [`Cabys`](app/Models/Cabys.php)
- **Funcionalidades**: Emisión de facturas electrónicas, gestión de consecutivos, códigos CAByS

## 🔌 API Reference

### Autenticación

El sistema utiliza **Laravel Sanctum** para autenticación de API.

**Endpoints de Autenticación:**

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@ursol.com",
  "password": "admin123"
}
```

**Respuesta:**
```json
{
  "user": {
    "id": 1,
    "nombre": "Administrador",
    "email": "admin@ursol.com"
  },
  "token": "1|abc123def456...",
  "permisos": [
    "empresas.crear",
    "empresas.leer",
    "empresas.actualizar",
    "empresas.eliminar",
    ...
  ]
}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

#### Usuario Actual
```http
GET /api/me
Authorization: Bearer {token}
```

**Respuesta incluye usuario con todos sus permisos y roles**

### Headers Requeridos

Todos los endpoints (excepto `/login`) requieren:

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Protección por Permisos

Los endpoints están protegidos por el middleware `CheckPermission`. Ejemplos:

```php
// Requiere permiso específico
Route::get('/empresas', [EmpresaController::class, 'index'])
    ->middleware('permission:empresas.leer');

// Requiere uno de varios permisos
Route::post('/ventas', [VentaController::class, 'store'])
    ->middleware('permission:ventas.crear,administrador');
```

**Permisos disponibles (68 total):**
- `{módulo}.crear` - Crear registros
- `{módulo}.leer` - Ver/listar registros  
- `{módulo}.actualizar` - Modificar registros
- `{módulo}.eliminar` - Eliminar registros

**Módulos:** empresas, sucursales, almacenes, productos, categorias_producto, clientes, proveedores, ventas, compras, inventario, cuentas_contables, asientos_contables, empleados, nomina, rutas, buses, facturacion_electronica

### Registro (Deshabilitado)

Por seguridad, el endpoint `/register` está **comentado**. Los usuarios se crean manualmente por administradores o mediante seeders.

### Endpoints Principales

Ver documentación completa en [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

#### Almacenes
```http
GET    /api/almacenes           # Listar almacenes
POST   /api/almacenes           # Crear almacén
GET    /api/almacenes/{id}      # Ver almacén
PUT    /api/almacenes/{id}      # Actualizar almacén
DELETE /api/almacenes/{id}      # Eliminar almacén
```

#### Productos
```http
GET    /api/productos           # Listar productos
POST   /api/productos           # Crear producto
GET    /api/productos/{id}      # Ver producto
PUT    /api/productos/{id}      # Actualizar producto
DELETE /api/productos/{id}      # Eliminar producto
```

#### Ventas
```http
GET    /api/ventas              # Listar ventas
POST   /api/ventas              # Crear venta
GET    /api/ventas/{id}         # Ver venta
PUT    /api/ventas/{id}         # Actualizar venta
DELETE /api/ventas/{id}         # Eliminar venta
```

#### Clientes
```http
GET    /api/clientes            # Listar clientes
POST   /api/clientes            # Crear cliente
GET    /api/clientes/{id}       # Ver cliente
PUT    /api/clientes/{id}       # Actualizar cliente
DELETE /api/clientes/{id}       # Eliminar cliente
```

### Headers Requeridos

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## 🧪 Testing

El proyecto cuenta con una **suite completa de 66 tests** que verifican el funcionamiento de los componentes críticos del sistema.

### Base de Datos de Testing

Se utiliza una base de datos MySQL separada para testing:

```env
DB_DATABASE=api_db_testing
```

Crear la base de datos de testing:

```bash
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS api_db_testing;"
```

### Ejecutar Tests

```bash
# Todos los tests (66 tests)
php artisan test

# Tests específicos por clase
php artisan test --filter AuthTest          # 11 tests de autenticación
php artisan test --filter ProductoTest      # 12 tests de productos
php artisan test --filter PermissionTest    # 17 tests de permisos RBAC
php artisan test --filter RoleTest          # 10 tests unitarios de Rol
php artisan test --filter UsuarioTest       # 16 tests unitarios de Usuario

# Con cobertura
php artisan test --coverage

# Con detalles (verbose)
php artisan test --verbose
```

### Estructura de Tests

```
tests/
├── TestCase.php                    # Base con helpers (RefreshDatabase, factories)
├── Feature/                        # Tests de integración (40 tests)
│   ├── AuthTest.php               # Login, logout, tokens, permisos (11)
│   ├── ProductoTest.php           # CRUD, search, filters, multi-tenancy (12)
│   └── PermissionTest.php         # Sistema RBAC completo (17)
└── Unit/                          # Tests unitarios (26 tests)
    ├── RoleTest.php               # Modelo Rol y relaciones (10)
    └── UsuarioTest.php            # Modelo Usuario, auth, RBAC (16)
```

### Tests Implementados

#### Feature Tests (40)
- **AuthTest (11)**: Login exitoso/fallido, logout, múltiples tokens, permisos
- **ProductoTest (12)**: CRUD completo, validación, búsqueda, filtros, paginación, multi-tenancy, soft deletes
- **PermissionTest (17)**: Verificación de permisos, roles, middleware, herencia, gestión

#### Unit Tests (26)
- **RoleTest (10)**: Relaciones, `hasPermission()`, scopes, normalización, sincronización
- **UsuarioTest (16)**: Relaciones, `hasRole()`, `hasPermission()`, Sanctum, validación, autenticación

### Documentación Completa

Ver guía detallada en: [FASE_4_TESTING_COMPLETADA.md](FASE_4_TESTING_COMPLETADA.md)

## 📚 Documentación Swagger

El proyecto incluye **documentación interactiva Swagger/OpenAPI** para explorar y probar la API.

### Acceder a Swagger UI

Una vez iniciado el servidor, accede a:

```
http://localhost:8000/api/documentation
```

### Características

- ✅ **Documentación interactiva**: Prueba endpoints directamente desde el navegador
- ✅ **Autenticación Bearer**: Configura tu token una vez y úsalo en todas las peticiones
- ✅ **Schemas completos**: Modelos de datos documentados (Usuario, Producto, Rol, Permiso, etc.)
- ✅ **Ejemplos de request/response**: Ve datos de ejemplo para cada endpoint
- ✅ **Filtros y parámetros**: Documenta todos los query params disponibles

### Endpoints Documentados

#### Autenticación
- `POST /api/login` - Iniciar sesión y obtener token
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Obtener usuario autenticado con permisos

#### Productos
- `GET /api/productos` - Listar productos (filtros, búsqueda, paginación)
- `POST /api/productos` - Crear producto
- `GET /api/productos/{id}` - Obtener producto
- `PUT /api/productos/{id}` - Actualizar producto
- `DELETE /api/productos/{id}` - Eliminar producto (soft delete)

### Usar Autenticación en Swagger

1. Haz login en `POST /api/login` con credenciales válidas
2. Copia el token de la respuesta
3. Haz clic en el botón **"Authorize"** (🔓)
4. Ingresa: `Bearer {tu-token}`
5. Ahora puedes probar todos los endpoints protegidos

### Regenerar Documentación

Si modificas las anotaciones OpenAPI en los controllers:

```bash
php artisan l5-swagger:generate
```

## 🚀 Despliegue

### Preparación para Producción

1. **Optimizar Configuración**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Compilar Assets**
```bash
npm run build
```

3. **Configurar Variables de Entorno**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Configurar Redis para cache y sesiones
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

4. **Configurar Permisos**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Servidor Web

#### Nginx (Recomendado)

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /ruta/al/proyecto/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Tareas Programadas

Configurar cron para ejecutar el scheduler de Laravel:

```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisord para Queues

```ini
[program:ursol-cast-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log
```

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir [PSR-12](https://www.php-fig.org/psr/psr-12/) para código PHP
- Usar nombres descriptivos en español para variables y métodos
- Documentar funciones y clases con PHPDoc
- Escribir tests para nuevas funcionalidades

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT. Ver archivo [LICENSE](LICENSE) para más detalles.

## 📞 Soporte y Contacto

Para soporte técnico, consultas y asistencia:

- **📧 Email Corporativo**: [sistemas@ursol.com](mailto:sistemas@ursol.com)
- **📧 Email Técnico/Desarrollo**: [deadmooncr@gmail.com](mailto:deadmooncr@gmail.com)
- **💬 WhatsApp**: [+506 8868-7765](https://wa.me/50688687765)
- **🌐 Sitio Web**: [ursol.com](https://ursol.com) | [ursol.net](https://ursol.net)
- **📚 Repositorio Oficial**: [Ursol Reposit for Developers](https://sites.google.com/view/repdevursol/home/repositorio) - Plataforma de desarrollo, distribución y documentación
- **🐙 GitHub Organization**: [SistemasUrsol](https://github.com/orgs/SistemasUrsol)
- **👨‍💻 Desarrollador Principal**: [Jeremy Arias Solano](https://github.com/jeremy-sud)
- **🐛 Issues**: [GitHub Issues](https://github.com/SistemasUrsol/Ursol-CAST-API/issues)

## 🙏 Agradecimientos

- [Laravel Framework](https://laravel.com) - El framework PHP más elegante
- [Spatie Laravel Multitenancy](https://github.com/spatie/laravel-multitenancy) - Solución robusta de multi-tenancy
- Comunidad de desarrollo Laravel Costa Rica
- Nuestros clientes que confían en Sistemas Ursol S.A.

---

<p align="center">
  <strong>Desarrollado con ❤️ y el "Toque Humano" por</strong><br>
  <a href="https://ursol.com"><strong>Sistemas Ursol S.A.</strong></a><br>
  <em>Costa Rica | 30 años de experiencia tecnológica</em><br><br>
  <strong>Fundador y Visionario:</strong> Eduardo Alberto Ureña Solano<br>
  <strong>Desarrollador Principal:</strong> <a href="https://github.com/jeremy-sud">Jeremy Arias Solano</a><br><br>
  <sub>© 2025 Sistemas Ursol S.A. - Todos los derechos reservados</sub>
</p>
