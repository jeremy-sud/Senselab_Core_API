<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Cache\TaggableStore;

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
        $this->flushProductCache();
        
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
        $this->flushProductCache();
        
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
        $this->flushProductCache();
        
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
        $this->flushProductCache();
    }

    /**
     * Handle the Producto "force deleted" event.
     */
    public function forceDeleted(Producto $producto): void
    {
        $this->flushProductCache();
    }

    /**
     * Limpiar cache de productos de forma segura.
     * Cache::tags() solo funciona con drivers que soportan tags (Redis, Memcached).
     */
    private function flushProductCache(): void
    {
        if (Cache::getStore() instanceof TaggableStore) {
            $tenantId = $this->resolveTenantId();
            Cache::tags(["tenant_{$tenantId}:productos", "tenant_{$tenantId}:catalogos"])->flush();
        } else {
            Cache::flush();
        }
    }

    private function resolveTenantId(): int
    {
        if (auth('sanctum')->check()) {
            /** @var \App\Models\Usuario|null $user */
            $user = auth('sanctum')->user();
            return $user->empresa_id ?? 0;
        }

        return 0;
    }
}
