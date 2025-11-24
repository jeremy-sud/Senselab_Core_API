<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CleanCacheJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================================
// Sprint 8.4 - Scheduled Queue Jobs
// ============================================================================

// Limpiar cache expirado cada hora
Schedule::job(new CleanCacheJob('expired'))
    ->hourly()
    ->name('cache:clean-expired')
    ->withoutOverlapping();

// Limpiar sesiones antiguas diariamente a las 3 AM
Schedule::job(new CleanCacheJob('sessions'))
    ->dailyAt('03:00')
    ->name('cache:clean-sessions')
    ->withoutOverlapping();

// Limpiar logs antiguos semanalmente (domingos 2 AM)
Schedule::job(new CleanCacheJob('logs'))
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->name('cache:clean-logs')
    ->withoutOverlapping();

// Limpieza completa de cache mensual (primer día del mes, 4 AM)
Schedule::job(new CleanCacheJob('all'))
    ->monthlyOn(1, '04:00')
    ->name('cache:clean-all')
    ->withoutOverlapping();
