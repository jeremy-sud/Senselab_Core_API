<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer para Producto
 * Sprint 8.3 - Observer Pattern
 * 
 * Maneja eventos del ciclo de vida de Producto:
 * - Limpiar cache cuando se modifica
 * - Log de cambios críticos  
 * - Validaciones adicionales
 */
class ProductoObserver
{
    /**
     * Handle the Producto "created" event.
     */
    public function created(Producto $producto): void
    {
        // Limpiar cache de productos
        Cache::tags(['productos', 'catalogos'])->flush();
        
        // Log de creación
        Log::info('Producto creado', [
            'producto_id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'empresa_id' => $producto->empresa_id
        ]);
    }

    /**
     * Handle the Producto "updated" event.
     */
    public function updated(Producto $producto): void
    {
        // Limpiar cache
        Cache::tags(['productos', 'catalogos'])->flush();
        
        // Log si cambió el precio
        if ($producto->isDirty('precio_venta')) {
            Log::info('Producto: cambio de precio', [
                'producto_id' => $producto->id,
                'precio_anterior' => $producto->getOriginal('precio_venta'),
                'precio_nuevo' => $producto->precio_venta
            ]);
        }
    }

    /**
     * Handle the Producto "deleted" event.
     */
    public function deleted(Producto $producto): void
    {
        // Limpiar cache
        Cache::tags(['productos', 'catalogos'])->flush();
        
        Log::warning('Producto eliminado', [
            'producto_id' => $producto->id,
            'codigo' => $producto->codigo
        ]);
    }

    /**
     * Handle the Producto "restored" event.
     */
    public function restored(Producto $producto): void
    {
        Cache::tags(['productos', 'catalogos'])->flush();
    }

    /**
     * Handle the Producto "force deleted" event.
     */
    public function forceDeleted(Producto $producto): void
    {
        Cache::tags(['productos', 'catalogos'])->flush();
    }
}
