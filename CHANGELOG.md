# Changelog

Todos los cambios importantes en este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto se adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [Unreleased]

### Added
- Resolución de tenant por encabezados/subdominio
  - Nuevo `HeaderSubdomainTenantFinder` que prioriza `X-Empresa-Id`, `X-Empresa-Subdominio` o subdominio público y valida contra `empresas`
  - Migración `2025_11_25_120000_add_subdominio_to_empresas_table` agrega el campo único `subdominio`
  - Variables de entorno documentadas (`TENANT_BASE_DOMAIN`, `TENANT_HEADER_EMPRESA_ID`, etc.) y guía actualizada en `README.md`, `MULTI_TENANCY.md` y `API_DOCUMENTATION.md`
- **FASE 9 - Nuevas Tablas para Costa Rica** (Diciembre 2024)
  - 12 nuevas tablas para mercado costarricense:
    * Facturación Electrónica (3): mensajes_hacienda, tipos_comprobantes_fe, codigos_actividad_economica
    * Tributación (2): declaraciones_tributarias, retenciones_impuestos
    * Bancos (2): cuentas_bancarias, movimientos_bancarios
    * RRHH (2): deducciones_legales, planillas_ccss
    * Comercio (2): tipos_clientes, zonas_geograficas
    * Seguridad (1): logs_acceso_sistema
  - 4 nuevos seeders con 28 registros iniciales:
    * TiposComprobantesFESeeder (9 tipos DGT)
    * DeduccionesLegalesSeeder (6 deducciones)
    * TiposClientesSeeder (6 tipos)
    * ZonasGeograficasCRSeeder (7 provincias)
  - 16 Foreign Keys configuradas (100% compatibles)
  - 42 índices optimizados (7 UNIQUE + 34 INDEX + 1 FULLTEXT)
  - Verificación completa de integridad documentada
  - Total: 78 tablas en base de datos (65 + 12 + migrations)
- Middleware `CheckPermission` para protección de rutas basada en permisos RBAC
  - Soporte para múltiples permisos con lógica OR (el usuario necesita AL MENOS uno de los permisos)
  - Respuestas 403 con lista de permisos requeridos cuando el usuario no tiene acceso
  - Aplicado a rutas críticas: `/api/productos` (requiere `ver-productos`) y `/api/roles` (requiere `ver-roles`, `editar-roles`)
- Integración de Sentry para monitoreo de errores en producción
  - Archivo de configuración `config/sentry.php` con opciones completas
  - Variables de entorno en `.env.example` para configuración de Sentry
  - Documentación completa en `SENTRY_SETUP.md` con guía de instalación y uso
  - Soporte para traces, profiles, y breadcrumbs
- Sistema de backups automáticos de base de datos
  - Script `database/backups/backup.sh` para backups automáticos con rotación de 7 días
  - Script `database/backups/restore.sh` para restauración interactiva o por archivo
  - Documentación completa en `BACKUP_STRATEGY.md` con estrategia 3-2-1
  - Configuración de cron para backups automáticos
  - .gitignore para evitar subir archivos de backup al repositorio
- CHANGELOG.md siguiendo formato Keep a Changelog
- Versionado semántico v1.0.0 en composer.json y package.json
- Git tag v1.0.0 para marcar el primer release oficial

### Changed
- **Actualización de estadísticas del proyecto** (Diciembre 2024)
  - 78 tablas en base de datos (65 originales + 12 FASE 9 + migrations)
  - 77 migraciones CREATE ejecutadas
  - 65 modelos Eloquent
  - 60 controladores implementados
  - 127 tests automatizados (100% pasando)
  - 140 registros iniciales (112 originales + 28 nuevos)
- Actualizado `tests/TestCase.php` para mejorar el seeding de permisos
  - `seedPermisos()` ahora incluye 10 permisos: productos (4), clientes (2), roles (4)
  - Auto-asignación de todos los permisos al rol Administrador después de crear permisos
  - Método `assignAllPermissionsToRole()` usa `sync()` en lugar de `attach()` para prevenir duplicados en tabla pivot
- Actualizado `tests/Feature/ProductoTest.php` para incluir `seedRoles()` antes de `seedPermisos()`
  - Asegura que el rol Administrador existe antes de asignar permisos
  - 11 tests actualizados con el orden correcto de seeding

### Fixed
- Habilitados 2 tests previamente omitidos en `tests/Feature/PermissionTest.php`
  - `test_middleware_verifica_permisos_correctamente` - Verifica que middleware retorna 403 sin permiso
  - `test_solo_usuarios_con_permiso_pueden_gestionar_roles` - Verifica acceso a endpoints de roles
- Corregido orden de seeding en `ProductoTest` (ahora llama `seedRoles()` antes de `seedPermisos()`)
- Solucionados errores de "Duplicate entry" en tabla `roles_permisos` usando `sync()` en lugar de `attach()`

### Security
- VentaController fuerza aislamiento multi-tenant
  - Todas las operaciones (`index`, `store`, `show`, `update`, `destroy`, `generatePdfReport`) usan la empresa resuelta por el tenant finder
  - Requests y modelos (`StoreVentaRequest`, `Venta`, traits `BelongsToTenant`/`HasEmpresaContext`) ahora eliminan `empresa_id` del payload y usan siempre el tenant activo
  - Documentación y ejemplos de consumo enfatizan el uso obligatorio de los headers de empresa
- Implementada protección por permisos en endpoints críticos del API
- Validación estricta de permisos antes de permitir acceso a recursos
- Configuración de Sentry para NO enviar información personal por defecto (SENTRY_SEND_DEFAULT_PII=false)
- Scripts de backup con manejo seguro de credenciales (no hardcodeadas)
- Backups con permisos restrictivos (600) recomendados en documentación

## [1.0.0] - 2024-01-XX (Fecha pendiente de release)

### Added
- Sistema completo de API REST para gestión empresarial multiempresa
- 68 permisos granulares (17 módulos × 4 acciones: ver, crear, editar, eliminar)
- Autenticación basada en Laravel Sanctum
- Sistema RBAC completo con roles, permisos y asignaciones
- 77 migraciones de base de datos
- 127 tests automatizados con PHPUnit (100% pasando)
- 60 controladores implementados (44 en API/, 16 en raíz)
- 65 modelos Eloquent sincronizados
- 78 tablas en base de datos MySQL
- Documentación OpenAPI/Swagger completa
- Documentación OpenAPI/Swagger completa
- Docker Compose para ambiente de desarrollo

### Modules
- Empresas y Sucursales
- Usuarios y Roles
- Productos y Categorías
- Clientes y Proveedores
- Inventario y Almacenes
- Ventas y Compras
- Contabilidad
- Nómina
- Cuentas por Cobrar/Pagar
- Facturación Electrónica (Costa Rica)
- Reportes
- Configuración

[Unreleased]: https://github.com/usuario/ursol-cast-api/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/usuario/ursol-cast-api/releases/tag/v1.0.0
