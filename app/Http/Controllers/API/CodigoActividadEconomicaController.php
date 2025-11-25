<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CodigoActividadEconomica;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para códigos de actividad económica Costa Rica
 * Catálogo DGT para clasificación de empresas
 */
class CodigoActividadEconomicaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['codigos-actividad-economica', 'catalogos', 'hacienda'];
    protected int $cacheTTL = 86400; // 24 horas - catálogo muy estable

    /**
     * Listar códigos de actividad económica
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CodigoActividadEconomica::class);

        $cacheKey = $this->getCacheKey('index', [
            'activo' => $request->input('activo'),
            'categoria' => $request->input('categoria'),
            'buscar' => $request->input('buscar'),
            'per_page' => $request->input('per_page', 20)
        ]);

        $codigos = $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = CodigoActividadEconomica::query();

            if ($request->filled('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            if ($request->filled('categoria')) {
                $query->porCategoria($request->categoria);
            }

            if ($request->filled('buscar')) {
                $query->buscar($request->buscar);
            }

            return $query->orderBy('id')->paginate($request->input('per_page', 20));
        });

        return response()->json(['success' => true, 'data' => $codigos]);
    }

    /**
     * Crear código de actividad económica
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CodigoActividadEconomica::class);

        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:codigos_actividad_economica',
            'descripcion' => 'required|string|max:500',
            'categoria_principal' => 'nullable|string|max:100',
            'activo' => 'boolean'
        ]);

        $codigo = CodigoActividadEconomica::create($validated);

        $this->flushCache(['codigos-actividad-economica', 'catalogos', 'hacienda']);

        return response()->json(['success' => true, 'data' => $codigo], 201);
    }

    /**
     * Mostrar código específico
     */
    public function show(CodigoActividadEconomica $codigoActividadEconomica): JsonResponse
    {
        $this->authorize('view', $codigoActividadEconomica);
        return response()->json(['success' => true, 'data' => $codigoActividadEconomica]);
    }

    /**
     * Actualizar código de actividad económica
     */
    public function update(Request $request, CodigoActividadEconomica $codigoActividadEconomica): JsonResponse
    {
        $this->authorize('update', $codigoActividadEconomica);

        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:codigos_actividad_economica,codigo,' . $codigoActividadEconomica->id,
            'descripcion' => 'sometimes|string|max:500',
            'categoria_principal' => 'nullable|string|max:100',
            'activo' => 'boolean'
        ]);

        $codigoActividadEconomica->update($validated);

        $this->flushCache(['codigos-actividad-economica', 'catalogos', 'hacienda']);

        return response()->json(['success' => true, 'data' => $codigoActividadEconomica]);
    }

    /**
     * Eliminar código de actividad económica
     */
    public function destroy(CodigoActividadEconomica $codigoActividadEconomica): JsonResponse
    {
        $this->authorize('delete', $codigoActividadEconomica);

        $codigoActividadEconomica->update(['eliminado' => true, 'activo' => false]);

        $this->flushCache(['codigos-actividad-economica', 'catalogos', 'hacienda']);

        return response()->json(['success' => true, 'message' => 'Código eliminado exitosamente']);
    }
}
