# Changelog

Todos los cambios importantes en este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto se adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [Unreleased]

### Added
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
- 66 migraciones de base de datos
- 81 tests automatizados con PHPUnit
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
