<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Models\DetalleOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            $proveedorId = $request->input('proveedor_id');
            $estado = $request->input('estado');
            
            $query = OrdenCompra::with([
                'empresa',
                'proveedor',
                'usuario'
            ]);
            
            if ($empresaId) {
                $query->porEmpresa($empresaId);
            }
            
            if ($proveedorId) {
                $query->porProveedor($proveedorId);
            }
            
            if ($estado) {
                $query->where('estado', $estado);
            }
            
            if ($request->boolean('pendientes')) {
                $query->pendientes();
            }
            
            if ($request->boolean('activas')) {
                $query->activas();
            }
            
            $ordenes = $query->orderBy('fecha_orden', 'desc')
                             ->paginate($perPage);
            
            return response()->json($ordenes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener órdenes de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreOrdenCompraRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrdenCompraRequest $request)
    {
        try {
            DB::beginTransaction();
            
            try {
                // Crear orden de compra
                $ordenData = $request->except('detalles');
                $ordenData['numero_orden'] = $this->generarNumeroOrden($request->empresa_id);
                
                $orden = OrdenCompra::create($ordenData);
                
                // Crear detalles
                $montoSubtotal = 0;
                $montoImpuestos = 0;
                
                foreach ($request->detalles as $detalle) {
                    $cantidad = $detalle['cantidad'];
                    $precioUnitario = $detalle['precio_unitario'];
                    $descuento = $detalle['descuento'] ?? 0;
                    
                    $subtotal = ($cantidad * $precioUnitario) - $descuento;
                    $montoSubtotal += $subtotal;
                    
                    DetalleOrdenCompra::create([
                        'orden_compra_id' => $orden->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'subtotal' => $subtotal,
                        'descripcion' => $detalle['descripcion'] ?? null,
                    ]);
                }
                
                // Actualizar totales
                $orden->update([
                    'monto_subtotal' => $montoSubtotal,
                    'monto_impuestos' => $montoImpuestos,
                    'monto_total' => $montoSubtotal + $montoImpuestos,
                ]);
                
                DB::commit();
                
                $orden->load(['proveedor', 'detalles.producto']);
                
                return response()->json([
                    'message' => 'Orden de compra creada exitosamente',
                    'data' => $orden
                ], 201);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $orden = OrdenCompra::with([
                'empresa',
                'proveedor',
                'usuario',
                'detalles.producto',
                'pagos',
                'entradasInventario'
            ])->findOrFail($id);
            
            // Calcular saldo pendiente
            $orden->saldo_pendiente = $orden->calcularSaldoPendiente();
            
            return response()->json($orden);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Orden de compra no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateOrdenCompraRequest $request, int $id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            
            $orden->update($request->validated());
            $orden->load(['proveedor', 'detalles.producto']);
            
            return response()->json([
                'message' => 'Orden de compra actualizada exitosamente',
                'data' => $orden
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Orden de compra no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $orden = OrdenCompra::findOrFail($id);
            
            // Solo permitir eliminar en estado borrador
            if ($orden->estado !== 'borrador') {
                return response()->json([
                    'message' => 'Solo se pueden eliminar órdenes en estado borrador'
                ], 422);
            }
            
            DB::beginTransaction();
            
            try {
                // Eliminar detalles
                $orden->detalles()->delete();
                
                // Soft delete de la orden
                $orden->update([
                    'activo' => false,
                    'eliminado' => true
                ]);
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Orden de compra eliminada exitosamente'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Orden de compra no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar número de orden único
     * 
     * @param int $empresaId
     * @return string
     */
    private function generarNumeroOrden($empresaId)
    {
        $ultimaOrden = OrdenCompra::where('empresa_id', $empresaId)
                                  ->orderBy('id', 'desc')
                                  ->first();
        
        $numero = $ultimaOrden ? (int)substr($ultimaOrden->numero_orden, -6) + 1 : 1;
        
        return 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}
