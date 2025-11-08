<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AlmacenController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\VentaController;
use App\Http\Controllers\API\ClienteController;
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

    // Almacenes
    Route::apiResource('almacenes', AlmacenController::class);

    // Productos
    Route::apiResource('productos', ProductoController::class);

    // Ventas
    Route::apiResource('ventas', VentaController::class);

    // Clientes
    Route::apiResource('clientes', ClienteController::class);
});