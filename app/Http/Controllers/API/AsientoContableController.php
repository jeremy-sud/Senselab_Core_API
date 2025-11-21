<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsientoContableRequest;
use App\Http\Requests\UpdateAsientoContableRequest;
use App\Http\Resources\AsientoContableResource;
use App\Models\AsientoContable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Asientos Contables
 *
 * Gestiona los asientos contables con sistema de doble partida (debe = haber).
 * Incluye estados: Borrador, Mayorizado, Anulado.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class AsientoContableController extends Controller
{
    /**
     * Listar todos los asientos contables de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = AsientoContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable', 'empresa']);

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_asiento', [$request->desde, $request->hasta]);
        }

        // Filtro por cuenta contable (a través de detalles)
        if ($request->filled('cuenta_contable_id')) {
            $query->whereHas('detalles', function ($q) use ($request) {
                $q->where('cuenta_contable_id', $request->cuenta_contable_id);
            });
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'fecha_asiento'), $request->get('sort_order', 'desc'));

        $asientos = $query->paginate($request->get('per_page', 15));

        return AsientoContableResource::collection($asientos);
    }

    /**
     * Crear un nuevo asiento contable
     *
     * @param StoreAsientoContableRequest $request
     * @return JsonResponse
     */
    public function store(StoreAsientoContableRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['empresa_id'] = $request->user()->empresa_id;

            // Calcular totales de debe y haber
            $detalles = $validated['detalles'];
            $totalDebe = collect($detalles)->sum('debe');
            $totalHaber = collect($detalles)->sum('haber');

            $validated['total_debe'] = $totalDebe;
            $validated['total_haber'] = $totalHaber;
            $validated['estado'] = $validated['estado'] ?? 'Borrador';

            // Crear asiento
            $asiento = AsientoContable::create($validated);

            // Crear detalles
            foreach ($detalles as $detalle) {
                $asiento->detalles()->create($detalle);
            }

            $asiento->load(['detalles.cuentaContable', 'empresa']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable creado exitosamente',
                'data' => new AsientoContableResource($asiento)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el asiento contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un asiento contable específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable', 'empresa'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new AsientoContableResource($asiento)
        ]);
    }

    /**
     * Actualizar un asiento contable existente
     *
     * @param UpdateAsientoContableRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateAsientoContableRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // No permitir modificar asientos mayorizados
        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un asiento contable que ya ha sido mayorizado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Si se actualizan los detalles, recalcular totales
            if (isset($validated['detalles'])) {
                $detalles = $validated['detalles'];
                $totalDebe = collect($detalles)->sum('debe');
                $totalHaber = collect($detalles)->sum('haber');

                $validated['total_debe'] = $totalDebe;
                $validated['total_haber'] = $totalHaber;

                // Eliminar detalles antiguos y crear nuevos
                $asiento->detalles()->delete();
                foreach ($detalles as $detalle) {
                    $asiento->detalles()->create($detalle);
                }
            }

            $asiento->update($validated);
            $asiento->load(['detalles.cuentaContable', 'empresa']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable actualizado exitosamente',
                'data' => new AsientoContableResource($asiento)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el asiento contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (soft delete) un asiento contable
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // No permitir eliminar asientos mayorizados
        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un asiento contable que ya ha sido mayorizado'
            ], 422);
        }

        $asiento->update(['eliminado' => 1, 'activo' => 0]);
        $asiento->detalles()->update(['eliminado' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Asiento contable eliminado exitosamente'
        ]);
    }

    /**
     * Mayorizar un asiento contable (cambiar estado a Mayorizado)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function mayorizar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with('detalles.cuentaContable')
            ->firstOrFail();

        if ($asiento->estado === 'Mayorizado') {
            return response()->json([
                'success' => false,
                'message' => 'El asiento contable ya está mayorizado'
            ], 422);
        }

        // Validar que debe = haber
        if ($asiento->total_debe != $asiento->total_haber) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede mayorizar: el total del debe no coincide con el haber'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Actualizar saldo de las cuentas contables
            foreach ($asiento->detalles as $detalle) {
                $cuenta = $detalle->cuentaContable;
                $diferencia = $detalle->debe - $detalle->haber;
                $cuenta->increment('saldo_actual', $diferencia);
            }

            $asiento->update(['estado' => 'Mayorizado']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asiento contable mayorizado exitosamente',
                'data' => new AsientoContableResource($asiento->fresh(['detalles.cuentaContable']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al mayorizar el asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar que un asiento esté balanceado (debe = haber)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function validar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $asiento = AsientoContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $balanceado = $asiento->total_debe == $asiento->total_haber;
        $diferencia = abs($asiento->total_debe - $asiento->total_haber);

        return response()->json([
            'success' => true,
            'data' => [
                'balanceado' => $balanceado,
                'total_debe' => $asiento->total_debe,
                'total_haber' => $asiento->total_haber,
                'diferencia' => $diferencia,
                'estado' => $asiento->estado
            ]
        ]);
    }
}
