<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// Importar modelos
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\CuentaBancaria;
use App\Models\DeclaracionTributaria;
use App\Models\Almacen;
use App\Models\Sucursal;
use App\Models\OrdenCompra;
use App\Models\Empleado;
use App\Models\CategoriaProducto;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\MovimientoBancario;
use App\Models\RetencionImpuesto;
use App\Models\CajaChica;
use App\Models\AsientoContable;

// Importar policies
use App\Policies\EmpresaPolicy;
use App\Policies\UsuarioPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\VentaPolicy;
use App\Policies\ClientePolicy;
use App\Policies\ProveedorPolicy;
use App\Policies\CuentaBancariaPolicy;
use App\Policies\DeclaracionTributariaPolicy;
use App\Policies\AlmacenPolicy;
use App\Policies\SucursalPolicy;
use App\Policies\OrdenCompraPolicy;
use App\Policies\EmpleadoPolicy;
use App\Policies\CategoriaProductoPolicy;
use App\Policies\RolPolicy;
use App\Policies\PermisoPolicy;
use App\Policies\CuentaPorCobrarPolicy;
use App\Policies\CuentaPorPagarPolicy;
use App\Policies\MovimientoBancarioPolicy;
use App\Policies\RetencionImpuestoPolicy;
use App\Policies\CajaChicaPolicy;
use App\Policies\AsientoContablePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de modelos a policies
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Empresa::class => EmpresaPolicy::class,
        Usuario::class => UsuarioPolicy::class,
        Producto::class => ProductoPolicy::class,
        Venta::class => VentaPolicy::class,
        Cliente::class => ClientePolicy::class,
        Proveedor::class => ProveedorPolicy::class,
        CuentaBancaria::class => CuentaBancariaPolicy::class,
        DeclaracionTributaria::class => DeclaracionTributariaPolicy::class,
        Almacen::class => AlmacenPolicy::class,
        Sucursal::class => SucursalPolicy::class,
        OrdenCompra::class => OrdenCompraPolicy::class,
        Empleado::class => EmpleadoPolicy::class,
        CategoriaProducto::class => CategoriaProductoPolicy::class,
        Rol::class => RolPolicy::class,
        Permiso::class => PermisoPolicy::class,
        CuentaPorCobrar::class => CuentaPorCobrarPolicy::class,
        CuentaPorPagar::class => CuentaPorPagarPolicy::class,
        MovimientoBancario::class => MovimientoBancarioPolicy::class,
        RetencionImpuesto::class => RetencionImpuestoPolicy::class,
        CajaChica::class => CajaChicaPolicy::class,
        AsientoContable::class => AsientoContablePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
