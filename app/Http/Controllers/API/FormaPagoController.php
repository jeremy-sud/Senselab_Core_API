<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormaPagoRequest;
use App\Http\Requests\UpdateFormaPagoRequest;
use App\Http\Resources\FormaPagoResource;
use App\Models\FormaPago;
use App\Services\FormaPagoService;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de formas de pago
 *
 * Gestiona métodos de pago (Efectivo, Tarjeta, Transferencia, SINPE, etc.)
 * Nota: Tabla global sin empresa_id según api_db.sql
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class FormaPagoController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['formas-pago', 'catalogos'];
    protected int $cacheTTL = 86400; // 24 horas - catálogo muy estable

    public function __construct(
        private readonly FormaPagoService $service
    ) {}

    /**
     * Listar todas las formas de pago
     *
     * GET /api/formas-pago
     */
    #[OA\Get(
        path: '/api/formas-pago',
        summary: 'Listar formas de pago',
        description: 'Obtiene todos los métodos de pago disponibles',
        security: [['sanctum' => []]],
        tags: ['Formas de Pago'],
        parameters: [
            new OA\Parameter(name: 'activo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FormaPago'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FormaPago::class);

        $filtros = array_filter([
            'activo' => $request->has('activo') ? $request->boolean('activo') : null,
            'tipo' => $request->input('tipo'),
        ], fn ($v) => $v !== null);

        $cacheKey = $this->getCacheKey('index', $filtros);

        $formasPago = $this->cacheQueryIfEnabled($cacheKey, fn () => $this->service->listarTodos($filtros));

        return FormaPagoResource::collection($formasPago);
    }

    /**
     * Crear una nueva forma de pago
     *
     * POST /api/formas-pago
     */
    #[OA\Post(
        path: '/api/formas-pago',
        summary: 'Crear forma de pago',
        description: 'Registra nuevo método de pago en el sistema',
        security: [['sanctum' => []]],
        tags: ['Formas de Pago'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['codigo_dgt', 'nombre'],
                properties: [
                    new OA\Property(property: 'codigo_dgt', type: 'string', maxLength: 10, example: '01'),
                    new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Efectivo'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Forma de pago creada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/FormaPago')]))
        ]
    )]
    public function store(StoreFormaPagoRequest $request): JsonResponse
    {
        $this->authorize('create', FormaPago::class);

        $formaPago = $this->service->crear($request->validated());

        $this->flushCache();

        return (new FormaPagoResource($formaPago))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una forma de pago específica
     *
     * GET /api/formas-pago/{id}
     */
    #[OA\Get(
        path: '/api/formas-pago/{id}',
        summary: 'Obtener forma de pago',
        security: [['sanctum' => []]],
        tags: ['Formas de Pago'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(int $id): FormaPagoResource
    {
        $formaPago = $this->service->obtener($id);

        $this->authorize('view', $formaPago);

        return new FormaPagoResource($formaPago);
    }

    /**
     * Actualizar una forma de pago existente
     *
     * PUT/PATCH /api/formas-pago/{id}
     */
    #[OA\Put(
        path: '/api/formas-pago/{id}',
        summary: 'Actualizar forma de pago',
        security: [['sanctum' => []]],
        tags: ['Formas de Pago'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateFormaPagoRequest $request, int $id): FormaPagoResource
    {
        $formaPago = $this->service->obtener($id);

        $this->authorize('update', $formaPago);

        $formaPago = $this->service->actualizar($formaPago, $request->validated());

        $this->flushCache();

        return new FormaPagoResource($formaPago);
    }

    /**
     * Eliminar una forma de pago (soft delete)
     *
     * DELETE /api/formas-pago/{id}
     */
    #[OA\Delete(
        path: '/api/formas-pago/{id}',
        summary: 'Eliminar forma de pago',
        description: 'Soft delete de la forma de pago',
        security: [['sanctum' => []]],
        tags: ['Formas de Pago'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Eliminada')]
    )]
    public function destroy(int $id): JsonResponse
    {
        $formaPago = $this->service->obtener($id);

        $this->authorize('delete', $formaPago);

        $this->service->eliminar($formaPago);

        $this->flushCache();

        return response()->json([
            'message' => 'Forma de pago eliminada exitosamente',
            'data' => new FormaPagoResource($formaPago->fresh())
        ], 200);
    }
}
