<?php

/**
 * Observador para el modelo `Cliente`.
 *
 * Ideal para añadir comportamiento como limpieza de cache, validaciones
 * adicionales o disparar eventos de integración cuando cambia información
 * crítica del cliente.
 */
namespace App\Observers;

use App\Models\Cliente;

class ClienteObserver
{
    /**
     * Handle the Cliente "created" event.
     */
    public function created(Cliente $cliente): void
    {
        //
    }

    /**
     * Handle the Cliente "updated" event.
     */
    public function updated(Cliente $cliente): void
    {
        //
    }

    /**
     * Handle the Cliente "deleted" event.
     */
    public function deleted(Cliente $cliente): void
    {
        //
    }

    /**
     * Handle the Cliente "restored" event.
     */
    public function restored(Cliente $cliente): void
    {
        //
    }

    /**
     * Handle the Cliente "force deleted" event.
     */
    public function forceDeleted(Cliente $cliente): void
    {
        //
    }
}
