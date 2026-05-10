<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ZonaGeografica;
use App\Http\Requests\StoreZonaGeograficaRequest;
use App\Http\Requests\UpdateZonaGeograficaRequest;
use App\Http\Resources\ZonaGeograficaResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controller para gestionar zonas geográficas
 * Provincias, cantones, distritos, zonas de ventas y rutas
 *
 * @author GitHub Copilot
 * @copyright 2025 Senselab
 */

#[OA\Tag(
    name: 'Zonas Geográficas',
    description: 'Gestión de zonas geográficas de cobertura (provincias, cantones, distritos)'
)]
class ZonaGeograficaController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['zonas-geograficas', 'geografico'];
    protected int $cacheTTL = 3600; // 1 hora - cambia ocasionalmente

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
        #[OA\Get(
        path: '/api/zona-geografica',
        summary: 'Listar zonas geográficas',
        security: [['sanctum' => []]],
        tags: ['Zonas Geográficas'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de zonas geográficas'),
        ]
    )]

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', ZonaGeografica::class);

        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = Auth::user()->empresa_id;

            $cacheKey = $this->getCacheKey('index', [
                'search' => $search,
                'activa' => $request->get('activa'),
                'activas' => $request->get('activas'),
                'tipo' => $request->get('tipo'),
                'provincias' => $request->boolean('provincias'),
                'cantones' => $request->boolean('cantones'),
                'zonas_ventas' => $request->boolean('zonas_ventas'),
                'zona_padre_id' => $request->get('zona_padre_id'),
                'vendedor_asignado_id' => $request->get('vendedor_asignado_id'),
                'per_page' => $perPage
            ]);

            return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId, $search, $perPage) {
                $query = ZonaGeografica::with(['empresa', 'zonaPadre', 'vendedorAsignado'])
                                       ->where('empresa_id', $empresaId)
                                       ->where('eliminado', false);

                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                          ->orWhere('codigo', 'like', "%{$search}%");
                    });
                }

                // Filtro por estado activo
                if ($request->has('activa') || $request->has('activas')) {
                    $esActivo = $request->boolean('activa') || $request->boolean('activas');
                    if ($esActivo) {
                        $query->activas();
                    } else {
                        $query->where('activa', false);
                    }
                }

                // Filtros por tipo
                if ($request->has('tipo')) {
                    $query->porTipo($request->input('tipo'));
                }

                if ($request->boolean('provincias')) {
                    $query->provincias();
                }

                if ($request->boolean('cantones')) {
                    $query->cantones();
                }

                if ($request->boolean('zonas_ventas')) {
                    $query->zonasVentas();
                }

                // Filtro por zona padre
                if ($request->has('zona_padre_id')) {
                    $query->where('zona_padre_id', $request->input('zona_padre_id'));
                }

                // Filtro por vendedor asignado
                if ($request->has('vendedor_asignado_id')) {
                    $query->where('vendedor_asignado_id', $request->input('vendedor_asignado_id'));
                }

                $zonasGeograficas = $query->orderBy('nombre', 'asc')
                                          ->paginate($perPage);

                return ZonaGeograficaResource::collection($zonasGeograficas);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener zonas geográficas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreZonaGeograficaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
        #[OA\Post(
        path: '/api/zona-geografica',
        summary: 'Crear zona geográfica',
        security: [['sanctum' => []]],
        tags: ['Zonas Geográficas'],
        responses: [
            new OA\Response(response: 201, description: 'zona geográfica creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(StoreZonaGeograficaRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', ZonaGeografica::class);

        try {
            $data = $request->validated();

            // Asignar empresa_id del usuario autenticado si no viene en request
            if (!isset($data['empresa_id'])) {
                $data['empresa_id'] = Auth::user()->empresa_id;
            }

            $zonaGeografica = ZonaGeografica::create($data);
            $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);

            $this->flushCache();

            return (new ZonaGeograficaResource($zonaGeografica))
                ->additional(['message' => 'Zona geográfica creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear zona geográfica',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return ZonaGeograficaResource
     */
        #[OA\Get(
        path: '/api/zona-geografica/{id}',
        summary: 'Obtener zona geográfica',
        security: [['sanctum' => []]],
        tags: ['Zonas Geográficas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'zona geográfica encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(int $id): ZonaGeograficaResource
    {
        $zonaGeografica = ZonaGeografica::with([
            'empresa',
            'zonaPadre',
            'zonasHijas',
            'vendedorAsignado'
        ])->findOrFail($id);

        $this->authorize('view', $zonaGeografica);

        return new ZonaGeograficaResource($zonaGeografica);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateZonaGeograficaRequest $request
     * @param int $id
     * @return ZonaGeograficaResource
     */
        #[OA\Put(
        path: '/api/zona-geografica/{id}',
        summary: 'Actualizar zona geográfica',
        security: [['sanctum' => []]],
        tags: ['Zonas Geográficas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'zona geográfica actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(UpdateZonaGeograficaRequest $request, int $id): ZonaGeograficaResource
    {
        $zonaGeografica = ZonaGeografica::findOrFail($id);

        $this->authorize('update', $zonaGeografica);

        $zonaGeografica->update($request->validated());
        $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);

        $this->flushCache();

        return (new ZonaGeograficaResource($zonaGeografica))
            ->additional(['message' => 'Zona geográfica actualizada exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
        #[OA\Delete(
        path: '/api/zona-geografica/{id}',
        summary: 'Eliminar zona geográfica',
        security: [['sanctum' => []]],
        tags: ['Zonas Geográficas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'zona geográfica eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $zonaGeografica = ZonaGeografica::findOrFail($id);

            $this->authorize('delete', $zonaGeografica);

            // Soft delete - marcar como inactiva
            $zonaGeografica->update([
                'activa' => false,
                'eliminado' => true
            ]);

            $this->flushCache();

            return response()->json([
                'message' => 'Zona geográfica eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Zona geográfica no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar zona geográfica',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
