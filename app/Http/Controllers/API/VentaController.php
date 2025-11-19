<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;

class VentaController extends Controller
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
            $sucursalId = $request->input('sucursal_id');
            $clienteId = $request->input('cliente_id');
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            
            $query = Venta::with([
                'empresa',
                'sucursal',
                'cliente',
                'usuario',
                'formaPago'
            ]);
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($sucursalId) {
                $query->where('sucursal_id', $sucursalId);
            }
            
            if ($clienteId) {
                $query->where('cliente_id', $clienteId);
            }
            
            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
            }
            
            $ventas = $query->orderBy('fecha_venta', 'desc')
                            ->paginate($perPage);
            
            return response()->json($ventas);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreVentaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreVentaRequest $request)
    {
        try {
            DB::beginTransaction();
            
            try {
                // Crear venta
                $ventaData = $request->except('detalles');
                $ventaData['numero_comprobante'] = $this->generarNumeroComprobante($request->empresa_id, $request->tipo_comprobante);
                
                $venta = Venta::create($ventaData);
                
                // Crear detalles y calcular totales
                $montoSubtotal = 0;
                $montoImpuestos = 0;
                $montoDescuentos = 0;
                
                foreach ($request->detalles as $detalle) {
                    $cantidad = $detalle['cantidad'];
                    $precioUnitario = $detalle['precio_unitario'];
                    $descuento = $detalle['descuento'] ?? 0;
                    $porcentajeImpuesto = $detalle['porcentaje_impuesto'] ?? 0;
                    
                    $subtotal = $cantidad * $precioUnitario;
                    $montoDescuentos += $descuento;
                    $subtotalConDescuento = $subtotal - $descuento;
                    $impuesto = $subtotalConDescuento * ($porcentajeImpuesto / 100);
                    $total = $subtotalConDescuento + $impuesto;
                    
                    $montoSubtotal += $subtotal;
                    $montoImpuestos += $impuesto;
                    
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'porcentaje_impuesto' => $porcentajeImpuesto,
                        'monto_impuesto' => $impuesto,
                        'subtotal' => $subtotal,
                        'total' => $total,
                        'descripcion' => $detalle['descripcion'] ?? null,
                    ]);
                }
                
                // Actualizar totales de la venta
                $venta->update([
                    'monto_subtotal' => $montoSubtotal,
                    'monto_descuentos' => $montoDescuentos,
                    'monto_impuestos' => $montoImpuestos,
                    'monto_total' => $montoSubtotal - $montoDescuentos + $montoImpuestos,
                ]);
                
                DB::commit();
                
                $venta->load(['cliente', 'detalles.producto']);
                
                return response()->json([
                    'message' => 'Venta creada exitosamente',
                    'data' => $venta
                ], 201);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear venta',
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
            $venta = Venta::with([
                'empresa',
                'sucursal',
                'cliente',
                'usuario',
                'formaPago',
                'detalles.producto'
            ])->findOrFail($id);
            
            return response()->json($venta);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateVentaRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateVentaRequest $request, int $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            
            // Solo permitir actualizar observaciones y estado
            $venta->update($request->validated());
            $venta->load(['cliente', 'detalles.producto']);
            
            return response()->json([
                'message' => 'Venta actualizada exitosamente',
                'data' => $venta
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar venta',
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
            $venta = Venta::findOrFail($id);
            
            // Marcar como anulada en lugar de eliminar
            $venta->update([
                'estado' => 'anulada',
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Venta anulada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar número de comprobante único
     * 
     * @param int $empresaId
     * @param string $tipoComprobante
     * @return string
     */
    private function generarNumeroComprobante($empresaId, $tipoComprobante)
    {
        $prefijos = [
            'factura' => 'FAC',
            'tiquete' => 'TIQ',
            'nota_credito' => 'NC',
            'nota_debito' => 'ND',
        ];
        
        $prefijo = $prefijos[$tipoComprobante] ?? 'DOC';
        
        $ultimaVenta = Venta::where('empresa_id', $empresaId)
                            ->where('tipo_comprobante', $tipoComprobante)
                            ->orderBy('id', 'desc')
                            ->first();
        
        $numero = $ultimaVenta ? (int)substr($ultimaVenta->numero_comprobante, -8) + 1 : 1;
        
        return $prefijo . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }
}
