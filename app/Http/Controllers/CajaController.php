<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Http\Requests\StoreCajaRequest;
use App\Http\Requests\UpdateCajaRequest;
use App\Http\Resources\CajaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CajaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Caja::where('eliminado', 0);

        // Filtros
        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->filled('search')) {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        // Ordenamiento
        $query->orderBy('nombre');

        $cajas = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CajaResource::collection($cajas),
            'meta' => [
                'current_page' => $cajas->currentPage(),
                'last_page' => $cajas->lastPage(),
                'per_page' => $cajas->perPage(),
                'total' => $cajas->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCajaRequest $request): JsonResponse
    {
        $caja = Caja::create([
            'sucursal_id' => $request->sucursal_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Caja creada exitosamente',
            'data' => new CajaResource($caja)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Caja $caja): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CajaResource($caja)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCajaRequest $request, Caja $caja): JsonResponse
    {
        $caja->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Caja actualizada exitosamente',
            'data' => new CajaResource($caja)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Caja $caja): JsonResponse
    {
        // Soft delete
        $caja->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Caja eliminada exitosamente'
        ]);
    }

    /**
     * Listar cajas por sucursal.
     */
    public function porSucursal(Request $request, int $sucursalId): JsonResponse
    {
        $cajas = Caja::where('sucursal_id', $sucursalId)
            ->where('eliminado', 0)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CajaResource::collection($cajas)
        ]);
    }

    /**
     * Listar todas las cajas activas.
     */
    public function activas(): JsonResponse
    {
        $cajas = Caja::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CajaResource::collection($cajas)
        ]);
    }

    /**
     * Activar o desactivar una caja.
     */
    public function toggleActivo(Caja $caja): JsonResponse
    {
        $caja->update(['activo' => !$caja->activo]);

        return response()->json([
            'success' => true,
            'message' => $caja->activo ? 'Caja activada exitosamente' : 'Caja desactivada exitosamente',
            'data' => new CajaResource($caja)
        ]);
    }
}
