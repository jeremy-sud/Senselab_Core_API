# Curso Completo: Ursol CAST API — De Cero a Experto

**Fecha de creación:** 1 de abril de 2026  
**Versión de la API:** v4.1.0  
**Desarrollado por:** Sistemas Ursol S.A.  
**Desarrollador principal:** Jeremy Arias Solano

> Este documento es un curso integral y autodidacta diseñado para que cualquier desarrollador —desde junior hasta senior— comprenda la API en su totalidad: arquitectura, módulos, patrones, seguridad, testing, despliegue, y su evolución planificada (roadmap). Está basado enteramente en el código fuente real y documentación verificada del proyecto.

---

## Tabla de Contenidos

1. [Introducción y Visión General](#1-introducción-y-visión-general)
2. [Instalación y Puesta en Marcha](#2-instalación-y-puesta-en-marcha)
3. [Arquitectura y Estructura del Proyecto](#3-arquitectura-y-estructura-del-proyecto)
4. [Multi-Tenancy: Aislamiento por Empresa](#4-multi-tenancy-aislamiento-por-empresa)
5. [Seguridad y Autorización](#5-seguridad-y-autorización)
6. [Endpoints y Módulos de Negocio](#6-endpoints-y-módulos-de-negocio)
7. [Service Layer y DTOs](#7-service-layer-y-dtos)
8. [Facturación Electrónica y Hacienda](#8-facturación-electrónica-y-hacienda)
9. [Inteligencia Artificial Integrada](#9-inteligencia-artificial-integrada)
10. [Testing y Calidad de Código](#10-testing-y-calidad-de-código)
11. [Observabilidad, Logging y Monitoreo](#11-observabilidad-logging-y-monitoreo)
12. [Despliegue: Docker y Kubernetes](#12-despliegue-docker-y-kubernetes)
13. [Roadmap y Cambios Futuros](#13-roadmap-y-cambios-futuros)
14. [Recursos y Documentación Complementaria](#14-recursos-y-documentación-complementaria)

---

# 1. Introducción y Visión General

## 1.1. ¿Qué es Ursol CAST API?

**Ursol CAST API** es un sistema ERP (Enterprise Resource Planning) empresarial completo construido sobre **Laravel 12** y **PHP 8.4**, diseñado específicamente para empresas costarricenses. El acrónimo CAST hace referencia a las áreas principales: **Contabilidad, Administración, Servicios y Tecnología**.

El proyecto es desarrollado por **Sistemas Ursol S.A.**, empresa con más de 30 años de experiencia en soluciones tecnológicas en Costa Rica. Representa más de 5 años de evolución continua en sistemas empresariales.

## 1.2. Misión del proyecto

Proporcionar una plataforma ERP integral, moderna y asequible que permita a las empresas costarricenses:

- Cumplir con normativas locales (DGT, Hacienda, CCSS)
- Optimizar procesos operacionales
- Mejorar toma de decisiones con IA y análisis de datos
- Escalar sin limitaciones técnicas
- Integrar fácilmente con otros sistemas

## 1.3. Estado actual — Estadísticas verificadas (Marzo 2026)

| Métrica | Valor |
|---------|-------|
| Controladores API | **93** |
| Policies RBAC | **80+** (100% cobertura) |
| Modelos Eloquent | **87** |
| Migraciones | **98** |
| API Resources | **79** |
| Archivos de tests | **142** (1261 tests, 0 failing) |
| Servicios | **62** (10 IA + 8 Hacienda + 44 core) |
| DTOs | **60** (~65% cobertura) |
| Factories | **85** |
| FormRequests | **170+** |
| PHPStan | Level 8, **0 errores** |
| Providers | **3** (App, Auth, Observer) |
| Permisos RBAC | **68** (17 módulos × 4 acciones) |
| Roles | **8** predefinidos |
| Traits reutilizables | **12** |

## 1.4. Stack tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Lenguaje | PHP | 8.4+ |
| Framework | Laravel | 12.x |
| Base de datos | MySQL | 8.0+ |
| Cache/Colas/Sesiones | Redis | 7 Alpine |
| Autenticación | Laravel Sanctum | 4.2+ |
| Multi-tenancy | Spatie Multitenancy | 4.0 |
| Testing | PHPUnit | 11.5 |
| Análisis estático | PHPStan | 2.1 |
| Mutation testing | Infection PHP | — |
| Contract testing | Pact PHP | — |
| Load testing | k6 | — |
| Documentación API | L5-Swagger (OpenAPI) | 9.0 |
| Monitoreo | Sentry | 4.20+ |
| IA primaria | Google Gemini | Free tier |
| IA secundaria | OpenAI | GPT |
| Contenedores | Docker | Compose v2 |
| Orquestación | Kubernetes | Kustomize |
| Servidor web | Nginx | 1.25 |
| PDF | DomPDF | — |

## 1.5. Funcionalidades principales

### Módulos de negocio
- **Core**: Empresas, sucursales, usuarios, roles, permisos
- **Ventas**: Clientes, ventas, detalles, estados, formas de pago
- **Compras**: Proveedores, órdenes de compra, detalles, pagos
- **Inventario**: Almacenes, productos, entradas, salidas, stock en tiempo real
- **Contabilidad**: Plan de cuentas, asientos, presupuestos, impuestos, cuentas bancarias
- **Nómina**: Empleados, cargos, departamentos, períodos, pagos, deducciones, planilla CCSS
- **Facturación Electrónica**: Comprobantes v4.4, firma XAdES-EPES, integración con Hacienda
- **Transporte**: Unidades de bus, modelos, rutas, horarios, tiquetes
- **Catálogos**: Formas de pago, unidades de medida, marcas, tipos de cuenta, CABYS
- **Compliance**: GDPR, auditoría, retención de datos

### Funcionalidades transversales
- **IA**: 10 servicios, 32+ endpoints (OCR, chatbot, predicciones, anomalías, scoring, contenido)
- **Multi-tenancy**: Aislamiento completo por empresa
- **RBAC**: 68 permisos granulares, 8 roles, 80+ policies
- **Seguridad**: OWASP Top 10, encriptación AES-256, rate limiting, audit logs
- **API versioning**: `/api/v1/`, `/api/v2/`, header-based

---

# 2. Instalación y Puesta en Marcha

## 2.1. Requisitos del sistema

### Software requerido
- **PHP 8.4+** con extensiones: `mysql`, `xml`, `mbstring`, `curl`, `zip`, `gd`, `bcmath`, `intl`, `pcntl`, `redis`
- **Composer 2.x**
- **MySQL 8.0+**
- **Redis 7+**
- **Git**
- **Docker** y **Docker Compose** (recomendado)
- **Node.js y pnpm** (opcional, para assets frontend)

### Verificar requisitos
```bash
php -v          # Debe ser 8.4+
composer -V     # Debe ser 2.x
mysql --version # Debe ser 8.0+
redis-cli --version
git --version
docker --version
```

## 2.2. Opción A — Instalación con Docker (recomendada)

Esta es la forma más rápida y consiste de iniciar todo el entorno:

```bash
# 1. Clonar el repositorio
git clone https://github.com/jeremy-sud/Ursol-CAST-API.git
cd Ursol-CAST-API

# 2. Ejecutar script de instalación automática
bash docker/docker-start.sh
```

El script `docker-start.sh` automatiza:
1. Verificar que Docker esté instalado y corriendo
2. Crear `.env` desde `.env.docker`
3. Construir contenedores (`docker-compose build`)
4. Iniciar servicios (`docker-compose up -d`)
5. Esperar a que MySQL esté listo
6. Instalar dependencias (`composer install`)
7. Generar `APP_KEY`
8. Ejecutar migraciones y seeders
9. Generar documentación Swagger
10. Configurar permisos

### Servicios Docker levantados

| Servicio | Container | Puerto | Descripción |
|----------|-----------|--------|-------------|
| Nginx | `ursol_nginx` | 8000 | Servidor web |
| PHP-FPM | `ursol_php` | 9000 (interno) | Aplicación Laravel |
| MySQL | `ursol_mysql` | 3306 | Base de datos |
| Redis | `ursol_redis` | 6379 | Cache, colas, sesiones |
| PHPMyAdmin | `ursol_phpmyadmin` | 8080 | Admin BD (profile: tools) |
| Mailhog | `ursol_mailhog` | 1025/8025 | Testing emails (profile: dev) |
| Queue Worker | `ursol_queue` | — | Procesador de colas (profile: production) |
| Scheduler | `ursol_scheduler` | — | Tareas programadas (profile: production) |

### Comandos Docker útiles (Makefile)
```bash
make help          # Ver todos los comandos
make build         # Construir contenedores
make up            # Iniciar contenedores
make down          # Detener contenedores
make restart       # Reiniciar contenedores
make logs          # Ver logs de todos los servicios
make logs-php      # Ver logs de PHP
make logs-mysql    # Ver logs de MySQL
make shell         # Acceder a shell de PHP
make shell-mysql   # Acceder a shell de MySQL
make shell-redis   # Acceder a shell de Redis
make test          # Ejecutar tests
make test-coverage # Tests con cobertura
make ps            # Estado de contenedores
```

## 2.3. Opción B — Instalación manual (sin Docker)

```bash
# 1. Clonar
git clone https://github.com/jeremy-sud/Ursol-CAST-API.git
cd Ursol-CAST-API

# 2. Instalar dependencias PHP
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Editar .env con tu configuración de BD
#    DB_HOST=127.0.0.1  DB_PORT=3306
#    DB_DATABASE=api_db  DB_USERNAME=root  DB_PASSWORD=...
#    REDIS_HOST=127.0.0.1  REDIS_PORT=6379

# 5. Ejecutar migraciones y seeders
php artisan migrate --seed

# 6. Generar documentación Swagger
php artisan l5-swagger:generate

# 7. Iniciar servidor
php artisan serve
```

## 2.4. Verificación de la instalación

```bash
# API responde
curl http://localhost:8000/up
# → {"status":"ok"}

# Swagger disponible
# Abrir: http://localhost:8000/api/documentation

# Login funcional
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@ursol.com", "password": "admin123"}'
# → {"success":true,"data":{"usuario":{...},"token":"1|abc...","permisos":[...]}}

# Tests pasan
php artisan test
# → Tests: 1261 passed (5 skipped), Duration: ~25s
```

## 2.5. Credenciales por defecto (desarrollo)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@ursol.com` | `admin123` |

> **Importante**: Los seeders de demostración (`DemoDataSeeder`) crean estos usuarios. Los datos de producción se cargan con `MasterDataSeeder` (14 catálogos idempotentes).

## 2.6. Comandos Artisan esenciales

```bash
php artisan route:list              # Ver todas las rutas registradas
php artisan test                    # Ejecutar suite completa de tests
php artisan migrate:status          # Estado de migraciones
php artisan cache:clear             # Limpiar cache
php artisan route:cache             # Cachear rutas (producción)
php artisan l5-swagger:generate     # Regenerar docs Swagger
php vendor/bin/phpstan analyse app/ --level 8  # Análisis estático
```

---

# 3. Arquitectura y Estructura del Proyecto

## 3.1. Visión general de la arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CAPA DE PRESENTACIÓN                        │
│  ┌──────────────┐  ┌────────────┐  ┌───────────────────────────┐  │
│  │  Swagger UI  │  │ Rate Limit │  │ CORS / Security Headers   │  │
│  └──────────────┘  └────────────┘  └───────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE ROUTING                              │
│  routes/api/ (14 archivos por dominio)                              │
│  Middleware: auth:sanctum, throttle, permission:*                   │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE CONTROLADORES                       │
│  93 Controllers → FormRequests (170+) → Policies (80+)            │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE SERVICIOS                           │
│  62 Services (BaseService → Domain Services)                       │
│  60 DTOs (CreateDTO / UpdateDTO / Transformers)                    │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE DOMINIO                             │
│  95 Modelos Eloquent + Traits (12) + Observers (4) + Events (6)   │
│  Domain Exceptions (11 tipos)                                      │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE INFRAESTRUCTURA                     │
│  MySQL 8.0 (100 migraciones) │ Redis 7 │ Queue Workers │ Sentry    │
└─────────────────────────────────────────────────────────────────────┘
```

## 3.2. Estructura de carpetas detallada

```
Ursol-CAST-API/
├── app/
│   ├── Console/                    # Comandos Artisan custom (make:erp-module)
│   ├── DTOs/                       # 60 Data Transfer Objects
│   │   ├── API/                    # CreateDTO y UpdateDTO por módulo
│   │   ├── Responses/              # ErrorResponseDTO, PaginatedResponseDTO
│   │   └── Transformers/           # ClienteTransformer, ProductoTransformer, VentaTransformer
│   ├── Events/                     # 6 eventos de dominio
│   ├── Exceptions/                 # DomainException + 11 excepciones específicas
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/                # 77 controladores principales
│   │   │   ├── Api/                # 5 controladores (Hacienda + AI)
│   │   │   └── *.php               # 6 controladores raíz
│   │   ├── Middleware/             # CheckPermission, Sunset, etc.
│   │   ├── Requests/              # 170+ FormRequests (Store*/Update*)
│   │   └── Resources/             # 79 API Resources
│   ├── Jobs/                       # 10 jobs asincrónicos
│   │   └── Hacienda/              # 3 jobs de facturación electrónica
│   ├── Listeners/                  # DispatchWebhookListener
│   ├── Models/                     # 95 modelos Eloquent
│   ├── Multitenancy/              # TenantFinder custom
│   ├── Observers/                  # 4 observers activos
│   ├── Policies/                   # 80+ policies RBAC (BasePolicy base)
│   ├── Providers/                  # 3 providers (App, Auth, Observer)
│   ├── Rules/                      # 2 reglas custom (CrIdentificacion, CrTelefono)
│   ├── Services/                   # 62 servicios
│   │   ├── AI/                     # 10 servicios de IA
│   │   ├── Hacienda/              # 8 servicios de Hacienda
│   │   └── *.php                   # 44 servicios core
│   └── Traits/                     # 12 traits reutilizables
├── bootstrap/                      # Bootstrap de Laravel
├── config/                         # Configuración
│   ├── app.php                     # Configuración general
│   ├── hacienda.php                # Facturación electrónica v4.4
│   ├── multitenancy.php            # Multi-tenancy (Spatie)
│   ├── gemini.php                  # Google Gemini API
│   ├── openai.php                  # OpenAI API
│   ├── rate-limiting.php           # Rate limiting granular
│   ├── sanctum.php                 # Autenticación
│   ├── cors.php                    # CORS headers
│   └── encryption.php              # Encriptación de campos
├── database/
│   ├── factories/                  # 93 factories para testing
│   ├── migrations/                 # 100 migraciones
│   └── seeders/                    # MasterDataSeeder + DemoDataSeeder
├── docker/                         # Configuración Docker
│   ├── docker-start.sh             # Script de instalación automática
│   ├── nginx/                      # Configuración Nginx
│   ├── php/                        # Configuración PHP-FPM
│   ├── mysql/                      # Configuración MySQL
│   └── redis/                      # Configuración Redis
├── docs/                           # Documentación extensa
│   ├── api/                        # Documentación de endpoints
│   ├── guides/                     # Guías (instalación, testing, multi-tenancy)
│   ├── hacienda/                   # Documentación de facturación electrónica
│   └── sprints/                    # Notas de sprints
├── kubernetes/                     # Manifests K8s (base + overlays)
├── routes/
│   ├── api.php                     # Orquestador principal
│   └── api/                        # 14 archivos de rutas por dominio
├── tests/
│   ├── TestCase.php                # Clase base con helpers
│   ├── Feature/                    # 75 archivos — integración y E2E
│   ├── Unit/                       # 58 archivos — lógica de negocio
│   ├── Contract/                   # 9 archivos — Pact contract testing
│   └── Load/                       # 6 archivos — k6 load testing
├── docker-compose.yml              # Docker Compose (7 servicios)
├── Dockerfile                      # Multi-stage build PHP 8.4
├── Makefile                        # Automatización (Docker, CI, deploy)
├── phpstan.neon                    # PHPStan Level 8
├── phpunit.xml                     # PHPUnit config
├── infection.json5                 # Mutation testing
└── ROADMAP.md                      # Roadmap completo del proyecto
```

## 3.3. Flujo de una petición HTTP completa

```
Cliente HTTP (Postman, Frontend, curl)
   │
   ▼
Nginx (puerto 8000) ─── Archivos estáticos
   │
   ▼
PHP-FPM (puerto 9000)
   │
   ▼
Laravel Bootstrap (bootstrap/app.php)
   │
   ▼
Middleware Pipeline:
   1. CORS Headers
   2. Security Headers (X-Frame-Options, X-Content-Type-Options, etc.)
   3. Rate Limiting (throttle:120,1)
   4. Sanctum Authentication (auth:sanctum)
   5. Permission Check (permission:productos.ver)
   6. Tenant Resolution (HeaderSubdomainTenantFinder)
   │
   ▼
Route Match (routes/api/{dominio}.php)
   │
   ▼
Controller Method:
   1. $this->authorize('action', $model)  ← Policy check
   2. FormRequest validation               ← Input validation
   3. $service->método($dto)               ← Business logic
   │
   ▼
Service Layer:
   1. DTO validation
   2. Business rules
   3. Eloquent queries (tenant-scoped)
   4. Event dispatching
   │
   ▼
Response:
   ApiResponse trait → JSON envelope
   {success, data, message, meta, trace_id}
```

## 3.4. Patrones de diseño implementados

| Patrón | Implementación |
|--------|---------------|
| **Service Layer** | 62 servicios en `app/Services/` que separan lógica de negocio de controladores |
| **DTO** | 60 objetos inmutables en `app/DTOs/` para transferir datos entre capas |
| **Repository** | Implementado implícitamente vía Eloquent con scopes y traits |
| **Observer** | 4 observers para side effects (auditoría, cache, permisos) |
| **Event/Listener** | 6 eventos de dominio con listeners registrados |
| **Policy** | 80+ policies para autorización RBAC |
| **Factory** | 93 factories para generación de datos de test |
| **Trait** | 12 traits para reutilización de lógica transversal |
| **Strategy** | AIServiceInterface con múltiples implementaciones (Gemini, OpenAI) |
| **Template Method** | BasePolicy y BaseService como clases abstractas |
| **Envelope Response** | ApiResponse trait estandariza todas las salidas JSON |

---

# 4. Multi-Tenancy: Aislamiento por Empresa

## 4.1. ¿Qué es multi-tenancy en este proyecto?

Cada **empresa** es un "tenant" (inquilino). Todos los datos del sistema están filtrados por `empresa_id`. Un usuario solo puede ver datos de su propia empresa. El modelo `Empresa` extiende `Spatie\Multitenancy\Models\Tenant`.

## 4.2. Resolución del tenant

El sistema identifica a qué empresa pertenece cada petición mediante:

### Por header HTTP (recomendado)
```bash
curl -X GET http://localhost:8000/api/ventas \
    -H "Authorization: Bearer $TOKEN" \
    -H "X-Empresa-Id: 123"
```

Headers soportados: `X-Empresa-Id`, `X-Tenant-Id`, `X-Tenant`.

### Por subdominio
```bash
curl -X GET https://mi-empresa.api.ursol.com/api/ventas \
    -H "Authorization: Bearer $TOKEN"
```

### Validación de seguridad

Incluso si se envía un header con un `empresa_id` diferente, el método `resolveEmpresaOrFail()` compara ese ID con el `empresa_id` del usuario autenticado para **prevenir accesos cruzados**.

## 4.3. Trait `HasEmpresaContext`

Este trait es central para el multi-tenancy en controladores:

```php
use App\Traits\HasEmpresaContext;

class EjemploController extends Controller {
    use HasEmpresaContext;

    public function index(Request $request) {
        // Obtener empresa del usuario autenticado o tenant actual
        $empresaId = $this->getEmpresaId();

        // Filtrar queries por empresa
        $items = Modelo::where('empresa_id', $empresaId)->paginate(15);
        return ModeloResource::collection($items);
    }

    public function show(int $id) {
        $item = Modelo::findOrFail($id);
        // Verificar que el modelo pertenece a la empresa del usuario
        $this->assertEmpresa($item);  // Lanza 403 si no coincide
        return new ModeloResource($item);
    }
}
```

**Métodos disponibles:**
- `getEmpresaId(): ?int` — Obtiene empresa del usuario Sanctum o tenant actual
- `scopeEmpresa(Builder $query): Builder` — Aplica filtro `empresa_id` al query
- `assertEmpresa(Model $model): void` — Verifica pertenencia; lanza 403 si no coincide
- `resolveEmpresaOrFail(?int $requested): int` — Valida que el empresa_id solicitado coincida

## 4.4. Trait `BelongsToTenant`

Se aplica a modelos Eloquent para multi-tenancy automático:

```php
class Producto extends Model {
    use BelongsToTenant;
    // ...
}
```

- Auto-filtra queries por `empresa_id` (global scope)
- Auto-asigna `empresa_id` en `creating`
- Provee relación `empresa()` → `BelongsTo Empresa`

## 4.5. Cache tenant-aware

Cada entrada de cache se etiqueta con el tenant:

```php
protected array $cacheTags = ['productos', 'empresa_123'];
```

Esto permite invalidar cache selectivamente por empresa sin afectar a otros tenants.

---

# 5. Seguridad y Autorización

## 5.1. Autenticación con Laravel Sanctum

La API usa **tokens personales** para autenticación stateless.

### Flujo de autenticación

```
1. POST /api/login  (email + password)
       ↓
2. Validar credenciales contra BD
       ↓
3. Revocar tokens anteriores
       ↓
4. Crear nuevo token → "1|abc123..."
       ↓
5. Devolver: {usuario, token, permisos[]}
       ↓
6. Cliente usa en headers: Authorization: Bearer 1|abc123...
```

### Ejemplo completo de login

```bash
# Request
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@ursol.com", "password": "admin123"}'

# Response (200)
{
  "success": true,
  "data": {
    "usuario": {
      "id": 1,
      "nombre": "Administrador Sistema",
      "email": "admin@ursol.com",
      "empresa_id": 1,
      "roles": [{"id": 1, "nombre": "Administrador", "slug": "administrador"}],
      "empresa": {"id": 1, "nombre": "Sistemas Ursol S.A."}
    },
    "token": "1|dkjf9283hd9fh2938hf9823hf9823hf9823h",
    "permisos": [
      "empresas.crear", "empresas.leer", "empresas.actualizar", "empresas.eliminar",
      "productos.crear", "productos.leer", ...
    ]
  },
  "message": "Login exitoso"
}
```

### Ejemplo de logout

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 1|dkjf92..." \
  -H "Accept: application/json"
# → {"success": true, "message": "Logout exitoso"}
```

## 5.2. Sistema RBAC completo

### Estructura de permisos

El sistema define **68 permisos** organizados en **17 módulos** con **4 acciones** cada uno:

| Módulo | Permisos |
|--------|----------|
| empresas | ver, crear, editar, eliminar |
| sucursales | ver, crear, editar, eliminar |
| usuarios | ver, crear, editar, eliminar |
| roles | ver, crear, editar, eliminar |
| productos | ver, crear, editar, eliminar |
| clientes | ver, crear, editar, eliminar |
| proveedores | ver, crear, editar, eliminar |
| inventario | ver, crear, editar, eliminar |
| ventas | ver, crear, editar, eliminar |
| compras | ver, crear, editar, eliminar |
| contabilidad | ver, crear, editar, eliminar |
| nomina | ver, crear, editar, eliminar |
| cuentas_cobrar | ver, crear, editar, eliminar |
| cuentas_pagar | ver, crear, editar, eliminar |
| facturacion | ver, crear, editar, eliminar |
| reportes | ver, crear, editar, eliminar |
| configuracion | ver, crear, editar, eliminar |

### Roles predefinidos

| Rol | Descripción | Alcance |
|-----|-------------|---------|
| Administrador | Acceso total | 68/68 permisos |
| Gerente | Gestión operacional | Ventas, compras, inventario, reportes |
| Contador | Módulo contable | Contabilidad, cuentas, facturación, reportes |
| Vendedor | Módulo ventas | Ventas, clientes, productos (solo lectura) |
| Comprador | Módulo compras | Compras, proveedores |
| Bodeguero | Módulo inventario | Inventario, almacenes, productos |
| Usuario | Acceso básico | Lectura limitada |
| Auditor | Solo lectura en auditoría | Ver en todos los módulos |

### Protección en rutas

```php
// routes/api/inventario.php
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/productos', [ProductoController::class, 'index'])
        ->middleware('permission:ver-productos');
    Route::post('/productos', [ProductoController::class, 'store'])
        ->middleware('permission:crear-productos');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permission:editar-productos');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('permission:eliminar-productos');
});
```

### Policies — Autorización en controladores

```php
// En un controlador:
public function update(UpdateProductoRequest $request, Producto $producto) {
    $this->authorize('update', $producto);
    // Si llega aquí, el usuario tiene permiso Y el producto pertenece a su empresa
    // ...
}

// La Policy verifica automáticamente:
// 1. ¿El usuario tiene el permiso 'productos.editar'?
// 2. ¿El producto pertenece a la empresa del usuario?
```

**Flujo de autorización completo:**
```
Request ──→ Middleware auth:sanctum ──→ Middleware permission:editar-productos
    │
    ▼
Controller ──→ $this->authorize('update', $producto)
    │
    ▼
ProductoPolicy::update($usuario, $producto)
    │
    ├──→ $this->ownsResource($usuario, $producto)  // empresa_id match
    └──→ $this->hasPermission($usuario, 'productos.editar')  // RBAC check
```

## 5.3. Rate Limiting

Configurado en `config/rate-limiting.php`:

| Contexto | Límite | Ventana |
|----------|--------|---------|
| Autenticados (estándar) | 120 requests | 1 minuto |
| No autenticados | 30 requests | 1 minuto |
| Login | 5 intentos | 1 minuto |
| Pagos | Limitado | Por operación |
| Reportes | Limitado | Generación heavy |
| Hacienda API | Respeta límites de Hacienda | — |
| **IP blocking** | 10 violaciones → bloqueo 1h | — |

## 5.4. Cobertura OWASP Top 10

| Categoría OWASP | Implementación |
|------------------|---------------|
| A01: Broken Access Control | RBAC (68 permisos) + 80+ Policies + Multi-tenancy |
| A02: Cryptographic Failures | AES-256 + bcrypt + SSL/TLS + campos encriptados |
| A03: Injection | Eloquent ORM (no raw SQL) + FormRequests + DTOs |
| A04: Insecure Design | Service Layer + DTOs + validación exhaustiva |
| A05: Security Misconfiguration | Headers de seguridad + CORS + SESSION_ENCRYPT=true |
| A06: Vulnerable Components | Composer audit + Dependabot |
| A07: Auth Failures | Sanctum + rate limiting + password policy fuerte |
| A08: Data Integrity | Firma digital XAdES-EPES + audit trails |
| A09: Logging/Monitoring | Sentry + audit logs + trace_id + structured logging |
| A10: SSRF | Validación de URLs + no permite requests a IPs internas |

## 5.5. Datos sensibles protegidos

```php
// Modelo Empresa - campos ocultos en respuestas
protected $hidden = [
    'certificado_llave_fe',
    'pin_llave_fe_hash'
];

// Modelo Usuario - password nunca expuesto
protected $hidden = [
    'password_hash'
];
```

Campos financieros sensibles se encriptan con `HasEncryptedAttributes` usando `EncryptionService` (AES-256).

---

# 6. Endpoints y Módulos de Negocio

## 6.1. Organización de rutas

Las rutas están particionadas en **14 archivos** en `routes/api/`:

```
routes/api/
├── auth.php           → Autenticación (login, logout, me)
├── core.php           → Empresas, sucursales, usuarios, roles, permisos
├── inventario.php     → Almacenes, productos, entradas, salidas
├── ventas.php         → Ventas, clientes
├── compras.php        → Compras, proveedores
├── contabilidad.php   → Contabilidad, presupuestos, impuestos
├── nomina.php         → Nómina y RRHH
├── transporte.php     → Buses, rutas, horarios
├── fe.php             → Facturación electrónica y Hacienda
├── catalogos.php      → Catálogos del sistema
├── configuracion.php  → Configuraciones y utilidades
├── observabilidad.php → Health checks y métricas (sin auth)
├── compliance.php     → GDPR y auditoría
└── ai.php             → Inteligencia artificial
```

## 6.2. Módulo Core — Empresas y Usuarios

### Endpoints de Empresas

```
GET    /api/empresas               → Listar (paginado, búsqueda)
POST   /api/empresas               → Crear nueva empresa
GET    /api/empresas/{id}          → Ver detalle
PUT    /api/empresas/{id}          → Actualizar
DELETE /api/empresas/{id}          → Eliminar (soft delete)
```

**Ejemplo — Crear empresa:**
```bash
curl -X POST http://localhost:8000/api/empresas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Mi Empresa S.A.",
    "razon_social": "Mi Empresa Sociedad Anónima",
    "num_identificacion_dgt": "3-101-123456",
    "tipo_identificacion": "02",
    "regimen_tributario_id": 1,
    "email": "contacto@miempresa.com",
    "telefono": "22223333",
    "direccion": "San José, Costa Rica",
    "activo": true
  }'
```

### Endpoints de Usuarios
```
GET    /api/usuarios               → Listar usuarios de la empresa
POST   /api/usuarios               → Crear usuario
GET    /api/usuarios/{id}          → Ver usuario
PUT    /api/usuarios/{id}          → Actualizar
DELETE /api/usuarios/{id}          → Desactivar
```

## 6.3. Módulo de Ventas

### Endpoints
```
GET    /api/ventas                 → Listar ventas (filtros: fecha, estado, cliente)
POST   /api/ventas                 → Crear venta con detalles
GET    /api/ventas/{id}            → Ver venta con detalles
PUT    /api/ventas/{id}            → Actualizar venta
DELETE /api/ventas/{id}            → Anular venta

GET    /api/clientes               → Listar clientes
POST   /api/clientes               → Crear cliente
GET    /api/clientes/{id}          → Ver cliente
PUT    /api/clientes/{id}          → Actualizar cliente
DELETE /api/clientes/{id}          → Desactivar cliente
```

### Ejemplo — Crear venta
```json
POST /api/ventas
{
  "cliente_id": 5,
  "fecha_venta": "2026-04-01",
  "moneda": "CRC",
  "forma_pago_id": 1,
  "observaciones": "Venta de contado",
  "detalles": [
    {
      "producto_id": 10,
      "cantidad": 2,
      "precio_unitario": 15000.00,
      "descuento": 0
    },
    {
      "producto_id": 22,
      "cantidad": 1,
      "precio_unitario": 8500.00,
      "descuento": 500
    }
  ]
}
```

### Estados de una venta
- `borrador` — Venta en preparación
- `pendiente` — Esperando pago o aprobación
- `pagada` — Pago completado
- `anulada` — Venta cancelada

## 6.4. Módulo de Inventario

### Endpoints principales
```
GET    /api/almacenes              → Listar almacenes
POST   /api/almacenes              → Crear almacén
GET    /api/productos              → Listar productos (filtros, búsqueda)
POST   /api/productos              → Crear producto
GET    /api/inventario-productos   → Stock por almacén
POST   /api/entradas-inventario    → Registrar entrada
POST   /api/salidas-inventario     → Registrar salida
```

### Flujo de movimiento de inventario
```
Entrada (compra/ajuste) → EntradaInventario → DetalleEntrada
    ↓
    InventarioProducto.stock_actual += cantidad
    InventarioProducto.costo_promedio se recalcula

Salida (venta/consumo) → SalidaInventario → DetalleSalida
    ↓
    InventarioProducto.stock_actual -= cantidad
    Si stock < stock_minimo → InventarioBajoEvent
```

## 6.5. Módulo de Contabilidad

### Endpoints principales
```
GET    /api/cuentas-contables      → Plan de cuentas (jerárquico)
POST   /api/cuentas-contables      → Crear cuenta
GET    /api/asientos-contables     → Listar asientos
POST   /api/asientos-contables     → Crear asiento (partida doble)
GET    /api/tasas-impuesto         → Tasas vigentes
GET    /api/presupuestos           → Presupuestos
POST   /api/presupuestos           → Crear presupuesto
```

### Principio de partida doble
```json
POST /api/asientos-contables
{
  "fecha_asiento": "2026-04-01",
  "concepto": "Venta de productos al contado",
  "detalles": [
    {"cuenta_contable_id": 101, "debe": 38500.00, "haber": 0},
    {"cuenta_contable_id": 401, "debe": 0, "haber": 33190.00},
    {"cuenta_contable_id": 210, "debe": 0, "haber": 5310.00}
  ]
}
// total_debe (38500) == total_haber (33190 + 5310) ✓
```

## 6.6. Módulo de Nómina

### Endpoints principales
```
GET    /api/empleados              → Listar empleados
POST   /api/empleados              → Crear empleado
GET    /api/periodos-nomina        → Períodos de nómina
POST   /api/periodos-nomina        → Crear período
POST   /api/pagos-nomina          → Procesar pago
GET    /api/planilla-ccss          → Generar planilla CCSS
```

## 6.7. Formato de respuesta estándar

Todas las respuestas siguen el formato envelope del trait `ApiResponse`:

### Respuesta exitosa
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Respuesta paginada
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  },
  "trace_id": "..."
}
```

### Respuesta de error
```json
{
  "success": false,
  "message": "Los datos proporcionados no son válidos",
  "errors": {
    "email": ["El formato del email no es válido"],
    "nombre": ["El campo nombre es requerido"]
  },
  "trace_id": "..."
}
```

---

# 7. Service Layer y DTOs

## 7.1. Patrón Service Layer

La lógica de negocio reside en servicios (`app/Services/`), no en controladores. Un controlador refactorizado pasa de ~800 líneas a ~100:

### Antes (controlador monolítico):
```php
class VentaController {
    public function store(Request $request) {
        // 70+ líneas: validación, transformación, lógica de negocio,
        // cálculos, relaciones, cache, eventos...
    }
}
```

### Después (controlador con Service Layer):
```php
class VentaController {
    public function __construct(private VentaService $service) {}

    public function store(StoreVentaRequest $request) {
        $dto = VentaCreateDTO::fromRequest($request);
        $venta = $this->service->crear($dto);
        return response()->json(VentaTransformer::transform($venta));
    }
}
```

## 7.2. Servicios disponibles (62 total)

### Servicios Core (44)
| Servicio | Métodos principales |
|----------|-------------------|
| VentaService | crear, obtener, listar, porCliente, entreFechas, totalEnPeriodo, cambiarEstado |
| ClienteService | crear, actualizar, eliminar, obtener, listar, buscar, activos, saldoPendiente |
| ProductoService | crear, actualizar, eliminar, obtener, listar, buscar, activos |
| ProveedorService | crear, actualizar, eliminar, obtener, listar, buscar, activos, saldoPendiente |
| AlmacenService | crear, actualizar, eliminar, obtener, listar |
| EntradaInventarioService | crear, obtener, listar, porAlmacen, entreFechas |
| SalidaInventarioService | crear, obtener, listar, porAlmacen, entreFechas |
| AsientoContableService | crear, obtener, listar, entreFechas, validarBalanceo |
| CuentaContableService | crear, actualizar, obtener, listar |
| EmpleadoService | crear, actualizar, eliminar, obtener, listar |
| PeriodoNominaService | crear, obtener, listar, cerrar |
| UsuarioService | crear, actualizar, cambiarPassword, asignarRol, desactivar |
| PermissionService | checkPermission, cachePermisos, limpiarCache |
| EncryptionService | encrypt, decrypt, rotateKey, hashForSearch |
| WebhookService | crear, actualizar, eliminar, listar |
| WebhookDispatcherService | dispatch, retry |
| AuditService | registrar, consultar |
| ... | (30+ servicios adicionales) |

### Servicios de IA (10)
| Servicio | Función |
|----------|---------|
| GeminiService | Integración con Google Gemini (chat, visión, pro) |
| OpenAIService | Integración con OpenAI GPT (fallback) |
| OCRService | Escaneo de facturas con Gemini Vision (92% precisión) |
| ChatbotService | Asistente virtual con consultas en lenguaje natural |
| PredictionService | Predicciones de demanda de inventario |
| AnomalyDetectionService | Detección de fraudes y errores (95% precisión) |
| ContentGeneratorService | Generación de emails, reportes, contenido |
| CabysClassifierService | Clasificación automática de códigos CABYS (98% precisión) |
| CreditScoringService | Calificación de riesgo crediticio (0-100) |
| AIServiceInterface | Contrato/interfaz para todos los servicios de IA |

### Servicios de Hacienda (8)
| Servicio | Función |
|----------|---------|
| HaciendaApiClient | Cliente HTTP con OAuth 2.0 y rate limiting |
| HaciendaIntegrationService | Orquestación del flujo completo de FE |
| OAuthTokenManager | Gestión de tokens OAuth de Hacienda |
| ClaveNumericaGenerator | Generación de clave numérica (50 chars) |
| XmlComprobanteBuilder | Construcción de XML según esquema v4.4 |
| XadesEpesSigner | Firma digital XAdES-EPES |
| FirmaDigitalService | Gestión de certificados digitales |
| RateLimiter | Rate limiting para API de Hacienda |

## 7.3. DTOs (Data Transfer Objects)

Los DTOs son objetos inmutables que transportan datos entre capas:

```php
// Ejemplo: VentaCreateDTO
class VentaCreateDTO {
    public function __construct(
        public readonly int $clienteId,
        public readonly string $fechaVenta,
        public readonly string $moneda,
        public readonly int $formaPagoId,
        public readonly ?string $observaciones,
        public readonly array $detalles,
    ) {}

    public static function fromRequest(Request $request): self {
        return new self(
            clienteId: $request->input('cliente_id'),
            fechaVenta: $request->input('fecha_venta'),
            moneda: $request->input('moneda'),
            formaPagoId: $request->input('forma_pago_id'),
            observaciones: $request->input('observaciones'),
            detalles: $request->input('detalles'),
        );
    }
}
```

**Tipos de DTOs:**
- `{Modelo}CreateDTO` — Datos para crear (44 DTOs)
- `{Modelo}UpdateDTO` — Datos para actualizar (14 DTOs)
- `ErrorResponseDTO` — Respuesta de error estandarizada
- `PaginatedResponseDTO` — Metadata de paginación
- Transformers: `ClienteTransformer`, `ProductoTransformer`, `VentaTransformer`

---

# 8. Facturación Electrónica y Hacienda

## 8.1. Contexto legal

Costa Rica exige facturación electrónica según normativa de la Dirección General de Tributación (DGT). La versión actual del esquema es **v4.4** (DGT-R-000-2024). Todo comprobante debe:

1. Tener una **clave numérica** de 50 caracteres
2. Estar firmado digitalmente con **XAdES-EPES**
3. Enviarse al API de recepción de Hacienda
4. Almacenar la respuesta (aceptado/rechazado)

## 8.2. Tipos de comprobantes electrónicos

| Código | Tipo | Descripción |
|--------|------|-------------|
| 01 | Factura electrónica | Documento estándar para operaciones con crédito fiscal |
| 02 | Nota de débito | Incrementa monto de factura existente |
| 03 | Nota de crédito | Anula parcial o totalmente una factura |
| 04 | Tiquete electrónico | Para consumidores finales (sin crédito fiscal) |

## 8.3. Flujo completo de facturación electrónica

```
1. POST /api/comprobantes
       ↓
2. ComprobanteElectronicoService::crear($dto)
       ↓
3. ClaveNumericaGenerator — genera clave de 50 chars
       ↓
4. ConsecutivoFeService — asigna consecutivo secuencial
       ↓
5. XmlComprobanteBuilder — construye XML según esquema v4.4
       ↓
6. XadesEpesSigner — firma XML con certificado digital
       ↓
7. EnviarComprobanteJob → Queue 'hacienda'
       ↓
8. HaciendaApiClient — envía XML firmado al API
       ↓
9. ConsultarEstadoComprobanteJob — polling estado
       ↓
10. ProcesarRespuestaHaciendaJob — procesa ACK/rechazo
       ↓
11. Actualiza estado del comprobante local
       ↓
12. FacturaEmitidaEvent → Webhooks, notificaciones
```

## 8.4. Configuración de Hacienda

```php
// config/hacienda.php (valores clave)
'version_esquema' => '4.4',
'proveedor_sistemas' => env('HACIENDA_PROVEEDOR_SISTEMAS'),
'environment' => env('HACIENDA_ENV', 'sandbox'),

'api_urls' => [
    'sandbox' => [
        'oauth' => 'https://idp.comprobanteselectronicos.go.cr/.../token',
        'recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1/recepcion',
    ],
    'production' => [
        'oauth' => 'https://idp.comprobanteselectronicos.go.cr/.../token',
        'recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion',
    ],
],
```

## 8.5. Endpoints de facturación electrónica

```
GET    /api/comprobantes               → Listar (filtros: tipo, estado, fecha, clave)
POST   /api/comprobantes               → Crear y enviar
GET    /api/comprobantes/{id}          → Ver detalle con XML
GET    /api/consecutivos               → Ver consecutivos por tipo
GET    /api/mensajes-hacienda          → Respuestas de Hacienda
POST   /api/certificados-digitales     → Subir certificado
```

---

# 9. Inteligencia Artificial Integrada

## 9.1. Arquitectura de IA

La IA usa **Google Gemini** (tier gratuito) como proveedor principal con **OpenAI** como fallback. Todos los servicios implementan `AIServiceInterface` para intercambiabilidad.

```
┌─────────────────────────────────────────────┐
│             AI Controller                    │
│  (AnomalyController, ContentController,      │
│   CabysController, CreditController)         │
└──────────────────┬──────────────────────────┘
                   │
          ┌────────▼────────┐
          │ AIServiceInterface│
          └────────┬────────┘
                   │
       ┌───────────┼───────────┐
       │                       │
┌──────▼──────┐         ┌─────▼──────┐
│ GeminiService│         │ OpenAIService│
│  (Primario)  │         │  (Fallback)  │
│              │         │              │
│ gemini-2.0-  │         │ gpt-4o       │
│  flash       │         │              │
│ gemini-1.5-  │         │              │
│  flash (OCR) │         │              │
│ gemini-1.5-  │         │              │
│  pro         │         │              │
└──────────────┘         └──────────────┘
```

## 9.2. Servicios de IA y sus casos de uso

### OCR de Facturas
- **Servicio:** `OCRService` → usa `gemini-1.5-flash` (Vision)
- **Precisión:** 92%
- **Uso:** Escanear facturas en imagen/PDF y extraer datos estructurados
```
POST /api/ai/ocr/invoice         → Escanear una factura
POST /api/ai/ocr/batch           → Procesamiento batch
GET  /api/ai/ocr/capabilities    → Verificar capacidades
```

### Chatbot ERP
- **Servicio:** `ChatbotService` → usa `gemini-2.0-flash`
- **Uso:** Consultas en lenguaje natural sobre datos del ERP
```
POST /api/ai/chat                → Enviar mensaje
POST /api/ai/chat/intent         → Detectar intención
GET  /api/ai/chat/history        → Historial
```

### Predicciones de Inventario
- **Servicio:** `PredictionService`
- **Uso:** Predecir demanda futura, alertas de stock, estacionalidad
```
GET  /api/ai/predictions/inventory/{product}    → Predicción por producto
GET  /api/ai/predictions/demand                 → Demanda general
POST /api/ai/predictions/restock                → Sugerencias de restock
```

### Detección de Anomalías
- **Servicio:** `AnomalyDetectionService`
- **Precisión:** 95%
- **Uso:** Detectar fraudes, errores contables, transacciones sospechosas
```
POST /api/ai/anomaly/detect      → Analizar transacciones
GET  /api/ai/anomaly/report      → Reporte de anomalías
```

### Clasificación CABYS
- **Servicio:** `CabysClassifierService`
- **Precisión:** 98%
- **Uso:** Clasificar productos/servicios con códigos tributarios de Costa Rica
```
POST /api/ai/cabys/classify      → Clasificar producto
POST /api/ai/cabys/batch         → Clasificación batch
GET  /api/ai/cabys/suggestions   → Sugerencias
```

### Credit Scoring
- **Servicio:** `CreditScoringService`
- **Uso:** Evaluar riesgo crediticio de clientes (escala 0-100)
```
GET  /api/ai/credit/{client}     → Score de cliente
POST /api/ai/credit/evaluate     → Evaluación personalizada
GET  /api/ai/credit/report       → Reporte crediticio
```

### Generación de Contenido
- **Servicio:** `ContentGeneratorService`
- **Uso:** Generar emails de cobro, agradecimiento, reportes automáticos
```
POST /api/ai/content/email       → Generar email
POST /api/ai/content/report      → Generar reporte
POST /api/ai/content/template    → Usar plantilla IA
```

## 9.3. Configuración de Gemini

```php
// config/gemini.php
'models' => [
    'chat'   => 'gemini-2.0-flash',   // 15 RPM
    'vision' => 'gemini-1.5-flash',    // 15 RPM (OCR)
    'pro'    => 'gemini-1.5-pro',      // 2 RPM (tareas complejas)
],
'generation' => [
    'temperature'      => 0.7,
    'top_p'            => 0.95,
    'top_k'            => 40,
    'max_output_tokens' => 2048,
],
```

---

# 10. Testing y Calidad de Código

## 10.1. Estructura de testing

El proyecto implementa **4 capas de testing**:

| Capa | Archivos | Tests | Herramienta | Propósito |
|------|----------|-------|-------------|-----------|
| Feature | 75 | ~600 | PHPUnit | Integración HTTP, E2E |
| Unit | 58 | ~350 | PHPUnit | Lógica de negocio |
| Contract | 9 | ~47 | Pact PHP | Contratos API |
| Load | 6 | — | k6 | Performance |
| Mutation | — | — | Infection PHP | Calidad de tests |

**Totales:** 142 archivos, 1261 tests, 0 failing, ~3500+ assertions.

## 10.2. Ejecución de tests

```bash
# Suite completa
php artisan test
# → Tests: 1261 passed (5 skipped), Duration: ~25s

# Por suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Contract-Consumer
php artisan test --testsuite=Contract-Provider

# Por dominio
php artisan test --filter "AuthTest|PermissionTest|RolTest"
php artisan test --filter "VentaTest|CuentaContable"
php artisan test --filter "InventarioTest|AlmacenTest|ProductoTest"
php artisan test --filter "NominaTest|EmpleadoTest"
php artisan test --filter "Hacienda|FacturacionElectronica"
php artisan test --filter "MultiTenant|Encryption|RateLimit"

# Servicios unitarios
php artisan test tests/Unit/Services/

# Con cobertura
make test-coverage
```

## 10.3. Estructura de archivos de test

```
tests/
├── TestCase.php                          # Base con helpers (auth, empresa, seeders)
├── Feature/                              # 75 archivos — integración y E2E
│   ├── AuthTest.php                      # Autenticación Sanctum
│   ├── ProductoTest.php                  # CRUD productos
│   ├── VentaTest.php                     # Ventas con Service Layer
│   ├── ClienteTest.php                   # Clientes
│   ├── EmpresaTest.php                   # Multi-tenant empresas
│   ├── InventarioTest.php                # Inventario
│   ├── MultiTenantIsolationTest.php      # Aislamiento cross-tenant
│   ├── RateLimitingGranularTest.php      # Rate limiting por contexto
│   ├── EncryptionGranularTest.php        # Encriptación AES-256
│   ├── StructuredLoggingTest.php         # Logging + trace_id
│   ├── FacturacionElectronicaE2ETest.php # E2E facturación
│   └── ... (60+ archivos más)
├── Unit/                                 # 58 archivos — lógica de negocio
│   ├── Services/                         # 36 archivos — servicios
│   │   ├── VentaServiceTest.php
│   │   ├── Hacienda/HaciendaApiClientTest.php
│   │   └── ...
│   ├── Models/ModelRelationsTest.php     # Relaciones Eloquent
│   ├── Traits/ApiResponseTest.php        # Trait ApiResponse
│   └── Validation/                       # Reglas custom
├── Contract/                             # 9 archivos — Pact
│   ├── Consumer/                         # 6 consumers
│   └── Provider/                         # 1 verificación
└── Load/                                 # 6 archivos — k6
    ├── smoke-test.js                     # 1 VU, 30s
    └── load-ventas-facturacion.js        # 10 VUs, 2min
```

## 10.4. PHPStan — Análisis estático

```bash
# Ejecutar análisis
php vendor/bin/phpstan analyse app/ --level 8

# Resultado esperado: 0 errores
```

PHPStan Level 8 es el nivel más estricto. Configuración en `phpstan.neon`.

## 10.5. Mutation Testing con Infection

Modifica el código fuente (mutaciones) y verifica que los tests detecten los cambios:

```bash
vendor/bin/infection --min-msi=50
```

Meta: MSI (Mutation Score Indicator) ≥ 50%.

## 10.6. CI/CD Pipeline

GitHub Actions ejecuta automáticamente:
1. **Tests** — PHPUnit (1261 tests)
2. **PHPStan** — Level 8 (0 errores)
3. **Mutation testing** — Infection (MSI ≥ 50%)
4. **Cobertura** — Codecov

Targets del Makefile para CI:
```bash
make ci-test      # Tests + coverage
make ci-quality   # PHPStan + mutation
make ci-security  # Security audit
```

---

# 11. Observabilidad, Logging y Monitoreo

## 11.1. Logging estructurado

Cada entrada de log incluye contexto enriquecido:
```json
{
  "message": "Venta creada exitosamente",
  "level": "info",
  "trace_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": 1,
  "empresa_id": 1,
  "method": "POST",
  "url": "/api/ventas",
  "ip": "192.168.1.100",
  "timestamp": "2026-04-01T10:30:00Z"
}
```

## 11.2. Integración con Sentry

Sentry (`sentry/sentry-laravel`) captura errores no manejados, performance traces, y envía alertas. Configuración en `config/sentry.php`.

## 11.3. Auditoría

Dos mecanismos de auditoría:

1. **AuditObserver** — Registra automáticamente todos los cambios en modelos (before/after)
2. **AuditoriaActividad** — Log detallado por operación (usuario, IP, user agent, timestamp)
3. **LogAccesoSistema** — Registra cada acceso al sistema

## 11.4. Health checks

```bash
# Endpoint sin autenticación
GET /up
# → {"status": "ok"}

# Health check completo (verificar BD, Redis, etc.)
GET /api/health
```

## 11.5. Métricas Prometheus

El endpoint de métricas expone datos para scraping de Prometheus:
```
GET /metrics
# → application_requests_total, application_request_duration_seconds, etc.
```

---

# 12. Despliegue: Docker y Kubernetes

## 12.1. Docker para desarrollo

El `docker-compose.yml` define todo el stack:

```yaml
services:
  nginx:      # Servidor web → puerto 8000
  php:        # Laravel PHP-FPM 8.4
  mysql:      # BD MySQL 8.0 → puerto 3306
  redis:      # Cache/colas → puerto 6379
  phpmyadmin: # Admin BD → puerto 8080 (profile: tools)
  mailhog:    # Email testing → puerto 8025 (profile: development)
  queue:      # Queue worker (profile: production)
  scheduler:  # Cron tasks (profile: production)
```

### Dockerfile (multi-stage build)

```
Etapa 1 (composer): PHP 8.4-alpine
  → Instala dependencias con Composer
  → Optimiza autoloader

Etapa 2 (final): PHP 8.4-fpm-alpine
  → Extensiones: bcmath, gd, intl, mbstring, pdo_mysql, pcntl, zip, redis
  → XDebug opcional (INSTALL_XDEBUG=true)
  → Usuario laravel (no root)
  → Health check: php-fpm -t
```

## 12.2. Kubernetes para producción

Estructura de manifests con Kustomize:

```
kubernetes/
├── base/                    # Configuración base
│   ├── namespace.yaml       # Namespace: ursol-cast
│   ├── configmap.yaml       # Variables de entorno
│   ├── secret.yaml          # Credenciales
│   ├── deployment.yaml      # API (PHP-FPM + Nginx sidecar)
│   ├── service.yaml         # Services
│   ├── ingress.yaml         # Ingress con TLS
│   ├── pvc.yaml             # Persistent Volumes
│   ├── statefulset.yaml     # MySQL y Redis
│   ├── hpa.yaml             # Autoscaling
│   └── jobs.yaml            # Queue Workers y Scheduler
└── overlays/
    ├── development/         # 1 réplica, recursos reducidos
    └── production/          # 3+ réplicas, HPA, SSL
```

### Despliegue en Kubernetes

```bash
# Desarrollo
kubectl apply -k kubernetes/overlays/development

# Producción
kubectl apply -k kubernetes/overlays/production
```

### Especificaciones de producción

| Componente | Réplicas | RAM | CPU | Storage |
|-----------|----------|-----|-----|---------|
| API | 3-20 (HPA) | 256-512Mi | 250-500m | — |
| MySQL | 1 (StatefulSet) | 512Mi-1Gi | — | 50Gi |
| Redis | 1 (StatefulSet) | 256Mi | — | 1Gi |
| Queue Worker | 3 | 256Mi | 250m | — |
| Scheduler | 1 (CronJob) | 128Mi | 100m | — |

### Autoscaling (HPA)
- **Min replicas**: 3
- **Max replicas**: 20
- **Target CPU**: 70%
- **Target Memory**: 80%

## 12.3. Pipeline de despliegue

```bash
# Desplegar a staging
make deploy-staging

# Desplegar a producción
make deploy-prod

# Rollback
make rollback
```

---

# 13. Roadmap y Cambios Futuros

## 13.1. Versiones y fases completadas

| FASE | Versión | Descripción | Estado |
|------|---------|-------------|--------|
| 1-6 | v2.0-2.2 | Seguridad, Observabilidad, Auditoría, PHPStan, Rendimiento, CQRS | ✅ |
| 7.1-7.2 | v2.4.0 | Limpieza crítica + Tests críticos | ✅ |
| 8 | v2.5.0 | Service Layer en 6 módulos | ✅ |
| 9 | v2.6.0 | Unit tests servicios + 10 bugs corregidos | ✅ |
| 10 | v2.7.0 | PHPStan baseline→0 + CQRS 3 módulos | ✅ |
| 11 | v2.8.0 | 42 tests corregidos + 8 bugs producción | ✅ |
| 12 | v2.9.0 | PHPUnit attributes + 35 tests nuevos | ✅ |
| 13 | v3.0.0 | CQRS dead code eliminado (34 archivos) + 7 factories | ✅ |
| 17 | v3.0.1 | Seguridad pre-producción (Swagger auth, rate limiters) | ✅ |
| 14 | v3.1.0 | Cobertura tests: +203 tests, 5 bugs corregidos | ✅ |
| 14.5 | v3.1.1 | Correcciones auditoría: N+1, cache tenant, error handling | ✅ |
| 15 | v3.2.0 | Excepciones de dominio + Respuestas API estandarizadas | ✅ |
| 16 | v3.3.0 | Service Layer secundarios: BaseService, 8 servicios, 72 tests | ✅ |
| 18.5 | v3.2.1 | Seeders separados + Migration rollback + Load testing k6 | ✅ |
| 18 | v4.0.0 | API versionado v1/v2 + header-based + Sunset middleware | ✅ |
| 19.7 | v4.1.0 | PHPStan 98→0 errores + 17 DTOs + 3 observers eliminados | ✅ |

## 13.2. Fases pendientes

### Orden de ejecución planificado

```
MEDIO-BAJO (features avanzados):
├── FASE 20: Webhooks + Event-Driven             → v4.2.0
├── FASE 21: Reporting Engine + Dashboard API    → v4.3.0
│
BAJO (escalabilidad futura):
└── FASE 22: Optimización + Escalabilidad        → v5.0.0

BAJO (deuda técnica):
└── Deuda técnica de auditoría                   → (integrar en fases)
```

### FASE 20 — Webhooks + Event-Driven (→ v4.2.0)

**Objetivo:** Implementar sistema completo de webhooks y arquitectura event-driven.

- Interfaz de configuración de webhooks por empresa
- Firma HMAC-SHA256 de payloads
- Reintentos automáticos con backoff exponencial
- Dashboard de logs de webhook
- Eventos configurables por tipo (venta, factura, pago, inventario bajo)
- API para CRUD de suscripciones webhook

### FASE 21 — Reporting Engine + Dashboard API (→ v4.3.0)

**Objetivo:** Motor de reportes dinámicos y API para dashboards.

- Generación de reportes PDF/Excel
- Reportes contables: Mayor General, Balance de Comprobación, Estado de Resultados
- Reportes de ventas: por período, cliente, producto, vendedor
- Reportes de inventario: valorización, movimientos, rotación
- API de KPIs para dashboards
- Programación de reportes automáticos (vía Queue)

### FASE 22 — Optimización + Escalabilidad (→ v5.0.0)

**Objetivo:** Preparar la API para escalar a cientos de empresas y miles de usuarios concurrentes.

- Read replicas de MySQL
- Cache warming estratégico
- Database sharding por tenant (evaluación)
- Optimización de queries N+1 restantes
- Compresión de respuestas (Gzip/Brotli)
- CDN para assets estáticos

## 13.3. Deuda técnica pendiente

| Issue | Prioridad | Descripción |
|-------|-----------|-------------|
| Respuestas API inconsistentes | Media | 3 formatos distintos en algunos endpoints legacy |
| 4 modelos con timestamps inconsistentes | Baja | ZonaGeografica, CuentaBancaria, etc. usan created_at en vez de creado_en |
| Solo 1 excepción custom de dominio | Baja | La mayoría de módulos ya usa excepciones de dominio (11 tipos) |
| Historial git con secrets | Baja | Pendiente limpiar con BFG Repo-Cleaner |

## 13.4. Historial de versiones reciente

| Versión | Fecha | Cambios principales |
|---------|-------|-------------------|
| v4.1.0 | Mar 2026 | PHPStan 0 errores, 60 DTOs, 3 observers eliminados |
| v4.0.0 | Mar 2026 | API Versioning v1/v2, header-based, Sunset middleware |
| v3.3.0 | — | BaseService, 8 servicios secundarios, 72 tests |
| v3.2.0 | Jul 2025 | Domain Exceptions, ApiResponse trait, Rules CR |
| v3.1.1 | Mar 2026 | N+1 fixes, cache tenant, error handling seguro |
| v3.1.0 | Jul 2025 | +203 tests, 5 bug fixes, >60% cobertura |
| v3.0.1 | Mar 2026 | Swagger auth, rate limiters, FormRequest validation |
| v3.0.0 | Mar 2026 | CQRS eliminado (34 archivos), 7 factories corregidas |

---

# 14. Recursos y Documentación Complementaria

## 14.1. Documentación interna

| Documento | Ubicación | Descripción |
|-----------|-----------|-------------|
| Guía de instalación | `docs/guides/INSTALLATION_GUIDE.md` | Paso a paso para nuevos devs |
| Guía de testing | `docs/guides/TESTING_GUIDE.md` | Ejecución y estructura de tests |
| Guía de multi-tenancy | `docs/guides/MULTI_TENANCY.md` | Patrón de tenant y traits |
| Guía de refactorización | `docs/guides/REFACTORIZACION_CONTROLADORES.md` | Service Layer pattern |
| Cómo probar la API | `docs/guides/COMO_PROBAR_API.md` | Swagger, Postman, curl |
| Documentación API REST | `docs/api/API_DOCUMENTATION.md` | Endpoints detallados |
| Guía de Policies | `docs/api/POLICIES_GUIDE.md` | RBAC y autorización |
| Guía de FormRequests | `docs/api/FORMREQUESTS_USAGE_GUIDE.md` | Validaciones |
| Facturación electrónica | `docs/hacienda/FACTURACION_ELECTRONICA_API.md` | Hacienda integration |
| Funcionalidades IA | `docs/IA_FUNCIONALIDADES.md` | 10 servicios de IA detallados |
| Glosario completo | `docs/GLOSARIO_COMPLETO_URSOL_CAST_API.md` | Toda la terminología |
| Estado del proyecto | `ESTADO_ACTUAL_PROYECTO.md` | Estadísticas verificadas |
| Roadmap | `ROADMAP.md` | Fases y planificación |
| Changelog | `CHANGELOG.md` | Historial de cambios |
| Seguridad | `SECURITY.md` | OWASP, políticas de seguridad |

## 14.2. Herramientas de desarrollo

| Herramienta | Uso | Acceso |
|-------------|-----|--------|
| Swagger UI | Documentación interactiva y prueba de endpoints | `http://localhost:8000/api/documentation` |
| PHPMyAdmin | Administración visual de MySQL | `http://localhost:8080` (profile: tools) |
| Mailhog | Testing de emails | `http://localhost:8025` (profile: development) |
| Sentry | Monitoreo de errores en producción | Dashboard externo |
| Codecov | Cobertura de código | GitHub integration |

## 14.3. Comandos de referencia rápida

```bash
# === Docker ===
make up                    # Iniciar todo
make down                  # Detener todo
make restart               # Reiniciar
make shell                 # Acceder a PHP container
make logs                  # Ver logs

# === Testing ===
php artisan test           # Suite completa (1261 tests)
make test-coverage         # Con cobertura
php vendor/bin/phpstan analyse app/ --level 8  # Análisis estático
vendor/bin/infection        # Mutation testing

# === Base de datos ===
php artisan migrate         # Ejecutar migraciones
php artisan migrate:status  # Ver estado
php artisan db:seed         # Ejecutar seeders
php artisan migrate:fresh --seed  # Recrear BD completa

# === Cache ===
php artisan cache:clear     # Limpiar cache
php artisan route:cache     # Cachear rutas
php artisan config:cache    # Cachear configuración

# === Swagger ===
php artisan l5-swagger:generate  # Regenerar docs

# === Despliegue ===
make deploy-staging         # Deploy a staging
make deploy-prod            # Deploy a producción
make rollback               # Rollback
make ci-test                # Pipeline CI
```

## 14.4. Credenciales y contacto

| Concepto | Valor |
|----------|-------|
| Email soporte | sistemas@ursol.com |
| WhatsApp | +506 8868 7765 |
| Sitio web | ursol.com / ursol.net |
| GitHub | github.com/SistemasUrsol/Ursol-CAST-API |
| Login demo | admin@ursol.com / admin123 |

---

**Última actualización:** 1 de abril de 2026  
**Versión del curso:** 1.0.0  
**Basado en:** Código fuente Ursol CAST API v4.1.0 (auditoría marzo 2026)