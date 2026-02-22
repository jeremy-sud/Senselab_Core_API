<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RetencionImpuesto;
use App\Http\Requests\StoreRetencionImpuestoRequest;
use App\Http\Requests\UpdateRetencionImpuestoRequest;
use App\Http\Resources\RetencionImpuestoResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * Controller para gestionar retenciones de impuestos
 * Retenciones de renta e IVA aplicadas a proveedores
 *
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */

#[OA\Tag(
    name: 'Retenciones de Impuesto',
    description: 'Gestión de retenciones de impuesto sobre renta'
)]
class RetencionImpuestoController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['retenciones-impuesto', 'contabilidad', 'proveedores'];
    protected int $cacheTTL = 2400; // 40min - tax withholdings, semi-stable
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
        #[OA\Get(
        path: '/api/retencion-impuesto',
        summary: 'Listar retenciones de impuesto',
        security: [['sanctum' => []]],
        tags: ['Retenciones de Impuesto'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de retenciones de impuesto'),
        ]
    )]

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', RetencionImpuesto::class);

        $cacheKey = $this->getCacheKey('index', [
            'search' => $request->input('search'),
            'proveedor_id' => $request->input('proveedor_id'),
            'tipo_retencion' => $request->input('tipo_retencion'),
            'declaradas' => $request->boolean('declaradas'),
            'pendientes_declaracion' => $request->boolean('pendientes_declaracion'),
            'periodo_declaracion' => $request->input('periodo_declaracion'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'monto_minimo' => $request->input('monto_minimo'),
            'per_page' => $request->input('per_page', 15)
        ]);

        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            try {
                $perPage = $request->input('per_page', 15);
                $search = $request->input('search');
                $empresaId = Auth::user()->empresa_id;

                $query = RetencionImpuesto::with(['empresa', 'proveedor'])
                                          ->where('empresa_id', $empresaId)
                                          ->where('eliminado', false);

                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('numero_comprobante', 'like', "%{$search}%")
                          ->orWhere('notas', 'like', "%{$search}%")
                          ->orWhereHas('proveedor', function($sq) use ($search) {
                              $sq->where('nombre', 'like', "%{$search}%");
                          });
                    });
                }

                // Filtro por proveedor
                if ($request->has('proveedor_id')) {
                    $query->where('proveedor_id', $request->input('proveedor_id'));
                }

                // Filtro por tipo de retención
                if ($request->has('tipo_retencion')) {
                    $query->porTipo($request->input('tipo_retencion'));
                }

                // Filtro por estado de declaración
                if ($request->boolean('declaradas')) {
                    $query->declaradas();
                }

                if ($request->boolean('pendientes_declaracion')) {
                    $query->pendientesDeclaracion();
                }

                // Filtro por período de declaración (acepta 'periodo' o 'periodo_declaracion')
                if ($request->has('periodo_declaracion') || $request->has('periodo')) {
                    $periodoValue = $request->input('periodo_declaracion') ?? $request->input('periodo');
                    $query->porPeriodo($periodoValue);
                }

                // Filtro por rango de fechas
                if ($request->has('fecha_desde')) {
                    $query->where('fecha_retencion', '>=', $request->input('fecha_desde'));
                }

                if ($request->has('fecha_hasta')) {
                    $query->where('fecha_retencion', '<=', $request->input('fecha_hasta'));
                }

                // Filtro por monto retenido mínimo
                if ($request->has('monto_minimo')) {
                    $query->where('monto_retenido', '>=', $request->input('monto_minimo'));
                }

                $retenciones = $query->orderBy('fecha_retencion', 'desc')
                                     ->orderBy('created_at', 'desc')
                                     ->paginate($perPage);

                return RetencionImpuestoResource::collection($retenciones);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al obtener retenciones de impuestos',
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRetencionImpuestoRequest $request
     * @return JsonResponse
     */
        #[OA\Post(
        path: '/api/retencion-impuesto',
        summary: 'Crear retención de impuesto',
        security: [['sanctum' => []]],
        tags: ['Retenciones de Impuesto'],
        responses: [
            new OA\Response(response: 201, description: 'retención de impuesto creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(StoreRetencionImpuestoRequest $request): JsonResponse
    {
        $this->authorize('create', RetencionImpuesto::class);

        try {
            $data = $request->validated();

            // Asignar empresa_id del usuario autenticado
            $data['empresa_id'] = Auth::user()->empresa_id;

            // Calcular monto retenido si no viene (monto_base * porcentaje / 100)
            if (!isset($data['monto_retenido']) && isset($data['monto_base']) && isset($data['porcentaje_retencion'])) {
                $data['monto_retenido'] = ($data['monto_base'] * $data['porcentaje_retencion']) / 100;
            }

            $retencion = RetencionImpuesto::create($data);
            $retencion->load(['empresa', 'proveedor']);

            $this->flushCache();

            return (new RetencionImpuestoResource($retencion))
                ->additional(['message' => 'Retención de impuesto creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear retención de impuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return RetencionImpuestoResource|JsonResponse
     */
        #[OA\Get(
        path: '/api/retencion-impuesto/{id}',
        summary: 'Obtener retención de impuesto',
        security: [['sanctum' => []]],
        tags: ['Retenciones de Impuesto'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'retención de impuesto encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(int $id): RetencionImpuestoResource|JsonResponse
    {
        try {
            $retencion = RetencionImpuesto::with([
                'empresa',
                'proveedor'
            ])->findOrFail($id);

            $this->authorize('view', $retencion);

            return new RetencionImpuestoResource($retencion);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Retención de impuesto no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener retención de impuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRetencionImpuestoRequest $request
     * @param int $id
     * @return JsonResponse
     */
        #[OA\Put(
        path: '/api/retencion-impuesto/{id}',
        summary: 'Actualizar retención de impuesto',
        security: [['sanctum' => []]],
        tags: ['Retenciones de Impuesto'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'retención de impuesto actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(UpdateRetencionImpuestoRequest $request, int $id): JsonResponse
    {
        try {
            $retencion = RetencionImpuesto::findOrFail($id);

            $this->authorize('update', $retencion);

            $data = $request->validated();

            // Recalcular monto retenido si cambiaron base o porcentaje
            if ((isset($data['monto_base']) || isset($data['porcentaje_retencion'])) && !isset($data['monto_retenido'])) {
                $base = $data['monto_base'] ?? $retencion->monto_base;
                $porcentaje = $data['porcentaje_retencion'] ?? $retencion->porcentaje_retencion;
                $data['monto_retenido'] = ($base * $porcentaje) / 100;
            }

            $retencion->update($data);
            $retencion->load(['empresa', 'proveedor']);

            $this->flushCache();

            return response()->json([
                'message' => 'Retención de impuesto actualizada exitosamente',
                'data' => new RetencionImpuestoResource($retencion)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Retención de impuesto no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar retención de impuesto',
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
        path: '/api/retencion-impuesto/{id}',
        summary: 'Eliminar retención de impuesto',
        security: [['sanctum' => []]],
        tags: ['Retenciones de Impuesto'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'retención de impuesto eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(int $id): JsonResponse
    {
        try {
            $retencion = RetencionImpuesto::findOrFail($id);

            $this->authorize('delete', $retencion);

            // Soft delete
            $retencion->update(['eliminado' => true]);

            $this->flushCache();

            return response()->json([
                'message' => 'Retención de impuesto eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Retención de impuesto no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar retención de impuesto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar retención como declarada
     *
     * @param int $id
     * @return JsonResponse
     */
    public function marcarComoDeclarada(int $id): JsonResponse
    {
        try {
            $retencion = RetencionImpuesto::findOrFail($id);

            $this->authorize('update', $retencion);

            $retencion->marcarComoDeclarada();

            return response()->json([
                'message' => 'Retención marcada como declarada exitosamente',
                'data' => new RetencionImpuestoResource($retencion)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Retención de impuesto no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al marcar retención como declarada',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
