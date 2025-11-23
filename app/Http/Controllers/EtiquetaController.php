<?php

namespace App\Http\Controllers;

use App\Models\Etiqueta;
use App\Http\Requests\StoreEtiquetaRequest;
use App\Http\Requests\UpdateEtiquetaRequest;
use App\Http\Requests\BuscarEtiquetaRequest;
use App\Http\Resources\EtiquetaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EtiquetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Etiqueta::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        // Filtros
        if ($request->filled('search')) {
            $query->where('nombre', 'LIKE', '%' . $request->search . '%');
        }

        // Ordenamiento
        $query->orderBy('nombre');

        $etiquetas = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => EtiquetaResource::collection($etiquetas),
            'meta' => [
                'current_page' => $etiquetas->currentPage(),
                'last_page' => $etiquetas->lastPage(),
                'per_page' => $etiquetas->perPage(),
                'total' => $etiquetas->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEtiquetaRequest $request): JsonResponse
    {
        $etiqueta = Etiqueta::create([
            'empresa_id' => auth()->user()->empresa_id,
            'nombre' => $request->nombre,
            'color_hex' => $request->color_hex ?? '#CCCCCC',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta creada exitosamente',
            'data' => new EtiquetaResource($etiqueta)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Etiqueta $etiqueta): JsonResponse
    {
        if ($etiqueta->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new EtiquetaResource($etiqueta)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEtiquetaRequest $request, Etiqueta $etiqueta): JsonResponse
    {
        if ($etiqueta->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $etiqueta->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta actualizada exitosamente',
            'data' => new EtiquetaResource($etiqueta)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Etiqueta $etiqueta): JsonResponse
    {
        if ($etiqueta->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        // Soft delete
        $etiqueta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta eliminada exitosamente'
        ]);
    }

    /**
     * Listar todas las etiquetas activas (sin paginación).
     */
    public function todas(): JsonResponse
    {
        $etiquetas = Etiqueta::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EtiquetaResource::collection($etiquetas)
        ]);
    }

    /**
     * Estadísticas de uso de etiquetas.
     */
    public function estadisticas(): JsonResponse
    {
        $etiquetas = Etiqueta::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0)
            ->withCount('entidadEtiquetas')
            ->orderBy('entidad_etiquetas_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $etiquetas->map(function ($etiqueta) {
                return [
                    'id' => $etiqueta->id,
                    'nombre' => $etiqueta->nombre,
                    'color_hex' => $etiqueta->color_hex,
                    'veces_usada' => $etiqueta->entidad_etiquetas_count,
                ];
            })
        ]);
    }

    /**
     * Buscar etiquetas por nombre.
     */
    public function buscar(BuscarEtiquetaRequest $request): JsonResponse
    {
        $etiquetas = Etiqueta::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0)
            ->where('nombre', 'LIKE', '%' . $request->q . '%')
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => EtiquetaResource::collection($etiquetas)
        ]);
    }
}
