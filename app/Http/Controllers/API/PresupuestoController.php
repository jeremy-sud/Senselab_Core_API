<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePresupuestoRequest;
use App\Http\Requests\UpdatePresupuestoRequest;
use App\Http\Resources\PresupuestoResource;
use App\Models\Presupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para Presupuestos Financieros
 * 
 * Gestiona presupuestos maestros con sus períodos y estados.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PresupuestoController extends Controller
{
    /**
     * Listar presupuestos de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuestos = Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->orderBy('periodo_inicio', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => PresupuestoResource::collection($presupuestos),
            'meta' => [
                'current_page' => $presupuestos->currentPage(),
                'total' => $presupuestos->total(),
                'per_page' => $presupuestos->perPage()
            ]
        ]);
    }

    /**
     * Crear nuevo presupuesto
     */
    public function store(StorePresupuestoRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();
        try {
            $presupuesto = Presupuesto::create([
                'empresa_id' => $empresaId,
                'nombre' => $request->nombre,
                'periodo_inicio' => $request->periodo_inicio,
                'periodo_fin' => $request->periodo_fin,
                'estado' => 'Borrador'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presupuesto creado exitosamente',
                'data' => new PresupuestoResource($presupuesto)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el presupuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar presupuesto específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Actualizar presupuesto
     */
    public function update(UpdatePresupuestoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($id);

        if ($presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un presupuesto finalizado'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $presupuesto->update($request->only([
                'nombre',
                'periodo_inicio',
                'periodo_fin'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presupuesto actualizado exitosamente',
                'data' => new PresupuestoResource($presupuesto)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el presupuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar presupuesto
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($id);

        if ($presupuesto->estado === 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un presupuesto activo'
            ], 422);
        }

        $presupuesto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto eliminado exitosamente'
        ]);
    }

    /**
     * Activar presupuesto
     */
    public function activar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($id);

        if ($presupuesto->estado === 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'El presupuesto ya está activo'
            ], 422);
        }

        if ($presupuesto->detalles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede activar un presupuesto sin cuentas detalladas'
            ], 422);
        }

        $presupuesto->update(['estado' => 'Activo']);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto activado exitosamente',
            'data' => new PresupuestoResource($presupuesto->fresh('detalles'))
        ]);
    }

    /**
     * Finalizar presupuesto
     */
    public function finalizar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($id);

        if ($presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'El presupuesto ya está finalizado'
            ], 422);
        }

        $presupuesto->update(['estado' => 'Finalizado']);

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto finalizado exitosamente',
            'data' => new PresupuestoResource($presupuesto)
        ]);
    }

    /**
     * Obtener presupuestos activos
     */
    public function activos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuestos = Presupuesto::where('empresa_id', $empresaId)
            ->where('estado', 'Activo')
            ->with('detalles')
            ->orderBy('periodo_inicio', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PresupuestoResource::collection($presupuestos)
        ]);
    }

    /**
     * Resumen de presupuesto
     */
    public function resumen(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->findOrFail($id);

        $totalPresupuestado = $presupuesto->detalles->sum('monto_presupuestado');

        return response()->json([
            'success' => true,
            'data' => [
                'presupuesto' => new PresupuestoResource($presupuesto),
                'total_presupuestado' => number_format($totalPresupuestado, 2),
                'total_cuentas' => $presupuesto->detalles->count(),
                'periodo_dias' => \Carbon\Carbon::parse($presupuesto->periodo_inicio)
                    ->diffInDays(\Carbon\Carbon::parse($presupuesto->periodo_fin))
            ]
        ]);
    }
}
