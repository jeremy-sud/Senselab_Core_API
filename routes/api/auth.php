<?php

/**
 * Rutas de Autenticación y Perfil de Usuario
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas de Autenticación
|--------------------------------------------------------------------------
*/

// Login con rate limiting estricto (5 intentos por minuto)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Rutas Autenticadas de Perfil
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/user/permissions', function (Request $request) {
        return response()->json([
            'permissions' => $request->user()->getAllPermissions()
        ]);
    });
});
