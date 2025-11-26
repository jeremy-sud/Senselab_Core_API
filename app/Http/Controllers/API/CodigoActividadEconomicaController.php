<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CodigoActividadEconomica;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\CodigoActividadEconomicaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(Request $request): AnonymousResourceCollection
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

        return CodigoActividadEconomicaResource::collection($codigos);
    }

    /**
     * Crear código de actividad económica
     */
    public function store(Request $request): CodigoActividadEconomicaResource
    {
        $this->authorize('create', CodigoActividadEconomica::class);

        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:codigos_actividad_economica',
            'descripcion' => 'required|string|max:500',
            'categoria_principal' => 'nullable|string|max:100',
            'activo' => 'boolean'
        ]);

        $codigo = CodigoActividadEconomica::create($validated);

        $this->flushCache();

        return (new CodigoActividadEconomicaResource($codigo))
            ->additional(['success' => true]);
    }

    /**
     * Mostrar código específico
     */
    public function show(CodigoActividadEconomica $codigoActividadEconomica): CodigoActividadEconomicaResource
    {
        $this->authorize('view', $codigoActividadEconomica);
        return (new CodigoActividadEconomicaResource($codigoActividadEconomica))
            ->additional(['success' => true]);
    }

    /**
     * Actualizar código de actividad económica
     */
    public function update(Request $request, CodigoActividadEconomica $codigoActividadEconomica): CodigoActividadEconomicaResource
    {
        $this->authorize('update', $codigoActividadEconomica);

        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:codigos_actividad_economica,codigo,' . $codigoActividadEconomica->id,
            'descripcion' => 'sometimes|string|max:500',
            'categoria_principal' => 'nullable|string|max:100',
            'activo' => 'boolean'
        ]);

        $codigoActividadEconomica->update($validated);

        $this->flushCache();

        return (new CodigoActividadEconomicaResource($codigoActividadEconomica))
            ->additional(['success' => true]);
    }

    /**
     * Eliminar código de actividad económica
     */
    public function destroy(CodigoActividadEconomica $codigoActividadEconomica): JsonResponse
    {
        $this->authorize('delete', $codigoActividadEconomica);

        $codigoActividadEconomica->update(['eliminado' => true, 'activo' => false]);

        $this->flushCache();

        return response()->json(['success' => true, 'message' => 'Código eliminado exitosamente']);
    }
}
