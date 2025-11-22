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

### Changed
- Actualizado `tests/TestCase.php` para mejorar el seeding de permisos
  - `seedPermisos()` ahora incluye 10 permisos: productos (4), clientes (2), roles (4)
  - Auto-asignación de todos los permisos al rol Administrador después de crear permisos
  - Método `assignAllPermissionsToRole()` usa `sync()` en lugar de `attach()` para prevenir duplicados en tabla pivot

### Fixed
- Habilitados 2 tests previamente omitidos en `tests/Feature/PermissionTest.php`
  - `test_middleware_verifica_permisos_correctamente` - Verifica que middleware retorna 403 sin permiso
  - `test_solo_usuarios_con_permiso_pueden_gestionar_roles` - Verifica acceso a endpoints de roles
- Corregido orden de seeding en `ProductoTest` (ahora llama `seedRoles()` antes de `seedPermisos()`)
- Solucionados errores de "Duplicate entry" en tabla `roles_permisos` usando `sync()` en lugar de `attach()`

### Security
- Implementada protección por permisos en endpoints críticos del API
- Validación estricta de permisos antes de permitir acceso a recursos

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
