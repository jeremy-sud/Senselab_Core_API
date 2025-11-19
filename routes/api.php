<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AlmacenController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\VentaController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\EmpresaController;
use App\Http\Controllers\API\ProveedorController;
use App\Http\Controllers\API\SucursalController;
use App\Http\Controllers\API\OrdenCompraController;
use App\Http\Controllers\API\EmpleadoController;
use App\Http\Controllers\API\CategoriaProductoController;
use App\Http\Controllers\API\MarcaController;
use App\Http\Controllers\API\UnidadMedidaController;
use App\Http\Controllers\API\InventarioController;
use App\Http\Controllers\API\RolController;
use App\Http\Controllers\API\PermisoController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\FormaPagoController;
use App\Http\Controllers\API\CargoController;
use App\Http\Controllers\API\CuentaPorCobrarController;
use App\Http\Controllers\API\CuentaPorPagarController;
use App\Http\Controllers\API\CabyController;
use App\Http\Controllers\API\TipoImpuestoController;
use App\Http\Controllers\API\CuentaContableController;
use App\Http\Controllers\API\AsientoContableController;
use App\Http\Controllers\API\DetalleAsientoController;
use App\Http\Controllers\API\TipoCuentaController;
use App\Http\Controllers\API\PagoController;
use App\Http\Controllers\API\TasaImpuestoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Perfil de usuario
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Empresas
    Route::apiResource('empresas', EmpresaController::class);

    // Sucursales
    Route::apiResource('sucursales', SucursalController::class);

    // Almacenes
    Route::apiResource('almacenes', AlmacenController::class);

    // Productos
    Route::apiResource('productos', ProductoController::class);

    // Clientes
    Route::apiResource('clientes', ClienteController::class);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class);

    // Ventas
    Route::apiResource('ventas', VentaController::class);

    // Órdenes de Compra
    Route::apiResource('ordenes-compra', OrdenCompraController::class);

    // Empleados
    Route::apiResource('empleados', EmpleadoController::class);

    // Categorías de Productos
    Route::apiResource('categorias-productos', CategoriaProductoController::class);

    // Marcas
    Route::apiResource('marcas', MarcaController::class);

    // Unidades de Medida
    Route::apiResource('unidades-medida', UnidadMedidaController::class);

    // Inventario - Entradas
    Route::get('/inventario/entradas', [InventarioController::class, 'indexEntradas']);
    Route::post('/inventario/entradas', [InventarioController::class, 'storeEntrada']);
    Route::get('/inventario/entradas/{id}', [InventarioController::class, 'showEntrada']);
    Route::post('/inventario/entradas/{id}/cancelar', [InventarioController::class, 'cancelarEntrada']);

    // Inventario - Salidas
    Route::get('/inventario/salidas', [InventarioController::class, 'indexSalidas']);
    Route::post('/inventario/salidas', [InventarioController::class, 'storeSalida']);
    Route::get('/inventario/salidas/{id}', [InventarioController::class, 'showSalida']);
    Route::post('/inventario/salidas/{id}/cancelar', [InventarioController::class, 'cancelarSalida']);

    // Roles (RBAC)
    Route::apiResource('roles', RolController::class);
    Route::post('/roles/{id}/permisos', [RolController::class, 'asignarPermisos']);

    // Permisos (RBAC)
    Route::apiResource('permisos', PermisoController::class);
    Route::get('/permisos/modulos/list', [PermisoController::class, 'modulos']);

    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{id}/roles', [UsuarioController::class, 'asignarRoles']);
    Route::post('/usuarios/{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);

    // Formas de Pago
    Route::apiResource('formas-pago', FormaPagoController::class);

    // Cargos
    Route::apiResource('cargos', CargoController::class);

    // Cuentas por Cobrar
    Route::apiResource('cuentas-por-cobrar', CuentaPorCobrarController::class);
    Route::get('/cuentas-por-cobrar/vencidas/list', [CuentaPorCobrarController::class, 'vencidas']);
    Route::get('/cuentas-por-cobrar/resumen/general', [CuentaPorCobrarController::class, 'resumen']);

    // Cuentas por Pagar
    Route::apiResource('cuentas-por-pagar', CuentaPorPagarController::class);
    Route::get('/cuentas-por-pagar/vencidas/list', [CuentaPorPagarController::class, 'vencidas']);
    Route::get('/cuentas-por-pagar/resumen/general', [CuentaPorPagarController::class, 'resumen']);

    // CAByS (Catálogo de Bienes y Servicios)
    Route::apiResource('cabys', CabyController::class);
    Route::post('/cabys/buscar', [CabyController::class, 'buscar']);

    // Tipos de Impuesto
    Route::apiResource('tipos-impuesto', TipoImpuestoController::class);
    Route::get('/tipos-impuesto/activos/list', [TipoImpuestoController::class, 'activos']);

    // Cuentas Contables
    Route::apiResource('cuentas-contables', CuentaContableController::class);
    Route::get('/cuentas-contables/arbol/jerarquia', [CuentaContableController::class, 'arbol']);
    Route::get('/cuentas-contables/movimientos/list', [CuentaContableController::class, 'paraMovimientos']);

    // Asientos Contables
    Route::apiResource('asientos-contables', AsientoContableController::class);
    Route::post('/asientos-contables/{id}/mayorizar', [AsientoContableController::class, 'mayorizar']);
    Route::get('/asientos-contables/{id}/validar', [AsientoContableController::class, 'validar']);

    // Detalle de Asientos Contables
    Route::get('/detalle-asientos', [DetalleAsientoController::class, 'index']);
    Route::get('/detalle-asientos/{id}', [DetalleAsientoController::class, 'show']);
    Route::get('/detalle-asientos/cuenta/{cuentaContableId}', [DetalleAsientoController::class, 'porCuenta']);
    Route::get('/detalle-asientos/reportes/libro-mayor', [DetalleAsientoController::class, 'libroMayor']);
    Route::get('/detalle-asientos/reportes/balance-comprobacion', [DetalleAsientoController::class, 'balanceComprobacion']);

    // Tipos de Cuentas Contables
    Route::apiResource('tipos-cuentas', TipoCuentaController::class);
    Route::get('/tipos-cuentas/naturaleza/{naturaleza}', [TipoCuentaController::class, 'porNaturaleza']);
    Route::get('/tipos-cuentas/activos/list', [TipoCuentaController::class, 'activos']);

    // Pagos
    Route::apiResource('pagos', PagoController::class);
    Route::get('/pagos/resumen/por-forma-pago', [PagoController::class, 'resumenPorFormaPago']);

    // Tasas de Impuesto
    Route::apiResource('tasas-impuesto', TasaImpuestoController::class);
    Route::get('/tasas-impuesto/vigente/{tipoImpuestoId}', [TasaImpuestoController::class, 'vigente']);
    Route::get('/tasas-impuesto/vigentes-actuales/list', [TasaImpuestoController::class, 'vigentesActuales']);
    Route::get('/tasas-impuesto/historico/{tipoImpuestoId}', [TasaImpuestoController::class, 'historico']);
});