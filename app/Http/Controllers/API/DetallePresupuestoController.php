<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDetallePresupuestoRequest;
use App\Http\Requests\UpdateDetallePresupuestoRequest;
use App\Http\Resources\DetallePresupuestoResource;
use App\Models\DetallePresupuesto;
use App\Models\Presupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para Detalle de Presupuestos
 * 
 * Gestiona las cuentas contables específicas de cada presupuesto con sus montos.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetallePresupuestoController extends Controller
{
    /**
     * Listar detalles de un presupuesto
     */
    public function index(Request $request, int $presupuestoId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($presupuestoId);

        $detalles = DetallePresupuesto::where('presupuesto_id', $presupuestoId)
            ->with('cuentaContable')
            ->get();

        return response()->json([
            'success' => true,
            'data' => DetallePresupuestoResource::collection($detalles)
        ]);
    }

    /**
     * Agregar cuenta al presupuesto
     */
    public function store(StoreDetallePresupuestoRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)
            ->findOrFail($request->presupuesto_id);

        if ($presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden agregar cuentas a un presupuesto finalizado'
            ], 422);
        }

        $detalle = DetallePresupuesto::create([
            'presupuesto_id' => $request->presupuesto_id,
            'cuenta_contable_id' => $request->cuenta_contable_id,
            'monto_presupuestado' => $request->monto_presupuestado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta agregada al presupuesto exitosamente',
            'data' => new DetallePresupuestoResource($detalle->load('cuentaContable'))
        ], 201);
    }

    /**
     * Mostrar detalle específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $detalle = DetallePresupuesto::with(['presupuesto', 'cuentaContable'])
            ->findOrFail($id);

        $empresaId = $request->user()->empresa_id;
        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new DetallePresupuestoResource($detalle)
        ]);
    }

    /**
     * Actualizar detalle de presupuesto
     */
    public function update(UpdateDetallePresupuestoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetallePresupuesto::with('presupuesto')
            ->findOrFail($id);

        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un presupuesto finalizado'
            ], 422);
        }

        $detalle->update([
            'monto_presupuestado' => $request->monto_presupuestado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detalle actualizado exitosamente',
            'data' => new DetallePresupuestoResource($detalle->fresh('cuentaContable'))
        ]);
    }

    /**
     * Eliminar cuenta del presupuesto
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetallePresupuesto::with('presupuesto')
            ->findOrFail($id);

        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta de un presupuesto finalizado'
            ], 422);
        }

        $detalle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada del presupuesto exitosamente'
        ]);
    }
}
