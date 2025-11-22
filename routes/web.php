<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UrlShortenerController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta pública para redirigir URLs acortadas
Route::get('/s/{slug}', [UrlShortenerController::class, 'redirect'])->name('url.redirect');
