<?php

/**
 * Rutas de Transporte
 * - Buses, Rutas, Horarios, Tiquetes
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\BusUnidadController;
use App\Http\Controllers\API\ModeloBusController;
use App\Http\Controllers\API\RutaController;
use App\Http\Controllers\API\HorarioRutaController;
use App\Http\Controllers\API\TiqueteDetalleController;
use App\Http\Controllers\API\TransportSelfServiceTicketController;
use App\Http\Controllers\API\TransportTripSeatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Transporte
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: BUSES/UNIDADES DE TRANSPORTE
    // Permisos: buses.leer, buses.crear, buses.actualizar, buses.eliminar
    // ------------------------------------------------------------------------
    Route::apiResource('buses-unidades', BusUnidadController::class)
        ->middleware(['permission:ver-buses,buses.crear,buses.actualizar,buses.eliminar']);
    Route::get('/buses-unidades/disponibles/list', [BusUnidadController::class, 'disponibles'])
        ->middleware('permission:ver-buses');
    Route::get('/buses-unidades/resumen/flota', [BusUnidadController::class, 'resumenFlota'])
        ->middleware('permission:ver-buses');
    Route::get('/buses-unidades/por-modelo/{modeloId}', [BusUnidadController::class, 'porModelo'])
        ->middleware('permission:ver-buses');

    // Modelos de Buses
    Route::apiResource('modelos-buses', ModeloBusController::class);

    // ------------------------------------------------------------------------
    // MÓDULO: RUTAS DE TRANSPORTE
    // Permisos: rutas.leer, rutas.crear, rutas.actualizar, rutas.eliminar
    // ------------------------------------------------------------------------
    Route::apiResource('rutas', RutaController::class)
        ->middleware(['permission:ver-rutas,rutas.crear,rutas.actualizar,rutas.eliminar']);
    Route::get('/rutas/activas/list', [RutaController::class, 'activas'])
        ->middleware('permission:ver-rutas');
    Route::post('/rutas/calcular-tarifa', [RutaController::class, 'calcularTarifa'])
        ->middleware('permission:ver-rutas');
    Route::get('/rutas/{id}/estadisticas', [RutaController::class, 'estadisticas'])
        ->middleware('permission:ver-rutas');

    // ------------------------------------------------------------------------
    // MÓDULO: HORARIOS DE RUTA (VIAJES PROGRAMADOS)
    // ------------------------------------------------------------------------
    Route::apiResource('horarios-ruta', HorarioRutaController::class)
        ->middleware(['permission:ver-rutas,rutas.crear,rutas.actualizar,rutas.eliminar']);
    Route::post('/horarios-ruta/{id}/iniciar-viaje', [HorarioRutaController::class, 'iniciarViaje'])
        ->middleware('permission:editar-rutas');
    Route::post('/horarios-ruta/{id}/finalizar-viaje', [HorarioRutaController::class, 'finalizarViaje'])
        ->middleware('permission:editar-rutas');
    Route::post('/horarios-ruta/{id}/cancelar', [HorarioRutaController::class, 'cancelar'])
        ->middleware('permission:editar-rutas');
    Route::get('/horarios-ruta/{id}/asientos-disponibles', [HorarioRutaController::class, 'asientosDisponibles'])
        ->middleware('permission:ver-rutas');
    Route::get('/horarios-ruta/proximos/disponibles', [HorarioRutaController::class, 'proximosDisponibles'])
        ->middleware('permission:ver-rutas');

    // ------------------------------------------------------------------------
    // MÓDULO: TIQUETES DE TRANSPORTE
    // ------------------------------------------------------------------------
    Route::get('/tiquetes-detalle', [TiqueteDetalleController::class, 'index'])
        ->middleware('permission:ver-rutas');
    Route::get('/tiquetes-detalle/{id}', [TiqueteDetalleController::class, 'show'])
        ->middleware('permission:ver-rutas');
    Route::post('/tiquetes-detalle/{id}/cancelar', [TiqueteDetalleController::class, 'cancelar'])
        ->middleware('permission:editar-rutas');
    Route::post('/tiquetes-detalle/{id}/marcar-usado', [TiqueteDetalleController::class, 'marcarUsado'])
        ->middleware('permission:editar-rutas');
    Route::get('/tiquetes-detalle/horario-ruta/{horarioRutaId}', [TiqueteDetalleController::class, 'porHorarioRuta'])
        ->middleware('permission:ver-rutas');
    Route::get('/tiquetes-detalle/mapa-asientos/{horarioRutaId}', [TiqueteDetalleController::class, 'mapaAsientos'])
        ->middleware('permission:ver-rutas');
});

// ------------------------------------------------------------------------
// MÓDULO: EXPERIENCIA DE RESERVA Y SEATMAP ESTILO TIG (tiguanacaste.com)
// Endpoints públicos y de taquilla para holding time y visualización en tiempo real
// ------------------------------------------------------------------------
Route::prefix("v1/transport")->middleware(["throttle:120,1"])->group(function () {
    Route::get("/trips/{trip}/seatmap", [TransportTripSeatController::class, "seatmap"]);
    Route::post("/trips/{trip}/hold-seat", [TransportTripSeatController::class, "holdSeat"]);
    Route::post("/trips/{trip}/release-seat", [TransportTripSeatController::class, "releaseSeat"]);

    // Autoservicio TIG: Cambia tu viaje y Reserva fallida
    Route::post("/tickets/change-trip", [TransportSelfServiceTicketController::class, "changeTrip"]);
    Route::post("/tickets/verify-failed-booking", [TransportSelfServiceTicketController::class, "verifyFailedBooking"]);
});

// Compatibilidad con rutas autenticadas ERP
Route::middleware(["auth:sanctum", "throttle:120,1"])->group(function () {
    Route::get("/horarios-ruta/{trip}/seatmap", [TransportTripSeatController::class, "seatmap"]);
    Route::post("/horarios-ruta/{trip}/hold-seat", [TransportTripSeatController::class, "holdSeat"]);
    Route::post("/horarios-ruta/{trip}/release-seat", [TransportTripSeatController::class, "releaseSeat"]);

    Route::post("/tiquetes/cambiar-viaje", [TransportSelfServiceTicketController::class, "changeTrip"]);
    Route::post("/tiquetes/verificar-reserva-fallida", [TransportSelfServiceTicketController::class, "verifyFailedBooking"]);
});
