<?php

/**
 * Rutas de Autenticación y Perfil de Usuario
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GoogleAuthController;
use App\Http\Controllers\API\AppleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas de Autenticación
|--------------------------------------------------------------------------
*/

// Login con rate limiting estricto (5 intentos por minuto)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Google OAuth
Route::get('/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/google/callback', [GoogleAuthController::class, 'handleCallback'])->name('auth.google.callback');

// Apple OAuth (el callback es POST por política de Apple form_post)
Route::get('/apple/redirect', [AppleAuthController::class, 'redirectToApple'])->name('auth.apple.redirect');
Route::post('/apple/callback', [AppleAuthController::class, 'handleCallback'])->name('auth.apple.callback');

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
