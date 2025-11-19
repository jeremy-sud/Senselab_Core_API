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
});