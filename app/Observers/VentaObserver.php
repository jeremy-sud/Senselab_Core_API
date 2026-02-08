<?php

/**
 * Observador para eventos del modelo `Venta`.
 *
 * Punto central para agregar lógica reactiva cuando se crean/actualizan
 * o eliminan ventas del sistema (ej. invalidar caché, auditar, notificar).
 * Actualmente los métodos están preparados como hooks vacíos para extender
 * con comportamiento específico del negocio.
 */
namespace App\Observers;

use App\Models\Venta;

class VentaObserver
{
    /**
     * Handle the Venta "created" event.
     */
    public function created(Venta $venta): void
    {
        //
    }

    /**
     * Handle the Venta "updated" event.
     */
    public function updated(Venta $venta): void
    {
        //
    }

    /**
     * Handle the Venta "deleted" event.
     */
    public function deleted(Venta $venta): void
    {
        //
    }

    /**
     * Handle the Venta "restored" event.
     */
    public function restored(Venta $venta): void
    {
        //
    }

    /**
     * Handle the Venta "force deleted" event.
     */
    public function forceDeleted(Venta $venta): void
    {
        //
    }
}
