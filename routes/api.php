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
});