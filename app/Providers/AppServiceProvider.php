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
use App\Models\BusUnidad;
use App\Models\Cabys;
use App\Models\Cargo;
use App\Models\CodigoActividadEconomica;
use App\Models\ComprobanteRecibidoElectronico;
use App\Models\Configuracion;
use App\Models\CuentaContable;
use App\Models\DeduccionLegal;
use App\Models\DetalleAsiento;
use App\Models\DetalleEntradaInventario;
use App\Models\DetallePresupuesto;
use App\Models\DetalleSalidaInventario;
use App\Models\EntradaInventario;
use App\Models\FormaPago;
use App\Models\HorarioRuta;
use App\Models\LogAccesoSistema;
use App\Models\Marca;
use App\Models\MensajeHacienda;
use App\Models\ModeloBus;
use App\Models\Pago;
use App\Models\PagoNomina;
use App\Models\PeriodoNomina;
use App\Models\PlanillaCcss;
use App\Models\Presupuesto;
use App\Models\Ruta;
use App\Models\SalidaInventario;
use App\Models\TasaImpuesto;
use App\Models\TipoCliente;
use App\Models\TipoComprobanteFe;
use App\Models\TipoCuenta;
use App\Models\TipoImpuesto;
use App\Models\TiqueteDetalle;
use App\Models\UnidadMedida;
use App\Models\UrlShortener;
use App\Models\ZonaGeografica;
use App\Models\InventarioProducto;

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
use App\Policies\BusUnidadPolicy;
use App\Policies\CabysPolicy;
use App\Policies\CargoPolicy;
use App\Policies\CodigoActividadEconomicaPolicy;
use App\Policies\ComprobanteRecibidoElectronicoPolicy;
use App\Policies\ConfiguracionPolicy;
use App\Policies\CuentaContablePolicy;
use App\Policies\DeduccionLegalPolicy;
use App\Policies\DetalleAsientoPolicy;
use App\Policies\DetalleEntradaInventarioPolicy;
use App\Policies\DetallePresupuestoPolicy;
use App\Policies\DetalleSalidaInventarioPolicy;
use App\Policies\EntradaInventarioPolicy;
use App\Policies\FormaPagoPolicy;
use App\Policies\HorarioRutaPolicy;
use App\Policies\LogAccesoSistemaPolicy;
use App\Policies\MarcaPolicy;
use App\Policies\MensajeHaciendaPolicy;
use App\Policies\ModeloBusPolicy;
use App\Policies\PagoPolicy;
use App\Policies\PagoNominaPolicy;
use App\Policies\PeriodoNominaPolicy;
use App\Policies\PlanillaCcssPolicy;
use App\Policies\PresupuestoPolicy;
use App\Policies\RutaPolicy;
use App\Policies\SalidaInventarioPolicy;
use App\Policies\TasaImpuestoPolicy;
use App\Policies\TipoClientePolicy;
use App\Policies\TipoComprobanteFePolicy;
use App\Policies\TipoCuentaPolicy;
use App\Policies\TipoImpuestoPolicy;
use App\Policies\TiqueteDetallePolicy;
use App\Policies\UnidadMedidaPolicy;
use App\Policies\UrlShortenerPolicy;
use App\Policies\ZonaGeograficaPolicy;
use App\Policies\InventarioPolicy;

// Importar observers
use App\Observers\PermisoObserver;
use App\Observers\RolObserver;
use App\Observers\ProductoObserver;
use App\Observers\VentaObserver;
use App\Observers\ClienteObserver;
use App\Observers\AsientoContableObserver;

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
        // Nuevas policies (36 adicionales)
        BusUnidad::class => BusUnidadPolicy::class,
        Cabys::class => CabysPolicy::class,
        Cargo::class => CargoPolicy::class,
        CodigoActividadEconomica::class => CodigoActividadEconomicaPolicy::class,
        ComprobanteRecibidoElectronico::class => ComprobanteRecibidoElectronicoPolicy::class,
        Configuracion::class => ConfiguracionPolicy::class,
        CuentaContable::class => CuentaContablePolicy::class,
        DeduccionLegal::class => DeduccionLegalPolicy::class,
        DetalleAsiento::class => DetalleAsientoPolicy::class,
        DetalleEntradaInventario::class => DetalleEntradaInventarioPolicy::class,
        DetallePresupuesto::class => DetallePresupuestoPolicy::class,
        DetalleSalidaInventario::class => DetalleSalidaInventarioPolicy::class,
        EntradaInventario::class => EntradaInventarioPolicy::class,
        FormaPago::class => FormaPagoPolicy::class,
        HorarioRuta::class => HorarioRutaPolicy::class,
        LogAccesoSistema::class => LogAccesoSistemaPolicy::class,
        Marca::class => MarcaPolicy::class,
        MensajeHacienda::class => MensajeHaciendaPolicy::class,
        ModeloBus::class => ModeloBusPolicy::class,
        Pago::class => PagoPolicy::class,
        PagoNomina::class => PagoNominaPolicy::class,
        PeriodoNomina::class => PeriodoNominaPolicy::class,
        PlanillaCcss::class => PlanillaCcssPolicy::class,
        Presupuesto::class => PresupuestoPolicy::class,
        Ruta::class => RutaPolicy::class,
        SalidaInventario::class => SalidaInventarioPolicy::class,
        TasaImpuesto::class => TasaImpuestoPolicy::class,
        TipoCliente::class => TipoClientePolicy::class,
        TipoComprobanteFe::class => TipoComprobanteFePolicy::class,
        TipoCuenta::class => TipoCuentaPolicy::class,
        TipoImpuesto::class => TipoImpuestoPolicy::class,
        TiqueteDetalle::class => TiqueteDetallePolicy::class,
        UnidadMedida::class => UnidadMedidaPolicy::class,
        UrlShortener::class => UrlShortenerPolicy::class,
        ZonaGeografica::class => ZonaGeograficaPolicy::class,
        InventarioProducto::class => InventarioPolicy::class,
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
        // Registrar observers (Sprint 8.3 - Observer Pattern)
        Permiso::observe(PermisoObserver::class);
        Rol::observe(RolObserver::class);
        Producto::observe(ProductoObserver::class);
        Venta::observe(VentaObserver::class);
        Cliente::observe(ClienteObserver::class);
        AsientoContable::observe(AsientoContableObserver::class);
        
        // Registrar policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
