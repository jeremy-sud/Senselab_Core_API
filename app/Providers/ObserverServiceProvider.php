<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Modelos
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\AsientoContable;
use App\Models\Usuario;
use App\Models\CuentaBancaria;
use App\Models\DeclaracionTributaria;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\MovimientoBancario;
use App\Models\RetencionImpuesto;
use App\Models\CajaChica;

// Observers
use App\Observers\PermisoObserver;
use App\Observers\RolObserver;
use App\Observers\ProductoObserver;
use App\Observers\VentaObserver;
use App\Observers\ClienteObserver;
use App\Observers\AsientoContableObserver;
use App\Observers\AuditObserver;

/**
 * ObserverServiceProvider — Registro de Observers de modelo
 *
 * Extraído de AppServiceProvider para separación de responsabilidades.
 * Registra observers dedicados y AuditObserver para modelos críticos.
 *
 * @package App\Providers
 * @version 2.3.0
 */
class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observers dedicados por modelo
        Permiso::observe(PermisoObserver::class);
        Rol::observe(RolObserver::class);
        Producto::observe(ProductoObserver::class);
        Venta::observe(VentaObserver::class);
        Cliente::observe(ClienteObserver::class);
        AsientoContable::observe(AsientoContableObserver::class);

        // FASE 3: Auditoría - AuditObserver en modelos críticos
        Usuario::observe(AuditObserver::class);
        Cliente::observe(AuditObserver::class);
        CuentaBancaria::observe(AuditObserver::class);
        DeclaracionTributaria::observe(AuditObserver::class);
        Permiso::observe(AuditObserver::class);
        CuentaPorCobrar::observe(AuditObserver::class);
        CuentaPorPagar::observe(AuditObserver::class);
        MovimientoBancario::observe(AuditObserver::class);
        RetencionImpuesto::observe(AuditObserver::class);
        CajaChica::observe(AuditObserver::class);
        AsientoContable::observe(AuditObserver::class);
    }
}
