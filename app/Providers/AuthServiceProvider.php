<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Modelos
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
use App\Models\Webhook;
use App\Models\ZonaGeografica;
use App\Models\InventarioProducto;

// Policies
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
use App\Policies\WebhookPolicy;
use App\Policies\ZonaGeograficaPolicy;
use App\Policies\InventarioPolicy;

/**
 * AuthServiceProvider — Registro de Policies RBAC
 *
 * Extraído de AppServiceProvider para separación de responsabilidades.
 * Mapea cada modelo Eloquent a su policy correspondiente.
 *
 * @package App\Providers
 * @version 2.3.0
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de modelos a policies.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Core
        Empresa::class => EmpresaPolicy::class,
        Usuario::class => UsuarioPolicy::class,
        Sucursal::class => SucursalPolicy::class,
        Almacen::class => AlmacenPolicy::class,
        Configuracion::class => ConfiguracionPolicy::class,
        LogAccesoSistema::class => LogAccesoSistemaPolicy::class,
        UrlShortener::class => UrlShortenerPolicy::class,

        // RBAC
        Rol::class => RolPolicy::class,
        Permiso::class => PermisoPolicy::class,

        // Comercial
        Producto::class => ProductoPolicy::class,
        CategoriaProducto::class => CategoriaProductoPolicy::class,
        Marca::class => MarcaPolicy::class,
        Cabys::class => CabysPolicy::class,
        Cliente::class => ClientePolicy::class,
        Proveedor::class => ProveedorPolicy::class,
        TipoCliente::class => TipoClientePolicy::class,
        FormaPago::class => FormaPagoPolicy::class,

        // Ventas y Compras
        Venta::class => VentaPolicy::class,
        OrdenCompra::class => OrdenCompraPolicy::class,
        Pago::class => PagoPolicy::class,

        // Inventario
        EntradaInventario::class => EntradaInventarioPolicy::class,
        SalidaInventario::class => SalidaInventarioPolicy::class,
        DetalleEntradaInventario::class => DetalleEntradaInventarioPolicy::class,
        DetalleSalidaInventario::class => DetalleSalidaInventarioPolicy::class,
        InventarioProducto::class => InventarioPolicy::class,

        // Contabilidad y Finanzas
        AsientoContable::class => AsientoContablePolicy::class,
        DetalleAsiento::class => DetalleAsientoPolicy::class,
        CuentaContable::class => CuentaContablePolicy::class,
        TipoCuenta::class => TipoCuentaPolicy::class,
        CuentaBancaria::class => CuentaBancariaPolicy::class,
        MovimientoBancario::class => MovimientoBancarioPolicy::class,
        CuentaPorCobrar::class => CuentaPorCobrarPolicy::class,
        CuentaPorPagar::class => CuentaPorPagarPolicy::class,
        CajaChica::class => CajaChicaPolicy::class,
        RetencionImpuesto::class => RetencionImpuestoPolicy::class,
        DeclaracionTributaria::class => DeclaracionTributariaPolicy::class,
        TasaImpuesto::class => TasaImpuestoPolicy::class,
        TipoImpuesto::class => TipoImpuestoPolicy::class,
        CodigoActividadEconomica::class => CodigoActividadEconomicaPolicy::class,
        Presupuesto::class => PresupuestoPolicy::class,
        DetallePresupuesto::class => DetallePresupuestoPolicy::class,

        // Nómina
        Empleado::class => EmpleadoPolicy::class,
        Cargo::class => CargoPolicy::class,
        PagoNomina::class => PagoNominaPolicy::class,
        PeriodoNomina::class => PeriodoNominaPolicy::class,
        PlanillaCcss::class => PlanillaCcssPolicy::class,
        DeduccionLegal::class => DeduccionLegalPolicy::class,

        // Facturación Electrónica
        TipoComprobanteFe::class => TipoComprobanteFePolicy::class,
        ComprobanteRecibidoElectronico::class => ComprobanteRecibidoElectronicoPolicy::class,
        MensajeHacienda::class => MensajeHaciendaPolicy::class,

        // Transporte
        BusUnidad::class => BusUnidadPolicy::class,
        ModeloBus::class => ModeloBusPolicy::class,
        Ruta::class => RutaPolicy::class,
        HorarioRuta::class => HorarioRutaPolicy::class,
        TiqueteDetalle::class => TiqueteDetallePolicy::class,
        ZonaGeografica::class => ZonaGeograficaPolicy::class,

        // Webhooks
        Webhook::class => WebhookPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Puente entre el sistema de Gate nativo de Laravel y las tablas RBAC de la base de datos
        Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\Usuario) {
                if (is_string($ability)) {
                    // Mapeo de habilidades inglesas/estándar a slugs de base de datos en español
                    $mappedAbility = match ($ability) {
                        'create-webhooks' => 'crear-webhooks',
                        'update-webhooks' => 'editar-webhooks',
                        'delete-webhooks' => 'eliminar-webhooks',
                        'view-webhooks' => 'ver-webhooks',
                        default => $ability,
                    };
                    if ($user->hasPermission($mappedAbility)) {
                        return true;
                    }
                }
            }
        });
    }
}
