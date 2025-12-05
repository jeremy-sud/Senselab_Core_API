<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CodigoActividadEconomica;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\CodigoActividadEconomicaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador para códigos de actividad económica Costa Rica
 * Catálogo DGT para clasificación de empresas
 */

#[OA\Tag(
    name: 'Códigos Actividad Económica',
    description: 'Catálogo de códigos de actividad económica del Ministerio de Hacienda'
)]
class CodigoActividadEconomicaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['codigos-actividad-economica', 'catalogos', 'hacienda'];
    protected int $cacheTTL = 86400; // 24 horas - catálogo muy estable

    /**
     * Listar códigos de actividad económica
     */
        #[OA\Get(
        path: '/api/codigo-actividad-economica',
        summary: 'Listar códigos de actividad económica',
        security: [['sanctum' => []]],
        tags: ['Códigos Actividad Económica'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de códigos de actividad económica'),
        ]
    )]

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
        #[OA\Post(
        path: '/api/codigo-actividad-economica',
        summary: 'Crear código de actividad económica',
        security: [['sanctum' => []]],
        tags: ['Códigos Actividad Económica'],
        responses: [
            new OA\Response(response: 201, description: 'código de actividad económica creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

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
        #[OA\Get(
        path: '/api/codigo-actividad-economica/{id}',
        summary: 'Obtener código de actividad económica',
        security: [['sanctum' => []]],
        tags: ['Códigos Actividad Económica'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'código de actividad económica encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(CodigoActividadEconomica $codigoActividadEconomica): CodigoActividadEconomicaResource
    {
        $this->authorize('view', $codigoActividadEconomica);
        return (new CodigoActividadEconomicaResource($codigoActividadEconomica))
            ->additional(['success' => true]);
    }

    /**
     * Actualizar código de actividad económica
     */
        #[OA\Put(
        path: '/api/codigo-actividad-economica/{id}',
        summary: 'Actualizar código de actividad económica',
        security: [['sanctum' => []]],
        tags: ['Códigos Actividad Económica'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'código de actividad económica actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

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
        #[OA\Delete(
        path: '/api/codigo-actividad-economica/{id}',
        summary: 'Eliminar código de actividad económica',
        security: [['sanctum' => []]],
        tags: ['Códigos Actividad Económica'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'código de actividad económica eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(CodigoActividadEconomica $codigoActividadEconomica): JsonResponse
    {
        $this->authorize('delete', $codigoActividadEconomica);

        $codigoActividadEconomica->update(['eliminado' => true, 'activo' => false]);

        $this->flushCache();

        return response()->json(['success' => true, 'message' => 'Código eliminado exitosamente']);
    }
}
