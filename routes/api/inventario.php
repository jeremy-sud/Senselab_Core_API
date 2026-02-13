<?php

/**
 * Rutas de Inventario
 * - Productos, Almacenes, Entradas, Salidas, Stock
 * 
 * @package routes/api
 */

use App\Http\Controllers\API\AlmacenController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\InventarioController;
use App\Http\Controllers\API\EntradaInventarioController;
use App\Http\Controllers\API\DetalleEntradaInventarioController;
use App\Http\Controllers\API\SalidaInventarioController;
use App\Http\Controllers\API\DetalleSalidaInventarioController;
use App\Http\Controllers\InventarioProductoController;
use App\Http\Controllers\API\CategoriaProductoController;
use App\Http\Controllers\API\MarcaController;
use App\Http\Controllers\API\UnidadMedidaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Inventario
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

    // ------------------------------------------------------------------------
    // MÓDULO: ALMACENES
    // ------------------------------------------------------------------------
    Route::get('/almacenes', [AlmacenController::class, 'index'])
        ->middleware('permission:ver-almacenes');
    Route::post('/almacenes', [AlmacenController::class, 'store'])
        ->middleware('permission:crear-almacenes');
    Route::get('/almacenes/{almacen}', [AlmacenController::class, 'show'])
        ->middleware('permission:ver-almacenes');
    Route::put('/almacenes/{almacen}', [AlmacenController::class, 'update'])
        ->middleware('permission:editar-almacenes');
    Route::patch('/almacenes/{almacen}', [AlmacenController::class, 'update'])
        ->middleware('permission:editar-almacenes');
    Route::delete('/almacenes/{almacen}', [AlmacenController::class, 'destroy'])
        ->middleware('permission:eliminar-almacenes');

    // ------------------------------------------------------------------------
    // MÓDULO: PRODUCTOS
    // ------------------------------------------------------------------------
    Route::get('/productos', [ProductoController::class, 'index'])
        ->middleware('permission:ver-productos');
    Route::post('/productos', [ProductoController::class, 'store'])
        ->middleware('permission:crear-productos');
    Route::get('/productos/{producto}', [ProductoController::class, 'show'])
        ->middleware('permission:ver-productos');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permission:editar-productos');
    Route::patch('/productos/{producto}', [ProductoController::class, 'update'])
        ->middleware('permission:editar-productos');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('permission:eliminar-productos');
    Route::get('/productos/{producto}/stock', [ProductoController::class, 'stock'])
        ->middleware('permission:ver-productos');
    Route::get('/productos/buscar/codigo', [ProductoController::class, 'buscarPorCodigo'])
        ->middleware('permission:ver-productos');

    // ------------------------------------------------------------------------
    // CATÁLOGOS DE INVENTARIO
    // ------------------------------------------------------------------------
    
    // Categorías de Productos
    Route::get('/categorias-productos', [CategoriaProductoController::class, 'index'])
        ->middleware('permission:ver-categorias_producto');
    Route::post('/categorias-productos', [CategoriaProductoController::class, 'store'])
        ->middleware('permission:crear-categorias_producto');
    Route::get('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'show'])
        ->middleware('permission:ver-categorias_producto');
    Route::put('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'update'])
        ->middleware('permission:editar-categorias_producto');
    Route::delete('/categorias-productos/{categoriaProducto}', [CategoriaProductoController::class, 'destroy'])
        ->middleware('permission:eliminar-categorias_producto');

    // Marcas
    Route::apiResource('marcas', MarcaController::class)
        ->middleware('permission:ver-catalogos');

    // Unidades de Medida
    Route::apiResource('unidades-medida', UnidadMedidaController::class)
        ->parameters(['unidades-medida' => 'unidadMedida'])
        ->middleware('permission:ver-catalogos');

    // ------------------------------------------------------------------------
    // MÓDULO: INVENTARIO - Entradas/Salidas
    // ------------------------------------------------------------------------

    // Entradas de Inventario
    Route::apiResource('entradas-inventario', EntradaInventarioController::class);
    Route::post('/entradas-inventario/{id}/procesar', [EntradaInventarioController::class, 'procesar']);
    Route::post('/entradas-inventario/{id}/cancelar', [EntradaInventarioController::class, 'cancelar']);
    Route::get('/entradas-inventario/proveedor/{proveedorId}', [EntradaInventarioController::class, 'porProveedor']);
    Route::get('/entradas-inventario/almacen/{almacenId}', [EntradaInventarioController::class, 'porAlmacen']);
    Route::get('/entradas-inventario/resumen/por-tipo', [EntradaInventarioController::class, 'resumenPorTipo']);
    Route::get('/entradas-inventario/pendientes/list', [EntradaInventarioController::class, 'pendientes']);

    // Detalles de Entradas
    Route::get('/entradas-inventario/{entradaId}/detalles', [DetalleEntradaInventarioController::class, 'index']);
    Route::apiResource('detalles-entradas-inventario', DetalleEntradaInventarioController::class)
        ->except(['index']);

    // Salidas de Inventario
    Route::apiResource('salidas-inventario', SalidaInventarioController::class);
    Route::post('/salidas-inventario/{id}/procesar', [SalidaInventarioController::class, 'procesar']);
    Route::post('/salidas-inventario/{id}/cancelar', [SalidaInventarioController::class, 'cancelar']);
    Route::get('/salidas-inventario/cliente/{clienteId}', [SalidaInventarioController::class, 'porCliente']);
    Route::get('/salidas-inventario/almacen/{almacenId}', [SalidaInventarioController::class, 'porAlmacen']);
    Route::get('/salidas-inventario/resumen/por-tipo', [SalidaInventarioController::class, 'resumenPorTipo']);
    Route::get('/salidas-inventario/pendientes/list', [SalidaInventarioController::class, 'pendientes']);

    // Detalles de Salidas
    Route::get('/salidas-inventario/{salidaId}/detalles', [DetalleSalidaInventarioController::class, 'index']);
    Route::apiResource('detalles-salidas-inventario', DetalleSalidaInventarioController::class)
        ->except(['index']);

    // Inventario de Productos (Stock)
    Route::apiResource('inventario-productos', InventarioProductoController::class)
        ->parameters(['inventario-productos' => 'inventarioProducto']);
    Route::get('/inventario-productos/almacen/{almacenId}', [InventarioProductoController::class, 'porAlmacen']);
    Route::get('/inventario-productos/alertas/bajo-stock', [InventarioProductoController::class, 'bajoStockMinimo']);
    Route::get('/inventario-productos/alertas/sobre-stock', [InventarioProductoController::class, 'sobreStockMaximo']);
    Route::get('/inventario-productos/resumen/por-almacen', [InventarioProductoController::class, 'resumenPorAlmacen']);

    // Legacy Inventario Controller
    Route::prefix('inventario')->group(function () {
        Route::get('/entradas', [InventarioController::class, 'indexEntradas']);
        Route::post('/entradas', [InventarioController::class, 'storeEntrada']);
        Route::get('/entradas/{id}', [InventarioController::class, 'showEntrada']);
        Route::post('/entradas/{id}/cancelar', [InventarioController::class, 'cancelarEntrada']);
        Route::get('/salidas', [InventarioController::class, 'indexSalidas']);
        Route::post('/salidas', [InventarioController::class, 'storeSalida']);
        Route::get('/salidas/{id}', [InventarioController::class, 'showSalida']);
        Route::post('/salidas/{id}/cancelar', [InventarioController::class, 'cancelarSalida']);
    });
});
