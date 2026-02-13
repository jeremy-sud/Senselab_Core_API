<?php

/**
 * Rutas Core del Sistema
 * - Empresas, Sucursales, Usuarios, Roles, Permisos
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\EmpresaController;
use App\Http\Controllers\API\SucursalController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\RolController;
use App\Http\Controllers\API\PermisoController;
use App\Http\Controllers\RolPermisoController;
use App\Http\Controllers\RolUsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Core - Empresas, Sucursales, Usuarios, Roles, Permisos
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: EMPRESAS
    // ------------------------------------------------------------------------
    Route::get('/empresas', [EmpresaController::class, 'index'])
        ->middleware('permission:ver-empresas');
    Route::post('/empresas', [EmpresaController::class, 'store'])
        ->middleware('permission:crear-empresas');
    Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])
        ->middleware('permission:ver-empresas');
    Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])
        ->middleware('permission:editar-empresas');
    Route::patch('/empresas/{empresa}', [EmpresaController::class, 'update'])
        ->middleware('permission:editar-empresas');
    Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])
        ->middleware('permission:eliminar-empresas');

    // ------------------------------------------------------------------------
    // MÓDULO: SUCURSALES
    // ------------------------------------------------------------------------
    Route::get('/sucursales', [SucursalController::class, 'index'])
        ->middleware('permission:ver-sucursales');
    Route::post('/sucursales', [SucursalController::class, 'store'])
        ->middleware('permission:crear-sucursales');
    Route::get('/sucursales/{sucursal}', [SucursalController::class, 'show'])
        ->middleware('permission:ver-sucursales');
    Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])
        ->middleware('permission:editar-sucursales');
    Route::patch('/sucursales/{sucursal}', [SucursalController::class, 'update'])
        ->middleware('permission:editar-sucursales');
    Route::delete('/sucursales/{sucursal}', [SucursalController::class, 'destroy'])
        ->middleware('permission:eliminar-sucursales');

    // ------------------------------------------------------------------------
    // MÓDULO: USUARIOS
    // ------------------------------------------------------------------------
    Route::apiResource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{usuario}/cambiar-password', [UsuarioController::class, 'cambiarPassword'])
        ->middleware('permission:editar-usuarios');
    Route::post('/usuarios/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])
        ->middleware('permission:editar-usuarios');

    // ------------------------------------------------------------------------
    // MÓDULO: ROLES Y PERMISOS
    // ------------------------------------------------------------------------
    Route::apiResource('roles', RolController::class);
    Route::apiResource('permisos', PermisoController::class);
    
    // Asignación de permisos a roles
    Route::get('/roles/{rol}/permisos', [RolPermisoController::class, 'index'])
        ->middleware('permission:ver-roles');
    Route::post('/roles/{rol}/permisos', [RolPermisoController::class, 'store'])
        ->middleware('permission:editar-roles');
    Route::delete('/roles/{rol}/permisos/{permiso}', [RolPermisoController::class, 'destroy'])
        ->middleware('permission:editar-roles');
    
    // Asignación de roles a usuarios
    Route::get('/usuarios/{usuario}/roles', [RolUsuarioController::class, 'index'])
        ->middleware('permission:ver-usuarios');
    Route::post('/usuarios/{usuario}/roles', [RolUsuarioController::class, 'store'])
        ->middleware('permission:editar-usuarios');
    Route::delete('/usuarios/{usuario}/roles/{rol}', [RolUsuarioController::class, 'destroy'])
        ->middleware('permission:editar-usuarios');
});
