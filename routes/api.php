<?php

/*
|--------------------------------------------------------------------------
| API Routes - Orquestador Principal
|--------------------------------------------------------------------------
|
| Este archivo orquesta la carga de las rutas API particionadas por dominio.
| Cada archivo en routes/api/ contiene las rutas de un módulo específico.
|
| Estructura de archivos:
| - auth.php          → Autenticación (login, logout, me)
| - core.php          → Core del sistema (empresas, sucursales, usuarios, roles)
| - inventario.php    → Gestión de inventario
| - ventas.php        → Ventas y clientes
| - compras.php       → Compras y proveedores
| - contabilidad.php  → Contabilidad y presupuestos
| - nomina.php        → Nómina y recursos humanos
| - transporte.php    → Transporte (buses, rutas, tiquetes)
| - fe.php            → Facturación electrónica y Hacienda
| - catalogos.php     → Catálogos del sistema
| - configuracion.php → Configuraciones y utilidades
| - observabilidad.php→ Health checks y métricas
| - compliance.php    → GDPR y auditoría
| - ai.php            → Inteligencia artificial
|
| NOTA: Sistema RBAC implementado. Cada ruta está protegida según permisos.
| Estructura de permisos: {modulo}.{accion}
| Acciones: leer, crear, actualizar, eliminar
|
*/

// ============================================================================
// CARGA DE RUTAS PARTICIONADAS
// ============================================================================

// Observabilidad (Health checks - sin auth)
require __DIR__ . '/api/observabilidad.php';

// Autenticación (públicas y protegidas)
require __DIR__ . '/api/auth.php';

// Compliance y GDPR
require __DIR__ . '/api/compliance.php';

// ------------------------------------------------------------------------
// RUTAS PROTEGIDAS POR DOMINIO
// ------------------------------------------------------------------------

// Core del sistema
require __DIR__ . '/api/core.php';

// Inventario y almacenes
require __DIR__ . '/api/inventario.php';

// Ventas y clientes
require __DIR__ . '/api/ventas.php';

// Compras y proveedores
require __DIR__ . '/api/compras.php';

// Contabilidad
require __DIR__ . '/api/contabilidad.php';

// Nómina y RRHH
require __DIR__ . '/api/nomina.php';

// Transporte
require __DIR__ . '/api/transporte.php';

// Facturación electrónica
require __DIR__ . '/api/fe.php';

// Catálogos del sistema
require __DIR__ . '/api/catalogos.php';

// Configuraciones del sistema
require __DIR__ . '/api/configuracion.php';

// Webhooks
require __DIR__ . '/api/webhooks.php';

// Reportes y Dashboard
require __DIR__ . '/api/reporting.php';

// Inteligencia Artificial
require __DIR__ . '/api/ai.php';
