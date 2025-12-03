<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoComprobanteFe;
use App\Http\Requests\StoreTipoComprobanteFeRequest;
use App\Http\Requests\UpdateTipoComprobanteFeRequest;
use App\Http\Resources\TipoComprobanteFeResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controller para gestionar tipos de comprobantes de facturación electrónica
 * Catálogo según DGT Costa Rica (01-Factura, 02-Nota Débito, 03-Nota Crédito, 04-Tiquete)
 *
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */

#[OA\Tag(
    name: 'Tipos de Comprobante FE',
    description: 'Gestión de tipos de comprobantes de facturación electrónica (Factura, Nota Crédito, Tiquete, etc)'
)]
class TipoComprobanteFeController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['tipos-comprobante-fe', 'facturacion'];
    protected int $cacheTTL = 86400; // 24 horas - catálogo fiscal muy estable

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
        #[OA\Get(
        path: '/api/tipo-comprobante-fe',
        summary: 'Listar tipos de comprobante',
        security: [['sanctum' => []]],
        tags: ['Tipos de Comprobante FE'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de tipos de comprobante'),
        ]
    )]

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TipoComprobanteFe::class);

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $cacheKey = $this->getCacheKey('index', [
            'per_page' => $perPage,
            'search' => $search,
            'activo' => $request->input('activo'),
            'activos' => $request->input('activos'),
            'requiere_referencia' => $request->boolean('requiere_referencia'),
            'permite_exportacion' => $request->boolean('permite_exportacion'),
            'codigo_dgt' => $request->input('codigo_dgt')
        ]);

        $tiposComprobante = $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $search, $perPage) {
            $query = TipoComprobanteFe::where('eliminado', false);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo_dgt', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            // Filtro por estado activo
            if ($request->has('activo') || $request->has('activos')) {
                $esActivo = $request->boolean('activo') || $request->boolean('activos');
                if ($esActivo) {
                    $query->activos();
                } else {
                    $query->where('activo', false);
                }
            }

            // Filtros específicos de FE
            if ($request->boolean('requiere_referencia')) {
                $query->queRequierenReferencia();
            }

            if ($request->boolean('permite_exportacion')) {
                $query->permiteExportacion();
            }

            // Filtro por código DGT específico
            if ($request->has('codigo_dgt')) {
                $query->porCodigo($request->input('codigo_dgt'));
            }

            return $query->orderBy('codigo_dgt', 'asc')
                            ->paginate($perPage);
        });

        return TipoComprobanteFeResource::collection($tiposComprobante);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTipoComprobanteFeRequest $request
     * @return JsonResponse
     */
        #[OA\Post(
        path: '/api/tipo-comprobante-fe',
        summary: 'Crear tipo de comprobante',
        security: [['sanctum' => []]],
        tags: ['Tipos de Comprobante FE'],
        responses: [
            new OA\Response(response: 201, description: 'tipo de comprobante creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(StoreTipoComprobanteFeRequest $request): JsonResponse
    {
        $this->authorize('create', TipoComprobanteFe::class);

        try {
            $tipoComprobante = TipoComprobanteFe::create($request->validated());

            $this->flushCache();

            return (new TipoComprobanteFeResource($tipoComprobante))
                ->additional(['message' => 'Tipo de comprobante FE creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear tipo de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return TipoComprobanteFeResource
     */
        #[OA\Get(
        path: '/api/tipo-comprobante-fe/{id}',
        summary: 'Obtener tipo de comprobante',
        security: [['sanctum' => []]],
        tags: ['Tipos de Comprobante FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'tipo de comprobante encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(int $id): TipoComprobanteFeResource
    {
        $tipoComprobante = TipoComprobanteFe::findOrFail($id);

        $this->authorize('view', $tipoComprobante);

        return new TipoComprobanteFeResource($tipoComprobante);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTipoComprobanteFeRequest $request
     * @param int $id
     * @return JsonResponse
     */
        #[OA\Put(
        path: '/api/tipo-comprobante-fe/{id}',
        summary: 'Actualizar tipo de comprobante',
        security: [['sanctum' => []]],
        tags: ['Tipos de Comprobante FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'tipo de comprobante actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(UpdateTipoComprobanteFeRequest $request, int $id): JsonResponse
    {
        try {
            $tipoComprobante = TipoComprobanteFe::findOrFail($id);

            $this->authorize('update', $tipoComprobante);

            $tipoComprobante->update($request->validated());

            return (new TipoComprobanteFeResource($tipoComprobante))
                ->additional(['message' => 'Tipo de comprobante FE actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de comprobante FE no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar tipo de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
        #[OA\Delete(
        path: '/api/tipo-comprobante-fe/{id}',
        summary: 'Eliminar tipo de comprobante',
        security: [['sanctum' => []]],
        tags: ['Tipos de Comprobante FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'tipo de comprobante eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(int $id): JsonResponse
    {
        try {
            $tipoComprobante = TipoComprobanteFe::findOrFail($id);

            $this->authorize('delete', $tipoComprobante);

            // Soft delete - marcar como inactivo
            $tipoComprobante->update([
                'activo' => false,
                'eliminado' => true
            ]);

            $this->flushCache();

            return response()->json([
                'message' => 'Tipo de comprobante FE eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo de comprobante FE no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar tipo de comprobante FE',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
