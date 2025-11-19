<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AlmacenController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\VentaController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\EmpresaController;
use App\Http\Controllers\API\ProveedorController;
use App\Http\Controllers\API\SucursalController;
use App\Http\Controllers\API\OrdenCompraController;
use App\Http\Controllers\API\EmpleadoController;
use App\Http\Controllers\API\CategoriaProductoController;
use App\Http\Controllers\API\MarcaController;
use App\Http\Controllers\API\UnidadMedidaController;
use App\Http\Controllers\API\InventarioController;
use App\Http\Controllers\API\RolController;
use App\Http\Controllers\API\PermisoController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\FormaPagoController;
use App\Http\Controllers\API\CargoController;
use App\Http\Controllers\API\CuentaPorCobrarController;
use App\Http\Controllers\API\CuentaPorPagarController;
use App\Http\Controllers\API\CabyController;
use App\Http\Controllers\API\TipoImpuestoController;
use App\Http\Controllers\API\CuentaContableController;
use App\Http\Controllers\API\AsientoContableController;
use App\Http\Controllers\API\DetalleAsientoController;
use App\Http\Controllers\API\TipoCuentaController;
use App\Http\Controllers\API\PagoController;
use App\Http\Controllers\API\TasaImpuestoController;
use App\Http\Controllers\API\PeriodoNominaController;
use App\Http\Controllers\API\PagoNominaController;
use App\Http\Controllers\API\BusUnidadController;
use App\Http\Controllers\API\ModeloBusController;
use App\Http\Controllers\API\RutaController;
use App\Http\Controllers\API\HorarioRutaController;
use App\Http\Controllers\API\TiqueteDetalleController;
use App\Http\Controllers\API\EntradaInventarioController;
use App\Http\Controllers\API\DetalleEntradaInventarioController;
use App\Http\Controllers\API\SalidaInventarioController;
use App\Http\Controllers\API\DetalleSalidaInventarioController;
use App\Http\Controllers\API\ComprobanteRecibidoElectronicoController;
use App\Http\Controllers\API\ConfiguracionController;
use App\Http\Controllers\API\PresupuestoController;
use App\Http\Controllers\API\DetallePresupuestoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Perfil de usuario
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Empresas
    Route::apiResource('empresas', EmpresaController::class);

    // Sucursales
    Route::apiResource('sucursales', SucursalController::class);

    // Almacenes
    Route::apiResource('almacenes', AlmacenController::class);

    // Productos
    Route::apiResource('productos', ProductoController::class);

    // Clientes
    Route::apiResource('clientes', ClienteController::class);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class);

    // Ventas
    Route::apiResource('ventas', VentaController::class);

    // Órdenes de Compra
    Route::apiResource('ordenes-compra', OrdenCompraController::class);

    // Empleados
    Route::apiResource('empleados', EmpleadoController::class);

    // Categorías de Productos
    Route::apiResource('categorias-productos', CategoriaProductoController::class);

    // Marcas
    Route::apiResource('marcas', MarcaController::class);

    // Unidades de Medida
    Route::apiResource('unidades-medida', UnidadMedidaController::class);

    // Inventario - Entradas
    Route::get('/inventario/entradas', [InventarioController::class, 'indexEntradas']);
    Route::post('/inventario/entradas', [InventarioController::class, 'storeEntrada']);
    Route::get('/inventario/entradas/{id}', [InventarioController::class, 'showEntrada']);
    Route::post('/inventario/entradas/{id}/cancelar', [InventarioController::class, 'cancelarEntrada']);

    // Inventario - Salidas
    Route::get('/inventario/salidas', [InventarioController::class, 'indexSalidas']);
    Route::post('/inventario/salidas', [InventarioController::class, 'storeSalida']);
    Route::get('/inventario/salidas/{id}', [InventarioController::class, 'showSalida']);
    Route::post('/inventario/salidas/{id}/cancelar', [InventarioController::class, 'cancelarSalida']);

    // Roles (RBAC)
    Route::apiResource('roles', RolController::class);
    Route::post('/roles/{id}/permisos', [RolController::class, 'asignarPermisos']);

    // Permisos (RBAC)
    Route::apiResource('permisos', PermisoController::class);
    Route::get('/permisos/modulos/list', [PermisoController::class, 'modulos']);

    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);
    Route::post('/usuarios/{id}/roles', [UsuarioController::class, 'asignarRoles']);
    Route::post('/usuarios/{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);

    // Formas de Pago
    Route::apiResource('formas-pago', FormaPagoController::class);

    // Cargos
    Route::apiResource('cargos', CargoController::class);

    // Cuentas por Cobrar
    Route::apiResource('cuentas-por-cobrar', CuentaPorCobrarController::class);
    Route::get('/cuentas-por-cobrar/vencidas/list', [CuentaPorCobrarController::class, 'vencidas']);
    Route::get('/cuentas-por-cobrar/resumen/general', [CuentaPorCobrarController::class, 'resumen']);

    // Cuentas por Pagar
    Route::apiResource('cuentas-por-pagar', CuentaPorPagarController::class);
    Route::get('/cuentas-por-pagar/vencidas/list', [CuentaPorPagarController::class, 'vencidas']);
    Route::get('/cuentas-por-pagar/resumen/general', [CuentaPorPagarController::class, 'resumen']);

    // CAByS (Catálogo de Bienes y Servicios)
    Route::apiResource('cabys', CabyController::class);
    Route::post('/cabys/buscar', [CabyController::class, 'buscar']);

    // Tipos de Impuesto
    Route::apiResource('tipos-impuesto', TipoImpuestoController::class);
    Route::get('/tipos-impuesto/activos/list', [TipoImpuestoController::class, 'activos']);

    // Cuentas Contables
    Route::apiResource('cuentas-contables', CuentaContableController::class);
    Route::get('/cuentas-contables/arbol/jerarquia', [CuentaContableController::class, 'arbol']);
    Route::get('/cuentas-contables/movimientos/list', [CuentaContableController::class, 'paraMovimientos']);

    // Asientos Contables
    Route::apiResource('asientos-contables', AsientoContableController::class);
    Route::post('/asientos-contables/{id}/mayorizar', [AsientoContableController::class, 'mayorizar']);
    Route::get('/asientos-contables/{id}/validar', [AsientoContableController::class, 'validar']);

    // Detalle de Asientos Contables
    Route::get('/detalle-asientos', [DetalleAsientoController::class, 'index']);
    Route::get('/detalle-asientos/{id}', [DetalleAsientoController::class, 'show']);
    Route::get('/detalle-asientos/cuenta/{cuentaContableId}', [DetalleAsientoController::class, 'porCuenta']);
    Route::get('/detalle-asientos/reportes/libro-mayor', [DetalleAsientoController::class, 'libroMayor']);
    Route::get('/detalle-asientos/reportes/balance-comprobacion', [DetalleAsientoController::class, 'balanceComprobacion']);

    // Tipos de Cuentas Contables
    Route::apiResource('tipos-cuentas', TipoCuentaController::class);
    Route::get('/tipos-cuentas/naturaleza/{naturaleza}', [TipoCuentaController::class, 'porNaturaleza']);
    Route::get('/tipos-cuentas/activos/list', [TipoCuentaController::class, 'activos']);

    // Pagos
    Route::apiResource('pagos', PagoController::class);
    Route::get('/pagos/resumen/por-forma-pago', [PagoController::class, 'resumenPorFormaPago']);

    // Tasas de Impuesto
    Route::apiResource('tasas-impuesto', TasaImpuestoController::class);
    Route::get('/tasas-impuesto/vigente/{tipoImpuestoId}', [TasaImpuestoController::class, 'vigente']);
    Route::get('/tasas-impuesto/vigentes-actuales/list', [TasaImpuestoController::class, 'vigentesActuales']);
    Route::get('/tasas-impuesto/historico/{tipoImpuestoId}', [TasaImpuestoController::class, 'historico']);

    // Períodos de Nómina
    Route::apiResource('periodos-nomina', PeriodoNominaController::class);
    Route::post('/periodos-nomina/{id}/cerrar', [PeriodoNominaController::class, 'cerrar']);
    Route::post('/periodos-nomina/{id}/procesar', [PeriodoNominaController::class, 'procesar']);
    Route::get('/periodos-nomina/{id}/resumen', [PeriodoNominaController::class, 'resumen']);
    Route::get('/periodos-nomina/activos/list', [PeriodoNominaController::class, 'activos']);

    // Pagos de Nómina
    Route::apiResource('pagos-nomina', PagoNominaController::class);
    Route::post('/pagos-nomina/{id}/marcar-pagado', [PagoNominaController::class, 'marcarPagado']);
    Route::get('/pagos-nomina/empleado/{empleadoId}', [PagoNominaController::class, 'porEmpleado']);
    Route::get('/pagos-nomina/resumen/por-metodo-pago', [PagoNominaController::class, 'resumenPorMetodoPago']);
    Route::get('/pagos-nomina/totales/por-periodo', [PagoNominaController::class, 'totalesPorPeriodo']);

    // GRUPO C: TRANSPORTE
    // Buses/Unidades de Transporte
    Route::apiResource('buses-unidades', BusUnidadController::class);
    Route::get('/buses-unidades/disponibles/list', [BusUnidadController::class, 'disponibles']);
    Route::get('/buses-unidades/resumen/flota', [BusUnidadController::class, 'resumenFlota']);
    Route::get('/buses-unidades/por-modelo/{modeloId}', [BusUnidadController::class, 'porModelo']);

    // Modelos de Buses
    Route::apiResource('modelos-buses', ModeloBusController::class);
    Route::get('/modelos-buses/activos/list', [ModeloBusController::class, 'activos']);

    // Rutas de Transporte
    Route::apiResource('rutas', RutaController::class);
    Route::get('/rutas/activas/list', [RutaController::class, 'activas']);
    Route::post('/rutas/calcular-tarifa', [RutaController::class, 'calcularTarifa']);
    Route::get('/rutas/{id}/estadisticas', [RutaController::class, 'estadisticas']);

    // Horarios de Ruta (Viajes Programados)
    Route::apiResource('horarios-ruta', HorarioRutaController::class);
    Route::post('/horarios-ruta/{id}/iniciar-viaje', [HorarioRutaController::class, 'iniciarViaje']);
    Route::post('/horarios-ruta/{id}/finalizar-viaje', [HorarioRutaController::class, 'finalizarViaje']);
    Route::post('/horarios-ruta/{id}/cancelar', [HorarioRutaController::class, 'cancelar']);
    Route::get('/horarios-ruta/{id}/asientos-disponibles', [HorarioRutaController::class, 'asientosDisponibles']);
    Route::get('/horarios-ruta/proximos/disponibles', [HorarioRutaController::class, 'proximosDisponibles']);

    // Tiquetes de Transporte
    Route::get('/tiquetes-detalle', [TiqueteDetalleController::class, 'index']);
    Route::get('/tiquetes-detalle/{id}', [TiqueteDetalleController::class, 'show']);
    Route::post('/tiquetes-detalle/{id}/cancelar', [TiqueteDetalleController::class, 'cancelar']);
    Route::post('/tiquetes-detalle/{id}/marcar-usado', [TiqueteDetalleController::class, 'marcarUsado']);
    Route::get('/tiquetes-detalle/horario-ruta/{horarioRutaId}', [TiqueteDetalleController::class, 'porHorarioRuta']);
    Route::get('/tiquetes-detalle/mapa-asientos/{horarioRutaId}', [TiqueteDetalleController::class, 'mapaAsientos']);

    // GRUPO D: GESTIÓN DE INVENTARIO AVANZADO
    // Entradas de Inventario
    Route::apiResource('entradas-inventario', EntradaInventarioController::class);
    Route::post('/entradas-inventario/{id}/procesar', [EntradaInventarioController::class, 'procesar']);
    Route::post('/entradas-inventario/{id}/cancelar', [EntradaInventarioController::class, 'cancelar']);
    Route::get('/entradas-inventario/proveedor/{proveedorId}', [EntradaInventarioController::class, 'porProveedor']);
    Route::get('/entradas-inventario/almacen/{almacenId}', [EntradaInventarioController::class, 'porAlmacen']);
    Route::get('/entradas-inventario/resumen/por-tipo', [EntradaInventarioController::class, 'resumenPorTipo']);
    Route::get('/entradas-inventario/pendientes/list', [EntradaInventarioController::class, 'pendientes']);

    // Detalle de Entradas de Inventario
    Route::get('/entradas-inventario/{entradaId}/detalles', [DetalleEntradaInventarioController::class, 'index']);
    Route::post('/detalles-entradas-inventario', [DetalleEntradaInventarioController::class, 'store']);
    Route::get('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'show']);
    Route::put('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'update']);
    Route::delete('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'destroy']);

    // Salidas de Inventario
    Route::apiResource('salidas-inventario', SalidaInventarioController::class);
    Route::post('/salidas-inventario/{id}/procesar', [SalidaInventarioController::class, 'procesar']);
    Route::post('/salidas-inventario/{id}/cancelar', [SalidaInventarioController::class, 'cancelar']);
    Route::get('/salidas-inventario/cliente/{clienteId}', [SalidaInventarioController::class, 'porCliente']);
    Route::get('/salidas-inventario/almacen/{almacenId}', [SalidaInventarioController::class, 'porAlmacen']);
    Route::get('/salidas-inventario/resumen/por-tipo', [SalidaInventarioController::class, 'resumenPorTipo']);
    Route::get('/salidas-inventario/pendientes/list', [SalidaInventarioController::class, 'pendientes']);

    // Detalle de Salidas de Inventario
    Route::get('/salidas-inventario/{salidaId}/detalles', [DetalleSalidaInventarioController::class, 'index']);
    Route::post('/detalles-salidas-inventario', [DetalleSalidaInventarioController::class, 'store']);
    Route::get('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'show']);
    Route::put('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'update']);
    Route::delete('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'destroy']);

    // GRUPO E: COMPROBANTES ELECTRÓNICOS Y CONFIGURACIONES
    // Comprobantes Electrónicos Recibidos
    Route::apiResource('comprobantes-recibidos-electronicos', ComprobanteRecibidoElectronicoController::class);
    Route::post('/comprobantes-recibidos-electronicos/{id}/confirmar', [ComprobanteRecibidoElectronicoController::class, 'confirmar']);
    Route::post('/comprobantes-recibidos-electronicos/{id}/rechazar', [ComprobanteRecibidoElectronicoController::class, 'rechazar']);
    Route::get('/comprobantes-recibidos-electronicos/proveedor/{proveedorId}', [ComprobanteRecibidoElectronicoController::class, 'porProveedor']);
    Route::get('/comprobantes-recibidos-electronicos/pendientes/list', [ComprobanteRecibidoElectronicoController::class, 'pendientes']);
    Route::get('/comprobantes-recibidos-electronicos/resumen/por-estado', [ComprobanteRecibidoElectronicoController::class, 'resumenPorEstado']);
    Route::put('/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda', [ComprobanteRecibidoElectronicoController::class, 'actualizarRespuestaHacienda']);

    // Configuraciones del Sistema
    Route::apiResource('configuraciones', ConfiguracionController::class);
    Route::get('/configuraciones/clave/{clave}', [ConfiguracionController::class, 'porClave']);
    Route::get('/configuraciones/valor/{clave}', [ConfiguracionController::class, 'obtenerValor']);
    Route::put('/configuraciones/actualizar-multiples', [ConfiguracionController::class, 'actualizarMultiples']);

    // Presupuestos Financieros
    Route::apiResource('presupuestos', PresupuestoController::class);
    Route::post('/presupuestos/{id}/activar', [PresupuestoController::class, 'activar']);
    Route::post('/presupuestos/{id}/finalizar', [PresupuestoController::class, 'finalizar']);
    Route::get('/presupuestos/activos/list', [PresupuestoController::class, 'activos']);
    Route::get('/presupuestos/{id}/resumen', [PresupuestoController::class, 'resumen']);

    // Detalle de Presupuestos
    Route::get('/presupuestos/{presupuestoId}/detalles', [DetallePresupuestoController::class, 'index']);
    Route::post('/detalles-presupuestos', [DetallePresupuestoController::class, 'store']);
    Route::get('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'show']);
    Route::put('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'update']);
    Route::delete('/detalles-presupuestos/{id}', [DetallePresupuestoController::class, 'destroy']);
});