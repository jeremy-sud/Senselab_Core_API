<?php

namespace App\Http\Controllers;

use App\Models\RegimenTributario;
use App\Http\Requests\StoreRegimenTributarioRequest;
use App\Http\Requests\UpdateRegimenTributarioRequest;
use App\Http\Resources\RegimenTributarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RegimenTributarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RegimenTributario::where('eliminado', 0);

        // Filtros
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('nombre', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Ordenamiento
        $query->orderBy('codigo');

        $regimenes = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => RegimenTributarioResource::collection($regimenes),
            'meta' => [
                'current_page' => $regimenes->currentPage(),
                'last_page' => $regimenes->lastPage(),
                'per_page' => $regimenes->perPage(),
                'total' => $regimenes->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRegimenTributarioRequest $request): JsonResponse
    {
        $regimen = RegimenTributario::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Régimen tributario creado exitosamente',
            'data' => new RegimenTributarioResource($regimen)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RegimenTributario $regimenTributario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RegimenTributarioResource($regimenTributario)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRegimenTributarioRequest $request, RegimenTributario $regimenTributario): JsonResponse
    {
        $regimenTributario->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Régimen tributario actualizado exitosamente',
            'data' => new RegimenTributarioResource($regimenTributario)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RegimenTributario $regimenTributario): JsonResponse
    {
        // Soft delete
        $regimenTributario->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Régimen tributario eliminado exitosamente'
        ]);
    }

    /**
     * Listar todos los regímenes activos (sin paginación).
     */
    public function todos(): JsonResponse
    {
        $regimenes = RegimenTributario::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RegimenTributarioResource::collection($regimenes)
        ]);
    }

    /**
     * Buscar régimen por código.
     */
    public function porCodigo(string $codigo): JsonResponse
    {
        $regimen = RegimenTributario::where('codigo', $codigo)
            ->where('eliminado', 0)
            ->first();

        if (!$regimen) {
            return response()->json([
                'success' => false,
                'message' => 'Régimen tributario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RegimenTributarioResource($regimen)
        ]);
    }
}
