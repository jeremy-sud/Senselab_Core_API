<?php

use App\Http\Controllers\API\ReservaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('reservas', ReservaController::class);
});
