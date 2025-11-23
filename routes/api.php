<?php

use Illuminate\Http\Request;
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
use App\Http\Controllers\API\UrlShortenerController;
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
use App\Http\Controllers\ConsecutivoFEController;
use App\Http\Controllers\TipoCambioHistorialController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\EntidadEtiquetaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajaChicaController;
use App\Http\Controllers\MovimientoCajaChicaController;
use App\Http\Controllers\RegimenTributarioController;
use App\Http\Controllers\RolPermisoController;
use App\Http\Controllers\InventarioProductoController;
use App\Http\Controllers\NominaEmpleadoController;
use App\Http\Controllers\PagoCuentaCobrarController;
use App\Http\Controllers\PagoCuentaPagarController;
use App\Http\Controllers\RolUsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes con Middleware de Permisos RBAC
|--------------------------------------------------------------------------
|
| NOTA IMPORTANTE: Este archivo implementa middleware de permisos en TODAS
| las rutas críticas del API. Cada ruta está protegida según el permiso
| correspondiente en el sistema RBAC.
|
| Estructura de permisos: {modulo}.{accion}
| Acciones: leer, crear, actualizar, eliminar
|
*/

// ============================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================================================

Route::post('/login', [AuthController::class, 'login']);

// ============================================================================
// RUTAS AUTENTICADAS (Requieren auth:sanctum)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ------------------------------------------------------------------------
    // AUTENTICACIÓN Y PERFIL
    // ------------------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/user/permissions', function (Request $request) {
        return response()->json([
            'permissions' => $request->user()->getAllPermissions()
        ]);
    });

    // ------------------------------------------------------------------------
    // MÓDULO: EMPRESAS
    // Permisos: empresas.leer, empresas.crear, empresas.actualizar, empresas.eliminar
    // ------------------------------------------------------------------------
    Route::get('/empresas', [EmpresaController::class, 'index'])
        ->middleware('permission:empresas.leer');
    Route::post('/empresas', [EmpresaController::class, 'store'])
        ->middleware('permission:empresas.crear');
    Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])
        ->middleware('permission:empresas.leer');
    Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])
        ->middleware('permission:empresas.actualizar');
    Route::patch('/empresas/{empresa}', [EmpresaController::class, 'update'])
        ->middleware('permission:empresas.actualizar');
    Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])
        ->middleware('permission:empresas.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: SUCURSALES
    // Permisos: sucursales.leer, sucursales.crear, sucursales.actualizar, sucursales.eliminar
    // ------------------------------------------------------------------------
    Route::get('/sucursales', [SucursalController::class, 'index'])
        ->middleware('permission:sucursales.leer');
    Route::post('/sucursales', [SucursalController::class, 'store'])
        ->middleware('permission:sucursales.crear');
    Route::get('/sucursales/{sucursal}', [SucursalController::class, 'show'])
        ->middleware('permission:sucursales.leer');
    Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])
        ->middleware('permission:sucursales.actualizar');
    Route::patch('/sucursales/{sucursal}', [SucursalController::class, 'update'])
        ->middleware('permission:sucursales.actualizar');
    Route::delete('/sucursales/{sucursal}', [SucursalController::class, 'destroy'])
        ->middleware('permission:sucursales.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: ALMACENES
    // Permisos: almacenes.leer, almacenes.crear, almacenes.actualizar, almacenes.eliminar
    // ------------------------------------------------------------------------
    Route::get('/almacenes', [AlmacenController::class, 'index'])
        ->middleware('permission:almacenes.leer');
    Route::post('/almacenes', [AlmacenController::class, 'store'])
        ->middleware('permission:almacenes.crear');
    Route::get('/almacenes/{almacen}', [AlmacenController::class, 'show'])
        ->middleware('permission:almacenes.leer');
    Route::put('/almacenes/{almacen}', [AlmacenController::class, 'update'])
        ->middleware('permission:almacenes.actualizar');
    Route::patch('/almacenes/{almacen}', [AlmacenController::class, 'update'])
        ->middleware('permission:almacenes.actualizar');
    Route::delete('/almacenes/{almacen}', [AlmacenController::class, 'destroy'])
        ->middleware('permission:almacenes.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: PRODUCTOS
    // Permisos: productos.leer, productos.crear, productos.actualizar, productos.eliminar
    // ------------------------------------------------------------------------
    Route::get('/productos', [ProductoController::class, 'index'])
        ->middleware('permission:productos.leer');
    Route::post('/productos', [ProductoController::class, 'store'])
        ->middleware('permission:productos.crear');
    Route::get('/productos/{producto}', [ProductoController::class, 'show'])
        ->middleware('permission:productos.leer');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permission:productos.actualizar');
    Route::patch('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permission:productos.actualizar');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('permission:productos.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: CLIENTES
    // Permisos: clientes.leer, clientes.crear, clientes.actualizar, clientes.eliminar
    // ------------------------------------------------------------------------
    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permission:clientes.leer');
    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permission:clientes.crear');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])
        ->middleware('permission:clientes.leer');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:clientes.actualizar');
    Route::patch('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:clientes.actualizar');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('permission:clientes.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: PROVEEDORES
    // Permisos: proveedores.leer, proveedores.crear, proveedores.actualizar, proveedores.eliminar
    // ------------------------------------------------------------------------
    Route::get('/proveedores', [ProveedorController::class, 'index'])
        ->middleware('permission:proveedores.leer');
    Route::post('/proveedores', [ProveedorController::class, 'store'])
        ->middleware('permission:proveedores.crear');
    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show'])
        ->middleware('permission:proveedores.leer');
    Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permission:proveedores.actualizar');
    Route::patch('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permission:proveedores.actualizar');
    Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])
        ->middleware('permission:proveedores.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: VENTAS
    // Permisos: ventas.leer, ventas.crear, ventas.actualizar, ventas.eliminar
    // ------------------------------------------------------------------------
    Route::get('/ventas', [VentaController::class, 'index'])
        ->middleware('permission:ventas.leer');
    Route::post('/ventas', [VentaController::class, 'store'])
        ->middleware('permission:ventas.crear');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])
        ->middleware('permission:ventas.leer');
    Route::put('/ventas/{venta}', [VentaController::class, 'update'])
        ->middleware('permission:ventas.actualizar');
    Route::patch('/ventas/{venta}', [VentaController::class, 'update'])
        ->middleware('permission:ventas.actualizar');
    Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])
        ->middleware('permission:ventas.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: COMPRAS (Órdenes de Compra)
    // Permisos: compras.leer, compras.crear, compras.actualizar, compras.eliminar
    // ------------------------------------------------------------------------
    Route::get('/ordenes-compra', [OrdenCompraController::class, 'index'])
        ->middleware('permission:compras.leer');
    Route::post('/ordenes-compra', [OrdenCompraController::class, 'store'])
        ->middleware('permission:compras.crear');
    Route::get('/ordenes-compra/{ordenCompra}', [OrdenCompraController::class, 'show'])
        ->middleware('permission:compras.leer');
    Route::put('/ordenes-compra/{ordenCompra}', [OrdenCompraController::class, 'update'])
        ->middleware('permission:compras.actualizar');
    Route::patch('/ordenes-compra/{ordenCompra}', [OrdenCompraController::class, 'update'])
        ->middleware('permission:compras.actualizar');
    Route::delete('/ordenes-compra/{ordenCompra}', [OrdenCompraController::class, 'destroy'])
        ->middleware('permission:compras.eliminar');

    // ------------------------------------------------------------------------
    // MÓDULO: EMPLEADOS
    // Permisos: empleados.leer, empleados.crear, empleados.actualizar, empleados.eliminar
    // ------------------------------------------------------------------------
    Route::get('/empleados', [EmpleadoController::class, 'index'])
        ->middleware('permission:empleados.leer');
    Route::post('/empleados', [EmpleadoController::class, 'store'])
        ->middleware('permission:empleados.crear');
    Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])
        ->middleware('permission:empleados.leer');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])
        ->middleware('permission:empleados.actualizar');
    Route::patch('/empleados/{empleado}', [EmpleadoController::class, 'update'])
        ->middleware('permission:empleados.actualizar');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])
        ->middleware('permission:empleados.eliminar');

    // ------------------------------------------------------------------------
    // CATÁLOGOS (Acceso general de lectura, solo admin puede modificar)
    // ------------------------------------------------------------------------
    
    // Categorías de Productos
    Route::get('/categorias-productos', [CategoriaProductoController::class, 'index'])
        ->middleware('permission:categorias_producto.leer');
    Route::post('/categorias-productos', [CategoriaProductoController::class, 'store'])
        ->middleware('permission:categorias_producto.crear');
    Route::get('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'show'])
        ->middleware('permission:categorias_producto.leer');
    Route::put('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'update'])
        ->middleware('permission:categorias_producto.actualizar');
    Route::patch('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'update'])
        ->middleware('permission:categorias_producto.actualizar');
    Route::delete('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'destroy'])
        ->middleware('permission:categorias_producto.eliminar');

    // Marcas
    Route::get('/marcas', [MarcaController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/marcas', [MarcaController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/marcas/{marca}', [MarcaController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/marcas/{marca}', [MarcaController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/marcas/{marca}', [MarcaController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/marcas/{marca}', [MarcaController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    
    // Unidades de Medida
    Route::get('/unidades-medida', [UnidadMedidaController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/unidades-medida', [UnidadMedidaController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/unidades-medida/{unidadMedida}', [UnidadMedidaController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/unidades-medida/{unidadMedida}', [UnidadMedidaController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/unidades-medida/{unidadMedida}', [UnidadMedidaController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/unidades-medida/{unidadMedida}', [UnidadMedidaController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    
    // Formas de Pago
    Route::get('/formas-pago', [FormaPagoController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/formas-pago', [FormaPagoController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/formas-pago/{formaPago}', [FormaPagoController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/formas-pago/{formaPago}', [FormaPagoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/formas-pago/{formaPago}', [FormaPagoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/formas-pago/{formaPago}', [FormaPagoController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    
    // Cargos
    Route::get('/cargos', [CargoController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/cargos', [CargoController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/cargos/{cargo}', [CargoController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/cargos/{cargo}', [CargoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/cargos/{cargo}', [CargoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/cargos/{cargo}', [CargoController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    
    // CAByS (Catálogo de Bienes y Servicios)
    Route::get('/cabys', [CabyController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/cabys', [CabyController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/cabys/{caby}', [CabyController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/cabys/{caby}', [CabyController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/cabys/{caby}', [CabyController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/cabys/{caby}', [CabyController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    Route::post('/cabys/buscar', [CabyController::class, 'buscar'])
        ->middleware('permission:catalogos.leer');
    
    // Tipos de Impuesto
    Route::get('/tipos-impuesto', [TipoImpuestoController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/tipos-impuesto', [TipoImpuestoController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/tipos-impuesto/{tipoImpuesto}', [TipoImpuestoController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    Route::get('/tipos-impuesto/activos/list', [TipoImpuestoController::class, 'activos'])
        ->middleware('permission:catalogos.leer');
    
    // Tasas de Impuesto
    Route::get('/tasas-impuesto', [TasaImpuestoController::class, 'index'])
        ->middleware('permission:catalogos.leer');
    Route::post('/tasas-impuesto', [TasaImpuestoController::class, 'store'])
        ->middleware('permission:catalogos.crear');
    Route::get('/tasas-impuesto/{tasaImpuesto}', [TasaImpuestoController::class, 'show'])
        ->middleware('permission:catalogos.leer');
    Route::put('/tasas-impuesto/{tasaImpuesto}', [TasaImpuestoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::patch('/tasas-impuesto/{tasaImpuesto}', [TasaImpuestoController::class, 'update'])
        ->middleware('permission:catalogos.actualizar');
    Route::delete('/tasas-impuesto/{tasaImpuesto}', [TasaImpuestoController::class, 'destroy'])
        ->middleware('permission:catalogos.eliminar');
    Route::get('/tasas-impuesto/vigente/{tipoImpuestoId}', [TasaImpuestoController::class, 'vigente'])
        ->middleware('permission:catalogos.leer');
    Route::get('/tasas-impuesto/vigentes-actuales/list', [TasaImpuestoController::class, 'vigentesActuales'])
        ->middleware('permission:catalogos.leer');
    Route::get('/tasas-impuesto/historico/{tipoImpuestoId}', [TasaImpuestoController::class, 'historico'])
        ->middleware('permission:catalogos.leer');
    
    // Regímenes Tributarios (Catálogo DGT)
    Route::apiResource('regimenes-tributarios', RegimenTributarioController::class)
        ->parameters(['regimenes-tributarios' => 'regimenTributario']);
    Route::get('/regimenes-tributarios/todos/list', [RegimenTributarioController::class, 'todos']);
    Route::get('/regimenes-tributarios/codigo/{codigo}', [RegimenTributarioController::class, 'porCodigo']);

    // ------------------------------------------------------------------------
    // MÓDULO: INVENTARIO
    // Permisos: inventario.leer, inventario.crear, inventario.actualizar, inventario.eliminar
    // ------------------------------------------------------------------------
    
    // Inventario - Entradas (InventarioController - Legacy)
    Route::get('/inventario/entradas', [InventarioController::class, 'indexEntradas'])
        ->middleware('permission:inventario.leer');
    Route::post('/inventario/entradas', [InventarioController::class, 'storeEntrada'])
        ->middleware('permission:inventario.crear');
    Route::get('/inventario/entradas/{id}', [InventarioController::class, 'showEntrada'])
        ->middleware('permission:inventario.leer');
    Route::post('/inventario/entradas/{id}/cancelar', [InventarioController::class, 'cancelarEntrada'])
        ->middleware('permission:inventario.actualizar');

    // Inventario - Salidas (InventarioController - Legacy)
    Route::get('/inventario/salidas', [InventarioController::class, 'indexSalidas'])
        ->middleware('permission:inventario.leer');
    Route::post('/inventario/salidas', [InventarioController::class, 'storeSalida'])
        ->middleware('permission:inventario.crear');
    Route::get('/inventario/salidas/{id}', [InventarioController::class, 'showSalida'])
        ->middleware('permission:inventario.leer');
    Route::post('/inventario/salidas/{id}/cancelar', [InventarioController::class, 'cancelarSalida'])
        ->middleware('permission:inventario.actualizar');

    // Entradas de Inventario (Nuevo sistema)
    Route::apiResource('entradas-inventario', EntradaInventarioController::class)
        ->middleware(['permission:inventario.leer,inventario.crear,inventario.actualizar,inventario.eliminar']);
    Route::post('/entradas-inventario/{id}/procesar', [EntradaInventarioController::class, 'procesar'])
        ->middleware('permission:inventario.actualizar');
    Route::post('/entradas-inventario/{id}/cancelar', [EntradaInventarioController::class, 'cancelar'])
        ->middleware('permission:inventario.actualizar');
    Route::get('/entradas-inventario/proveedor/{proveedorId}', [EntradaInventarioController::class, 'porProveedor'])
        ->middleware('permission:inventario.leer');
    Route::get('/entradas-inventario/almacen/{almacenId}', [EntradaInventarioController::class, 'porAlmacen'])
        ->middleware('permission:inventario.leer');
    Route::get('/entradas-inventario/resumen/por-tipo', [EntradaInventarioController::class, 'resumenPorTipo'])
        ->middleware('permission:inventario.leer');
    Route::get('/entradas-inventario/pendientes/list', [EntradaInventarioController::class, 'pendientes'])
        ->middleware('permission:inventario.leer');

    // Detalle de Entradas de Inventario
    Route::get('/entradas-inventario/{entradaId}/detalles', [DetalleEntradaInventarioController::class, 'index'])
        ->middleware('permission:inventario.leer');
    Route::post('/detalles-entradas-inventario', [DetalleEntradaInventarioController::class, 'store'])
        ->middleware('permission:inventario.crear');
    Route::get('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'show'])
        ->middleware('permission:inventario.leer');
    Route::put('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'update'])
        ->middleware('permission:inventario.actualizar');
    Route::delete('/detalles-entradas-inventario/{id}', [DetalleEntradaInventarioController::class, 'destroy'])
        ->middleware('permission:inventario.eliminar');

    // Salidas de Inventario (Nuevo sistema)
    Route::apiResource('salidas-inventario', SalidaInventarioController::class)
        ->middleware(['permission:inventario.leer,inventario.crear,inventario.actualizar,inventario.eliminar']);
    Route::post('/salidas-inventario/{id}/procesar', [SalidaInventarioController::class, 'procesar'])
        ->middleware('permission:inventario.actualizar');
    Route::post('/salidas-inventario/{id}/cancelar', [SalidaInventarioController::class, 'cancelar'])
        ->middleware('permission:inventario.actualizar');
    Route::get('/salidas-inventario/cliente/{clienteId}', [SalidaInventarioController::class, 'porCliente'])
        ->middleware('permission:inventario.leer');
    Route::get('/salidas-inventario/almacen/{almacenId}', [SalidaInventarioController::class, 'porAlmacen'])
        ->middleware('permission:inventario.leer');
    Route::get('/salidas-inventario/resumen/por-tipo', [SalidaInventarioController::class, 'resumenPorTipo'])
        ->middleware('permission:inventario.leer');
    Route::get('/salidas-inventario/pendientes/list', [SalidaInventarioController::class, 'pendientes'])
        ->middleware('permission:inventario.leer');

    // Detalle de Salidas de Inventario
    Route::get('/salidas-inventario/{salidaId}/detalles', [DetalleSalidaInventarioController::class, 'index'])
        ->middleware('permission:inventario.leer');
    Route::post('/detalles-salidas-inventario', [DetalleSalidaInventarioController::class, 'store'])
        ->middleware('permission:inventario.crear');
    Route::get('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'show'])
        ->middleware('permission:inventario.leer');
    Route::put('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'update'])
        ->middleware('permission:inventario.actualizar');
    Route::delete('/detalles-salidas-inventario/{id}', [DetalleSalidaInventarioController::class, 'destroy'])
        ->middleware('permission:inventario.eliminar');

    // Inventario de Productos (Stock por Almacén)
    Route::apiResource('inventario-productos', InventarioProductoController::class)
        ->parameters(['inventario-productos' => 'inventarioProducto'])
        ->middleware(['permission:inventario.leer,inventario.crear,inventario.actualizar,inventario.eliminar']);
    Route::get('/inventario-productos/almacen/{almacenId}', [InventarioProductoController::class, 'porAlmacen'])
        ->middleware('permission:inventario.leer');
    Route::get('/inventario-productos/alertas/bajo-stock', [InventarioProductoController::class, 'bajoStockMinimo'])
        ->middleware('permission:inventario.leer');
    Route::get('/inventario-productos/alertas/sobre-stock', [InventarioProductoController::class, 'sobreStockMaximo'])
        ->middleware('permission:inventario.leer');
    Route::get('/inventario-productos/resumen/por-almacen', [InventarioProductoController::class, 'resumenPorAlmacen'])
        ->middleware('permission:inventario.leer');

    // ------------------------------------------------------------------------
    // MÓDULO: CUENTAS CONTABLES
    // Permisos: cuentas_contables.leer, cuentas_contables.crear, cuentas_contables.actualizar, cuentas_contables.eliminar
    // ------------------------------------------------------------------------
    Route::apiResource('cuentas-contables', CuentaContableController::class)
        ->middleware(['permission:cuentas_contables.leer,cuentas_contables.crear,cuentas_contables.actualizar,cuentas_contables.eliminar']);
    Route::get('/cuentas-contables/arbol/jerarquia', [CuentaContableController::class, 'arbol'])
        ->middleware('permission:cuentas_contables.leer');
    Route::get('/cuentas-contables/movimientos/list', [CuentaContableController::class, 'paraMovimientos'])
        ->middleware('permission:cuentas_contables.leer');

    // Tipos de Cuentas Contables
    Route::apiResource('tipos-cuentas', TipoCuentaController::class);
    Route::get('/tipos-cuentas/naturaleza/{naturaleza}', [TipoCuentaController::class, 'porNaturaleza']);
    Route::get('/tipos-cuentas/activos/list', [TipoCuentaController::class, 'activos']);

    // ------------------------------------------------------------------------
    // MÓDULO: ASIENTOS CONTABLES
    // Permisos: asientos_contables.leer, asientos_contables.crear, asientos_contables.actualizar, asientos_contables.eliminar
    // ------------------------------------------------------------------------
    Route::apiResource('asientos-contables', AsientoContableController::class)
        ->middleware(['permission:asientos_contables.leer,asientos_contables.crear,asientos_contables.actualizar,asientos_contables.eliminar']);
    Route::post('/asientos-contables/{id}/mayorizar', [AsientoContableController::class, 'mayorizar'])
        ->middleware('permission:asientos_contables.actualizar');
    Route::get('/asientos-contables/{id}/validar', [AsientoContableController::class, 'validar'])
        ->middleware('permission:asientos_contables.leer');

    // Detalle de Asientos Contables
    Route::get('/detalle-asientos', [DetalleAsientoController::class, 'index'])
        ->middleware('permission:asientos_contables.leer');
    Route::get('/detalle-asientos/{id}', [DetalleAsientoController::class, 'show'])
        ->middleware('permission:asientos_contables.leer');
    Route::get('/detalle-asientos/cuenta/{cuentaContableId}', [DetalleAsientoController::class, 'porCuenta'])
        ->middleware('permission:asientos_contables.leer');
    Route::get('/detalle-asientos/reportes/libro-mayor', [DetalleAsientoController::class, 'libroMayor'])
        ->middleware('permission:asientos_contables.leer');
    Route::get('/detalle-asientos/reportes/balance-comprobacion', [DetalleAsientoController::class, 'balanceComprobacion'])
        ->middleware('permission:asientos_contables.leer');

    // ------------------------------------------------------------------------
    // FINANZAS
    // ------------------------------------------------------------------------
    
    // Cuentas por Cobrar
    Route::apiResource('cuentas-por-cobrar', CuentaPorCobrarController::class);
    Route::get('/cuentas-por-cobrar/vencidas/list', [CuentaPorCobrarController::class, 'vencidas']);
    Route::get('/cuentas-por-cobrar/resumen/general', [CuentaPorCobrarController::class, 'resumen']);

    // Cuentas por Pagar
    Route::apiResource('cuentas-por-pagar', CuentaPorPagarController::class);
    Route::get('/cuentas-por-pagar/vencidas/list', [CuentaPorPagarController::class, 'vencidas']);
    Route::get('/cuentas-por-pagar/resumen/general', [CuentaPorPagarController::class, 'resumen']);

    // Pagos
    Route::apiResource('pagos', PagoController::class);
    Route::get('/pagos/resumen/por-forma-pago', [PagoController::class, 'resumenPorFormaPago']);

    // Pagos de Cuentas por Cobrar
    Route::apiResource('pagos-cuentas-cobrar', PagoCuentaCobrarController::class)
        ->parameters(['pagos-cuentas-cobrar' => 'pagoCuentaCobrar']);
    Route::get('/pagos-cuentas-cobrar/cuenta/{cuentaId}', [PagoCuentaCobrarController::class, 'porCuenta']);
    Route::get('/pagos-cuentas-cobrar/forma-pago/{formaPagoId}', [PagoCuentaCobrarController::class, 'porFormaPago']);
    Route::get('/pagos-cuentas-cobrar/resumen/por-fecha', [PagoCuentaCobrarController::class, 'resumenPorFecha']);

    // Pagos de Cuentas por Pagar
    Route::apiResource('pagos-cuentas-pagar', PagoCuentaPagarController::class)
        ->parameters(['pagos-cuentas-pagar' => 'pagoCuentaPagar']);
    Route::get('/pagos-cuentas-pagar/cuenta/{cuentaId}', [PagoCuentaPagarController::class, 'porCuenta']);
    Route::get('/pagos-cuentas-pagar/forma-pago/{formaPagoId}', [PagoCuentaPagarController::class, 'porFormaPago']);
    Route::get('/pagos-cuentas-pagar/resumen/por-fecha', [PagoCuentaPagarController::class, 'resumenPorFecha']);

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

    // ------------------------------------------------------------------------
    // MÓDULO: NÓMINA
    // Permisos: nomina.leer, nomina.crear, nomina.actualizar, nomina.eliminar
    // ------------------------------------------------------------------------
    
    // Períodos de Nómina
    Route::apiResource('periodos-nomina', PeriodoNominaController::class)
        ->middleware(['permission:nomina.leer,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::post('/periodos-nomina/{id}/cerrar', [PeriodoNominaController::class, 'cerrar'])
        ->middleware('permission:nomina.actualizar');
    Route::post('/periodos-nomina/{id}/procesar', [PeriodoNominaController::class, 'procesar'])
        ->middleware('permission:nomina.actualizar');
    Route::get('/periodos-nomina/{id}/resumen', [PeriodoNominaController::class, 'resumen'])
        ->middleware('permission:nomina.leer');
    Route::get('/periodos-nomina/activos/list', [PeriodoNominaController::class, 'activos'])
        ->middleware('permission:nomina.leer');

    // Pagos de Nómina
    Route::apiResource('pagos-nomina', PagoNominaController::class)
        ->middleware(['permission:nomina.leer,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::post('/pagos-nomina/{id}/marcar-pagado', [PagoNominaController::class, 'marcarPagado'])
        ->middleware('permission:nomina.actualizar');
    Route::get('/pagos-nomina/empleado/{empleadoId}', [PagoNominaController::class, 'porEmpleado'])
        ->middleware('permission:nomina.leer');
    Route::get('/pagos-nomina/resumen/por-metodo-pago', [PagoNominaController::class, 'resumenPorMetodoPago'])
        ->middleware('permission:nomina.leer');
    Route::get('/pagos-nomina/totales/por-periodo', [PagoNominaController::class, 'totalesPorPeriodo'])
        ->middleware('permission:nomina.leer');

    // Nómina de Empleados
    Route::apiResource('nomina-empleados', NominaEmpleadoController::class)
        ->parameters(['nomina-empleados' => 'nominaEmpleado'])
        ->middleware(['permission:nomina.leer,nomina.crear,nomina.actualizar,nomina.eliminar']);
    Route::get('/nomina-empleados/periodo/{periodoId}', [NominaEmpleadoController::class, 'porPeriodo'])
        ->middleware('permission:nomina.leer');
    Route::get('/nomina-empleados/empleado/{empleadoId}', [NominaEmpleadoController::class, 'porEmpleado'])
        ->middleware('permission:nomina.leer');
    Route::get('/nomina-empleados/resumen/periodo/{periodoId}', [NominaEmpleadoController::class, 'resumenPorPeriodo'])
        ->middleware('permission:nomina.leer');

    // ------------------------------------------------------------------------
    // MÓDULO: TRANSPORTE (RUTAS, BUSES, TIQUETES)
    // Permisos: rutas.leer, rutas.crear, rutas.actualizar, rutas.eliminar
    // Permisos: buses.leer, buses.crear, buses.actualizar, buses.eliminar
    // ------------------------------------------------------------------------
    
    // Buses/Unidades de Transporte
    Route::apiResource('buses-unidades', BusUnidadController::class)
        ->middleware(['permission:buses.leer,buses.crear,buses.actualizar,buses.eliminar']);
    Route::get('/buses-unidades/disponibles/list', [BusUnidadController::class, 'disponibles'])
        ->middleware('permission:buses.leer');
    Route::get('/buses-unidades/resumen/flota', [BusUnidadController::class, 'resumenFlota'])
        ->middleware('permission:buses.leer');
    Route::get('/buses-unidades/por-modelo/{modeloId}', [BusUnidadController::class, 'porModelo'])
        ->middleware('permission:buses.leer');

    // Modelos de Buses
    Route::apiResource('modelos-buses', ModeloBusController::class);

    // Rutas de Transporte
    Route::apiResource('rutas', RutaController::class)
        ->middleware(['permission:rutas.leer,rutas.crear,rutas.actualizar,rutas.eliminar']);
    Route::get('/rutas/activas/list', [RutaController::class, 'activas'])
        ->middleware('permission:rutas.leer');
    Route::post('/rutas/calcular-tarifa', [RutaController::class, 'calcularTarifa'])
        ->middleware('permission:rutas.leer');
    Route::get('/rutas/{id}/estadisticas', [RutaController::class, 'estadisticas'])
        ->middleware('permission:rutas.leer');

    // Horarios de Ruta (Viajes Programados)
    Route::apiResource('horarios-ruta', HorarioRutaController::class)
        ->middleware(['permission:rutas.leer,rutas.crear,rutas.actualizar,rutas.eliminar']);
    Route::post('/horarios-ruta/{id}/iniciar-viaje', [HorarioRutaController::class, 'iniciarViaje'])
        ->middleware('permission:rutas.actualizar');
    Route::post('/horarios-ruta/{id}/finalizar-viaje', [HorarioRutaController::class, 'finalizarViaje'])
        ->middleware('permission:rutas.actualizar');
    Route::post('/horarios-ruta/{id}/cancelar', [HorarioRutaController::class, 'cancelar'])
        ->middleware('permission:rutas.actualizar');
    Route::get('/horarios-ruta/{id}/asientos-disponibles', [HorarioRutaController::class, 'asientosDisponibles'])
        ->middleware('permission:rutas.leer');
    Route::get('/horarios-ruta/proximos/disponibles', [HorarioRutaController::class, 'proximosDisponibles'])
        ->middleware('permission:rutas.leer');

    // Tiquetes de Transporte
    Route::get('/tiquetes-detalle', [TiqueteDetalleController::class, 'index'])
        ->middleware('permission:rutas.leer');
    Route::get('/tiquetes-detalle/{id}', [TiqueteDetalleController::class, 'show'])
        ->middleware('permission:rutas.leer');
    Route::post('/tiquetes-detalle/{id}/cancelar', [TiqueteDetalleController::class, 'cancelar'])
        ->middleware('permission:rutas.actualizar');
    Route::post('/tiquetes-detalle/{id}/marcar-usado', [TiqueteDetalleController::class, 'marcarUsado'])
        ->middleware('permission:rutas.actualizar');
    Route::get('/tiquetes-detalle/horario-ruta/{horarioRutaId}', [TiqueteDetalleController::class, 'porHorarioRuta'])
        ->middleware('permission:rutas.leer');
    Route::get('/tiquetes-detalle/mapa-asientos/{horarioRutaId}', [TiqueteDetalleController::class, 'mapaAsientos'])
        ->middleware('permission:rutas.leer');

    // ------------------------------------------------------------------------
    // MÓDULO: FACTURACIÓN ELECTRÓNICA
    // Permisos: facturacion_electronica.leer, facturacion_electronica.crear, 
    //           facturacion_electronica.actualizar, facturacion_electronica.eliminar
    // ------------------------------------------------------------------------
    
    // Comprobantes Electrónicos Recibidos
    Route::apiResource('comprobantes-recibidos-electronicos', ComprobanteRecibidoElectronicoController::class)
        ->parameters(['comprobantes-recibidos-electronicos' => 'comprobante'])
        ->middleware(['permission:facturacion_electronica.leer,facturacion_electronica.crear,facturacion_electronica.actualizar,facturacion_electronica.eliminar']);
    Route::post('/comprobantes-recibidos-electronicos/{id}/confirmar', [ComprobanteRecibidoElectronicoController::class, 'confirmar'])
        ->middleware('permission:facturacion_electronica.actualizar');
    Route::post('/comprobantes-recibidos-electronicos/{id}/rechazar', [ComprobanteRecibidoElectronicoController::class, 'rechazar'])
        ->middleware('permission:facturacion_electronica.actualizar');
    Route::get('/comprobantes-recibidos-electronicos/proveedor/{proveedorId}', [ComprobanteRecibidoElectronicoController::class, 'porProveedor'])
        ->middleware('permission:facturacion_electronica.leer');
    Route::get('/comprobantes-recibidos-electronicos/pendientes/list', [ComprobanteRecibidoElectronicoController::class, 'pendientes'])
        ->middleware('permission:facturacion_electronica.leer');
    Route::get('/comprobantes-recibidos-electronicos/resumen/por-estado', [ComprobanteRecibidoElectronicoController::class, 'resumenPorEstado'])
        ->middleware('permission:facturacion_electronica.leer');
    Route::put('/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda', [ComprobanteRecibidoElectronicoController::class, 'actualizarRespuestaHacienda'])
        ->middleware('permission:facturacion_electronica.actualizar');

    // Consecutivos de Facturación Electrónica
    Route::apiResource('consecutivos-fe', ConsecutivoFEController::class)
        ->parameters(['consecutivos-fe' => 'consecutivoFe'])
        ->middleware(['permission:facturacion_electronica.leer,facturacion_electronica.crear,facturacion_electronica.actualizar,facturacion_electronica.eliminar']);
    Route::post('/consecutivos-fe/obtener-siguiente', [ConsecutivoFEController::class, 'obtenerSiguiente'])
        ->middleware('permission:facturacion_electronica.leer');
    Route::post('/consecutivos-fe/{consecutivoFe}/resetear', [ConsecutivoFEController::class, 'resetear'])
        ->middleware('permission:facturacion_electronica.actualizar');
    Route::get('/consecutivos-fe/tipo/{tipoDocumentoDgt}', [ConsecutivoFEController::class, 'porTipoDocumento'])
        ->middleware('permission:facturacion_electronica.leer');
    Route::post('/consecutivos-fe/{consecutivoFe}/marcar-agotado', [ConsecutivoFEController::class, 'marcarAgotado'])
        ->middleware('permission:facturacion_electronica.actualizar');
    Route::post('/consecutivos-fe/{consecutivoFe}/activar', [ConsecutivoFEController::class, 'activar'])
        ->middleware('permission:facturacion_electronica.actualizar');
    Route::get('/consecutivos-fe/resumen/por-estado', [ConsecutivoFEController::class, 'resumenPorEstado'])
        ->middleware('permission:facturacion_electronica.leer');

    // ------------------------------------------------------------------------
    // OTROS MÓDULOS - UTILIDADES
    // ------------------------------------------------------------------------
    
    // Configuraciones del Sistema (Solo Admin)
    Route::get('/configuraciones', [ConfiguracionController::class, 'index'])
        ->middleware('permission:configuraciones.leer');
    Route::post('/configuraciones', [ConfiguracionController::class, 'store'])
        ->middleware('permission:configuraciones.crear');
    Route::get('/configuraciones/{configuracion}', [ConfiguracionController::class, 'show'])
        ->middleware('permission:configuraciones.leer');
    Route::put('/configuraciones/{configuracion}', [ConfiguracionController::class, 'update'])
        ->middleware('permission:configuraciones.actualizar');
    Route::patch('/configuraciones/{configuracion}', [ConfiguracionController::class, 'update'])
        ->middleware('permission:configuraciones.actualizar');
    Route::delete('/configuraciones/{configuracion}', [ConfiguracionController::class, 'destroy'])
        ->middleware('permission:configuraciones.eliminar');
    Route::get('/configuraciones/clave/{clave}', [ConfiguracionController::class, 'porClave'])
        ->middleware('permission:configuraciones.leer');
    Route::get('/configuraciones/valor/{clave}', [ConfiguracionController::class, 'obtenerValor'])
        ->middleware('permission:configuraciones.leer');
    Route::put('/configuraciones/actualizar-multiples', [ConfiguracionController::class, 'actualizarMultiples'])
        ->middleware('permission:configuraciones.actualizar');

    // Tipos de Cambio - Historial
    Route::get('/tipos-cambio-historial', [TipoCambioHistorialController::class, 'index'])
        ->middleware('permission:tipos_cambio.leer');
    Route::post('/tipos-cambio-historial', [TipoCambioHistorialController::class, 'store'])
        ->middleware('permission:tipos_cambio.crear');
    Route::get('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'show'])
        ->middleware('permission:tipos_cambio.leer');
    Route::put('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'update'])
        ->middleware('permission:tipos_cambio.actualizar');
    Route::patch('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'update'])
        ->middleware('permission:tipos_cambio.actualizar');
    Route::delete('/tipos-cambio-historial/{tipoCambioHistorial}', [TipoCambioHistorialController::class, 'destroy'])
        ->middleware('permission:tipos_cambio.eliminar');
    Route::get('/tipos-cambio/vigente', [TipoCambioHistorialController::class, 'vigente'])
        ->middleware('permission:tipos_cambio.leer');
    Route::post('/tipos-cambio/convertir', [TipoCambioHistorialController::class, 'convertir'])
        ->middleware('permission:tipos_cambio.leer');
    Route::get('/tipos-cambio/moneda', [TipoCambioHistorialController::class, 'porMoneda'])
        ->middleware('permission:tipos_cambio.leer');
    Route::get('/tipos-cambio/fecha/{fecha}', [TipoCambioHistorialController::class, 'porFecha'])
        ->middleware('permission:tipos_cambio.leer');
    Route::get('/tipos-cambio/tendencia', [TipoCambioHistorialController::class, 'tendencia'])
        ->middleware('permission:tipos_cambio.leer');

    // Etiquetas (Sistema de Tags)
    Route::get('/etiquetas', [EtiquetaController::class, 'index'])
        ->middleware('permission:etiquetas.leer');
    Route::post('/etiquetas', [EtiquetaController::class, 'store'])
        ->middleware('permission:etiquetas.crear');
    Route::get('/etiquetas/{etiqueta}', [EtiquetaController::class, 'show'])
        ->middleware('permission:etiquetas.leer');
    Route::put('/etiquetas/{etiqueta}', [EtiquetaController::class, 'update'])
        ->middleware('permission:etiquetas.actualizar');
    Route::patch('/etiquetas/{etiqueta}', [EtiquetaController::class, 'update'])
        ->middleware('permission:etiquetas.actualizar');
    Route::delete('/etiquetas/{etiqueta}', [EtiquetaController::class, 'destroy'])
        ->middleware('permission:etiquetas.eliminar');
    Route::get('/etiquetas/todas/list', [EtiquetaController::class, 'todas'])
        ->middleware('permission:etiquetas.leer');
    Route::get('/etiquetas/estadisticas/uso', [EtiquetaController::class, 'estadisticas'])
        ->middleware('permission:etiquetas.leer');
    Route::get('/etiquetas/buscar', [EtiquetaController::class, 'buscar'])
        ->middleware('permission:etiquetas.leer');

    // Entidades-Etiquetas (Relación Polimórfica)
    Route::get('/entidad-etiquetas', [EntidadEtiquetaController::class, 'index'])
        ->middleware('permission:etiquetas.leer');
    Route::post('/entidad-etiquetas', [EntidadEtiquetaController::class, 'store'])
        ->middleware('permission:etiquetas.crear');
    Route::get('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'show'])
        ->middleware('permission:etiquetas.leer');
    Route::put('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'update'])
        ->middleware('permission:etiquetas.actualizar');
    Route::patch('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'update'])
        ->middleware('permission:etiquetas.actualizar');
    Route::delete('/entidad-etiquetas/{entidadEtiqueta}', [EntidadEtiquetaController::class, 'destroy'])
        ->middleware('permission:etiquetas.eliminar');
    Route::post('/entidad-etiquetas/asignar-multiples', [EntidadEtiquetaController::class, 'asignarMultiples'])
        ->middleware('permission:etiquetas.crear');
    Route::post('/entidad-etiquetas/remover-multiples', [EntidadEtiquetaController::class, 'removerMultiples'])
        ->middleware('permission:etiquetas.eliminar');
    Route::get('/entidad-etiquetas/por-entidad', [EntidadEtiquetaController::class, 'porEntidad'])
        ->middleware('permission:etiquetas.leer');
    Route::get('/entidad-etiquetas/por-etiqueta/{etiquetaId}', [EntidadEtiquetaController::class, 'porEtiqueta'])
        ->middleware('permission:etiquetas.leer');
    Route::post('/entidad-etiquetas/sincronizar', [EntidadEtiquetaController::class, 'sincronizar'])
        ->middleware('permission:etiquetas.actualizar');

    // Cajas Registradoras
    Route::get('/cajas', [CajaController::class, 'index'])
        ->middleware('permission:cajas.leer');
    Route::post('/cajas', [CajaController::class, 'store'])
        ->middleware('permission:cajas.crear');
    Route::get('/cajas/{caja}', [CajaController::class, 'show'])
        ->middleware('permission:cajas.leer');
    Route::put('/cajas/{caja}', [CajaController::class, 'update'])
        ->middleware('permission:cajas.actualizar');
    Route::patch('/cajas/{caja}', [CajaController::class, 'update'])
        ->middleware('permission:cajas.actualizar');
    Route::delete('/cajas/{caja}', [CajaController::class, 'destroy'])
        ->middleware('permission:cajas.eliminar');
    Route::get('/cajas/sucursal/{sucursalId}', [CajaController::class, 'porSucursal'])
        ->middleware('permission:cajas.leer');
    Route::get('/cajas/activas/list', [CajaController::class, 'activas'])
        ->middleware('permission:cajas.leer');
    Route::post('/cajas/{caja}/toggle-activo', [CajaController::class, 'toggleActivo'])
        ->middleware('permission:cajas.actualizar');

    // Caja Chica (Fondo de Caja Menor)
    Route::get('/caja-chica', [CajaChicaController::class, 'index'])
        ->middleware('permission:caja_chica.leer');
    Route::post('/caja-chica', [CajaChicaController::class, 'store'])
        ->middleware('permission:caja_chica.crear');
    Route::get('/caja-chica/{cajaChica}', [CajaChicaController::class, 'show'])
        ->middleware('permission:caja_chica.leer');
    Route::put('/caja-chica/{cajaChica}', [CajaChicaController::class, 'update'])
        ->middleware('permission:caja_chica.actualizar');
    Route::patch('/caja-chica/{cajaChica}', [CajaChicaController::class, 'update'])
        ->middleware('permission:caja_chica.actualizar');
    Route::delete('/caja-chica/{cajaChica}', [CajaChicaController::class, 'destroy'])
        ->middleware('permission:caja_chica.eliminar');
    Route::get('/caja-chica/abiertas/list', [CajaChicaController::class, 'abiertas'])
        ->middleware('permission:caja_chica.leer');
    Route::get('/caja-chica/responsable/{responsableId}', [CajaChicaController::class, 'porResponsable'])
        ->middleware('permission:caja_chica.leer');
    Route::post('/caja-chica/{cajaChica}/cerrar', [CajaChicaController::class, 'cerrar'])
        ->middleware('permission:caja_chica.actualizar');
    Route::post('/caja-chica/{cajaChica}/liquidar', [CajaChicaController::class, 'liquidar'])
        ->middleware('permission:caja_chica.actualizar');
    Route::post('/caja-chica/{cajaChica}/reabrir', [CajaChicaController::class, 'reabrir'])
        ->middleware('permission:caja_chica.actualizar');
    Route::get('/caja-chica/resumen/por-estado', [CajaChicaController::class, 'resumenPorEstado'])
        ->middleware('permission:caja_chica.leer');

    // Movimientos de Caja Chica
    Route::get('/movimientos-caja-chica', [MovimientoCajaChicaController::class, 'index'])
        ->middleware('permission:caja_chica.leer');
    Route::post('/movimientos-caja-chica', [MovimientoCajaChicaController::class, 'store'])
        ->middleware('permission:caja_chica.crear');
    Route::get('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'show'])
        ->middleware('permission:caja_chica.leer');
    Route::put('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'update'])
        ->middleware('permission:caja_chica.actualizar');
    Route::patch('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'update'])
        ->middleware('permission:caja_chica.actualizar');
    Route::delete('/movimientos-caja-chica/{movimientoCajaChica}', [MovimientoCajaChicaController::class, 'destroy'])
        ->middleware('permission:caja_chica.eliminar');
    Route::get('/movimientos-caja-chica/caja/{cajaChicaId}', [MovimientoCajaChicaController::class, 'porCaja'])
        ->middleware('permission:caja_chica.leer');
    Route::get('/movimientos-caja-chica/tipo/{tipo}', [MovimientoCajaChicaController::class, 'porTipo'])
        ->middleware('permission:caja_chica.leer');
    Route::get('/movimientos-caja-chica/resumen/totales', [MovimientoCajaChicaController::class, 'totalPorTipo'])
        ->middleware('permission:caja_chica.leer');

    // URL Shortener (Utilidad - Solo Admin)
    Route::get('/url-shortener', [UrlShortenerController::class, 'index'])
        ->middleware('permission:configuraciones.leer');
    Route::post('/url-shortener', [UrlShortenerController::class, 'store'])
        ->middleware('permission:configuraciones.crear');
    Route::get('/url-shortener/{id}', [UrlShortenerController::class, 'show'])
        ->middleware('permission:configuraciones.leer');
    Route::put('/url-shortener/{id}', [UrlShortenerController::class, 'update'])
        ->middleware('permission:configuraciones.actualizar');
    Route::patch('/url-shortener/{id}', [UrlShortenerController::class, 'update'])
        ->middleware('permission:configuraciones.actualizar');
    Route::delete('/url-shortener/{id}', [UrlShortenerController::class, 'destroy'])
        ->middleware('permission:configuraciones.eliminar');
    Route::get('/url-shortener/{id}/stats', [UrlShortenerController::class, 'stats'])
        ->middleware('permission:configuraciones.leer');

    // ------------------------------------------------------------------------
    // MÓDULO: RBAC (Roles y Permisos)
    // Solo usuarios con permiso de administrador
    // ------------------------------------------------------------------------
    
    // Roles
    Route::get('/roles', [RolController::class, 'index'])
        ->middleware('permission:ver-roles');
    Route::post('/roles', [RolController::class, 'store'])
        ->middleware('permission:crear-roles');
    Route::get('/roles/{rol}', [RolController::class, 'show'])
        ->middleware('permission:ver-roles');
    Route::put('/roles/{rol}', [RolController::class, 'update'])
        ->middleware('permission:editar-roles');
    Route::patch('/roles/{rol}', [RolController::class, 'update'])
        ->middleware('permission:editar-roles');
    Route::delete('/roles/{rol}', [RolController::class, 'destroy'])
        ->middleware('permission:eliminar-roles');
    Route::post('/roles/{id}/permisos', [RolController::class, 'asignarPermisos'])
        ->middleware('permission:editar-roles');
    Route::delete('/roles/{id}/permisos/{permiso_id}', [RolController::class, 'removerPermiso'])
        ->middleware('permission:editar-roles');

    // Permisos
    Route::get('/permisos/grouped', [PermisoController::class, 'grouped'])
        ->middleware('permission:ver-permisos');
    Route::get('/permisos/modulos/list', [PermisoController::class, 'modulos'])
        ->middleware('permission:ver-permisos');
    Route::apiResource('permisos', PermisoController::class)
        ->middleware(['permission:ver-permisos,crear-permisos,editar-permisos,eliminar-permisos']);

    // Usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->middleware('permission:usuarios.leer');
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->middleware('permission:usuarios.crear');
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])
        ->middleware('permission:usuarios.leer');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('permission:usuarios.actualizar');
    Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('permission:usuarios.actualizar');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])
        ->middleware('permission:usuarios.eliminar');
    Route::post('/usuarios/{id}/roles', [UsuarioController::class, 'asignarRoles'])
        ->middleware('permission:usuarios.actualizar');
    Route::post('/usuarios/{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword'])
        ->middleware('permission:usuarios.actualizar');

    // Roles-Permisos (Relación Many-to-Many)
    Route::get('/roles-permisos', [RolPermisoController::class, 'index'])
        ->middleware('permission:ver-roles');
    Route::post('/roles-permisos', [RolPermisoController::class, 'store'])
        ->middleware('permission:editar-roles');
    Route::get('/roles-permisos/{rolPermiso}', [RolPermisoController::class, 'show'])
        ->middleware('permission:ver-roles');
    Route::put('/roles-permisos/{rolPermiso}', [RolPermisoController::class, 'update'])
        ->middleware('permission:editar-roles');
    Route::patch('/roles-permisos/{rolPermiso}', [RolPermisoController::class, 'update'])
        ->middleware('permission:editar-roles');
    Route::delete('/roles-permisos/{rolPermiso}', [RolPermisoController::class, 'destroy'])
        ->middleware('permission:editar-roles');
    Route::post('/roles-permisos/asignar', [RolPermisoController::class, 'asignarPermisos'])
        ->middleware('permission:editar-roles');
    Route::post('/roles-permisos/remover', [RolPermisoController::class, 'removerPermisos'])
        ->middleware('permission:editar-roles');
    Route::get('/roles-permisos/por-rol/{rolId}', [RolPermisoController::class, 'permisosPorRol'])
        ->middleware('permission:ver-roles');
    Route::get('/roles-permisos/por-permiso/{permisoId}', [RolPermisoController::class, 'rolesPorPermiso'])
        ->middleware('permission:ver-permisos');
    Route::post('/roles-permisos/sincronizar', [RolPermisoController::class, 'sincronizarPermisos'])
        ->middleware('permission:editar-roles');

    // Rol-Usuario (Asignación de Roles a Usuarios)
    Route::get('/rol-usuario', [RolUsuarioController::class, 'index'])
        ->middleware('permission:usuarios.leer');
    Route::post('/rol-usuario', [RolUsuarioController::class, 'store'])
        ->middleware('permission:usuarios.actualizar');
    Route::get('/rol-usuario/{rolUsuario}', [RolUsuarioController::class, 'show'])
        ->middleware('permission:usuarios.leer');
    Route::put('/rol-usuario/{rolUsuario}', [RolUsuarioController::class, 'update'])
        ->middleware('permission:usuarios.actualizar');
    Route::patch('/rol-usuario/{rolUsuario}', [RolUsuarioController::class, 'update'])
        ->middleware('permission:usuarios.actualizar');
    Route::delete('/rol-usuario/{rolUsuario}', [RolUsuarioController::class, 'destroy'])
        ->middleware('permission:usuarios.actualizar');
    Route::get('/rol-usuario/roles-usuario/{usuarioId}', [RolUsuarioController::class, 'rolesPorUsuario'])
        ->middleware('permission:usuarios.leer');
    Route::get('/rol-usuario/usuarios-rol/{rolId}', [RolUsuarioController::class, 'usuariosPorRol'])
        ->middleware('permission:usuarios.leer');
    Route::post('/rol-usuario/asignar-roles', [RolUsuarioController::class, 'asignarRoles'])
        ->middleware('permission:usuarios.actualizar');

    // ============================================
    // FASE 9: Nuevos Módulos
    // ============================================
    
    // Mensajes Hacienda
    Route::apiResource('mensajes-hacienda', \App\Http\Controllers\MensajeHaciendaController::class);
    
    // Tipos Comprobantes FE
    Route::apiResource('tipos-comprobantes-fe', \App\Http\Controllers\TipoComprobanteFeController::class);
    
    // Códigos Actividad Económica
    Route::apiResource('codigos-actividad-economica', \App\Http\Controllers\CodigoActividadEconomicaController::class);
    
    // Declaraciones Tributarias
    Route::apiResource('declaraciones-tributarias', \App\Http\Controllers\DeclaracionTributariaController::class);
    
    // Retenciones Impuesto
    Route::apiResource('retenciones-impuesto', \App\Http\Controllers\RetencionImpuestoController::class);
    
    // Cuentas Bancarias
    Route::apiResource('cuentas-bancarias', \App\Http\Controllers\CuentaBancariaController::class);
    
    // Movimientos Bancarios
    Route::apiResource('movimientos-bancarios', \App\Http\Controllers\MovimientoBancarioController::class);
    
    // Deducciones Legales
    Route::apiResource('deducciones-legales', \App\Http\Controllers\DeduccionLegalController::class);
    
    // Planillas CCSS
    Route::apiResource('planillas-ccss', \App\Http\Controllers\PlanillaCcssController::class);
    
    // Tipos Cliente
    Route::apiResource('tipos-clientes', \App\Http\Controllers\TipoClienteController::class);
    
    // Zonas Geográficas
    Route::apiResource('zonas-geograficas', \App\Http\Controllers\ZonaGeograficaController::class);
    
    // Logs Acceso Sistema
    Route::apiResource('logs-acceso-sistema', \App\Http\Controllers\LogAccesoSistemaController::class);
});
