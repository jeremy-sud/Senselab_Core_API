<?php

/**
 * Observador para `AsientoContable`.
 *
 * Lugar para reaccionar ante la creación/actualización/eliminación de asientos
 * contables (ej. sincronizar libros, emitir alertas, validar integridad).
 */
namespace App\Observers;

use App\Models\AsientoContable;

class AsientoContableObserver
{
    /**
     * Handle the AsientoContable "created" event.
     */
    public function created(AsientoContable $asientoContable): void
    {
        //
    }

    /**
     * Handle the AsientoContable "updated" event.
     */
    public function updated(AsientoContable $asientoContable): void
    {
        //
    }

    /**
     * Handle the AsientoContable "deleted" event.
     */
    public function deleted(AsientoContable $asientoContable): void
    {
        //
    }

    /**
     * Handle the AsientoContable "restored" event.
     */
    public function restored(AsientoContable $asientoContable): void
    {
        //
    }

    /**
     * Handle the AsientoContable "force deleted" event.
     */
    public function forceDeleted(AsientoContable $asientoContable): void
    {
        //
    }
}
