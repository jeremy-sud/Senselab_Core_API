<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\UpdateComprobanteRecibidoElectronicoRequest;
use App\Http\Resources\ComprobanteRecibidoElectronicoResource;
use App\Models\ComprobanteRecibidoElectronico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para Comprobantes Electrónicos Recibidos
 * 
 * Gestiona los comprobantes electrónicos recibidos de proveedores (facturas,
 * notas de crédito, etc.) según normativa DGT de Costa Rica.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ComprobanteRecibidoElectronicoController extends Controller
{
    /**
     * Listar comprobantes recibidos de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
            ->orderBy('fecha_recepcion_sistema', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes),
            'meta' => [
                'current_page' => $comprobantes->currentPage(),
                'total' => $comprobantes->total(),
                'per_page' => $comprobantes->perPage()
            ]
        ]);
    }

    /**
     * Registrar nuevo comprobante recibido
     */
    public function store(StoreComprobanteRecibidoElectronicoRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();
        try {
            $comprobante = ComprobanteRecibidoElectronico::create([
                'empresa_id' => $empresaId,
                'proveedor_id' => $request->proveedor_id,
                'clave_numerica' => $request->clave_numerica,
                'consecutivo_receptor' => $request->consecutivo_receptor,
                'tipo_documento_dgt' => $request->tipo_documento_dgt,
                'fecha_emision_comprobante' => $request->fecha_emision_comprobante,
                'moneda' => $request->moneda ?? 'CRC',
                'total_impuesto' => $request->total_impuesto,
                'total_comprobante' => $request->total_comprobante,
                'xml_contenido' => $request->xml_contenido,
                'entrada_inventario_id' => $request->entrada_inventario_id,
                'estado_hacienda' => 'Procesando',
                'confirmado_usuario' => 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante electrónico registrado exitosamente',
                'data' => new ComprobanteRecibidoElectronicoResource($comprobante->load('proveedor'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar comprobante específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante)
        ]);
    }

    /**
     * Actualizar comprobante recibido
     */
    public function update(UpdateComprobanteRecibidoElectronicoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un comprobante ya confirmado'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $comprobante->update($request->only([
                'proveedor_id',
                'consecutivo_receptor',
                'entrada_inventario_id'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante actualizado exitosamente',
                'data' => new ComprobanteRecibidoElectronicoResource($comprobante->load('proveedor'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar comprobante
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un comprobante ya confirmado'
            ], 422);
        }

        $comprobante->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comprobante eliminado exitosamente'
        ]);
    }

    /**
     * Confirmar/Aceptar comprobante por usuario
     */
    public function confirmar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $usuarioId = $request->user()->id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'El comprobante ya fue confirmado anteriormente'
            ], 422);
        }

        $comprobante->update([
            'confirmado_usuario' => 1,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante confirmado exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))
        ]);
    }

    /**
     * Rechazar comprobante por usuario
     */
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $usuarioId = $request->user()->id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        $comprobante->update([
            'confirmado_usuario' => 2,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante rechazado exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))
        ]);
    }

    /**
     * Obtener comprobantes por proveedor
     */
    public function porProveedor(Request $request, int $proveedorId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->with(['proveedor', 'entradaInventario'])
            ->orderBy('fecha_emision_comprobante', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes),
            'meta' => [
                'current_page' => $comprobantes->currentPage(),
                'total' => $comprobantes->total()
            ]
        ]);
    }

    /**
     * Obtener comprobantes pendientes de confirmar
     */
    public function pendientes(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('confirmado_usuario', 0)
            ->with(['proveedor'])
            ->orderBy('fecha_recepcion_sistema', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes)
        ]);
    }

    /**
     * Resumen por estado de Hacienda
     */
    public function resumenPorEstado(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->selectRaw('estado_hacienda, COUNT(*) as total_comprobantes, SUM(total_comprobante) as monto_total')
            ->groupBy('estado_hacienda')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Actualizar respuesta de Hacienda
     */
    public function actualizarRespuestaHacienda(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'xml_respuesta_hacienda' => 'required|string',
            'estado_hacienda' => 'required|string|in:Aceptado,Rechazado,Procesando',
            'mensaje_hacienda' => 'nullable|string'
        ]);

        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        $comprobante->update([
            'xml_respuesta_hacienda' => $request->xml_respuesta_hacienda,
            'estado_hacienda' => $request->estado_hacienda,
            'mensaje_hacienda' => $request->mensaje_hacienda,
            'fecha_respuesta_hacienda' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta de Hacienda actualizada exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante)
        ]);
    }
}
