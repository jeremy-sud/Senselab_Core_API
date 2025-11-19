<?php

namespace App\Http\Controllers;

use App\Models\CajaChica;
use App\Http\Requests\StoreCajaChicaRequest;
use App\Http\Requests\UpdateCajaChicaRequest;
use App\Http\Resources\CajaChicaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CajaChicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }

        // Ordenamiento por fecha de apertura descendente
        $query->orderBy('fecha_apertura', 'desc');

        $cajasChica = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($cajasChica),
            'meta' => [
                'current_page' => $cajasChica->currentPage(),
                'last_page' => $cajasChica->lastPage(),
                'per_page' => $cajasChica->perPage(),
                'total' => $cajasChica->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCajaChicaRequest $request): JsonResponse
    {
        $cajaChica = CajaChica::create([
            'empresa_id' => auth()->user()->empresa_id,
            'nombre' => $request->nombre,
            'monto_inicial' => $request->monto_inicial,
            'saldo_actual' => $request->monto_inicial, // Inicialmente el saldo es igual al monto inicial
            'responsable_id' => $request->responsable_id,
            'fecha_apertura' => $request->fecha_apertura,
            'estado' => $request->estado ?? 'Abierta',
            'observaciones' => $request->observaciones,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica creado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCajaChicaRequest $request, CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $cajaChica->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica actualizado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        // Soft delete
        $cajaChica->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica eliminado exitosamente'
        ]);
    }

    /**
     * Listar fondos de caja chica abiertos.
     */
    public function abiertas(): JsonResponse
    {
        $cajasAbiertas = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('estado', 'Abierta')
            ->where('eliminado', 0)
            ->orderBy('fecha_apertura', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($cajasAbiertas)
        ]);
    }

    /**
     * Listar fondos por responsable.
     */
    public function porResponsable(int $responsableId): JsonResponse
    {
        $fondos = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('responsable_id', $responsableId)
            ->where('eliminado', 0)
            ->orderBy('fecha_apertura', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($fondos)
        ]);
    }

    /**
     * Cerrar un fondo de caja chica.
     */
    public function cerrar(Request $request, CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($cajaChica->estado !== 'Abierta') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cerrar fondos en estado Abierta'
            ], 422);
        }

        $cajaChica->update([
            'estado' => 'Cerrada',
            'fecha_cierre' => $request->fecha_cierre ?? now()->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica cerrado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Liquidar un fondo de caja chica.
     */
    public function liquidar(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($cajaChica->estado !== 'Cerrada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden liquidar fondos en estado Cerrada'
            ], 422);
        }

        $cajaChica->update(['estado' => 'Liquidada']);

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica liquidado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Reabrir un fondo de caja chica cerrado.
     */
    public function reabrir(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($cajaChica->estado === 'Liquidada') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden reabrir fondos liquidados'
            ], 422);
        }

        $cajaChica->update([
            'estado' => 'Abierta',
            'fecha_cierre' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fondo de caja chica reabierto exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Resumen de fondos por estado.
     */
    public function resumenPorEstado(): JsonResponse
    {
        $resumen = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0)
            ->selectRaw('estado, count(*) as total_fondos, sum(monto_inicial) as total_inicial, sum(saldo_actual) as total_saldo')
            ->groupBy('estado')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
