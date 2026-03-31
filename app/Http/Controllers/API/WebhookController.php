<?php

namespace App\Http\Controllers\API;

use App\DTOs\API\WebhookCreateDTO;
use App\DTOs\API\WebhookUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\WebhookLogResource;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestionar Webhooks
 *
 * Permite a los tenants configurar webhooks para recibir notificaciones
 * de eventos del sistema en URLs externas con firma HMAC-SHA256.
 *
 * @package App\Http\Controllers\API
 */
class WebhookController extends Controller
{
    use HasEmpresaContext;

    public function __construct(
        private readonly WebhookService $service
    ) {}

    #[OA\Get(
        path: "/api/webhooks",
        summary: "Listar webhooks",
        description: "Obtiene la lista paginada de webhooks configurados por el tenant.",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [
            new OA\Parameter(name: "activo", in: "query", required: false, schema: new OA\Schema(type: "integer", enum: [0, 1])),
            new OA\Parameter(name: "evento", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de webhooks"),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Webhook::class);

        $filtros = array_filter([
            'empresa_id' => $this->getEmpresaId(),
            'activo' => $request->get('activo'),
            'evento' => $request->get('evento'),
        ], fn ($v) => $v !== null);

        $webhooks = $this->service->listar($filtros, (int) $request->get('per_page', 15));

        return WebhookResource::collection($webhooks);
    }

    #[OA\Post(
        path: "/api/webhooks",
        summary: "Crear webhook",
        description: "Registra un nuevo webhook para el tenant. Se genera automáticamente un secret HMAC.",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "url", "eventos"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Notificación ventas"),
                    new OA\Property(property: "url", type: "string", example: "https://example.com/webhook"),
                    new OA\Property(property: "eventos", type: "array", items: new OA\Items(type: "string"), example: ["venta.creada", "pago.recibido"]),
                    new OA\Property(property: "descripcion", type: "string", nullable: true),
                    new OA\Property(property: "timeout_segundos", type: "integer", example: 30),
                    new OA\Property(property: "max_reintentos", type: "integer", example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Webhook creado"),
            new OA\Response(response: 422, description: "Validación fallida"),
        ]
    )]
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $this->authorize('create', Webhook::class);

        $dto = WebhookCreateDTO::fromRequest($request);
        $data = array_merge($dto->toArray(), [
            'empresa_id' => $this->getEmpresaId(),
        ]);

        $webhook = $this->service->crear($data);

        return $this->createdResponse(
            new WebhookResource($webhook),
            'Webhook creado exitosamente. Guarde el secret para verificar firmas.'
        );
    }

    #[OA\Get(
        path: "/api/webhooks/{id}",
        summary: "Obtener webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Webhook encontrado"),
            new OA\Response(response: 404, description: "No encontrado"),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $webhook = $this->service->obtener($id);
        $this->authorize('view', $webhook);

        return $this->successResponse(new WebhookResource($webhook));
    }

    #[OA\Put(
        path: "/api/webhooks/{id}",
        summary: "Actualizar webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string"),
                    new OA\Property(property: "url", type: "string"),
                    new OA\Property(property: "eventos", type: "array", items: new OA\Items(type: "string")),
                    new OA\Property(property: "activo", type: "boolean"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Webhook actualizado"),
            new OA\Response(response: 404, description: "No encontrado"),
        ]
    )]
    public function update(UpdateWebhookRequest $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $dto = WebhookUpdateDTO::fromRequest($request);
        $webhook = $this->service->actualizar($webhook, $dto->toArray());

        return $this->successResponse(
            new WebhookResource($webhook),
            'Webhook actualizado exitosamente'
        );
    }

    #[OA\Delete(
        path: "/api/webhooks/{id}",
        summary: "Eliminar webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Webhook eliminado"),
            new OA\Response(response: 404, description: "No encontrado"),
        ]
    )]
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);
        $this->service->eliminar($webhook);

        return $this->deletedResponse('Webhook eliminado exitosamente');
    }

    #[OA\Get(
        path: "/api/webhooks/{id}/logs",
        summary: "Obtener logs de entrega del webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Logs de entrega"),
        ]
    )]
    public function logs(Request $request, Webhook $webhook): AnonymousResourceCollection
    {
        $this->authorize('view', $webhook);

        $logs = $this->service->obtenerLogs($webhook->id, (int) $request->get('per_page', 20));

        return WebhookLogResource::collection($logs);
    }

    #[OA\Post(
        path: "/api/webhooks/{id}/test",
        summary: "Probar conectividad del webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Resultado de prueba"),
        ]
    )]
    public function test(Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $resultado = $this->service->probar($webhook);

        return $this->successResponse($resultado, $resultado['exitoso'] ? 'Conexión exitosa' : 'Conexión fallida');
    }

    #[OA\Post(
        path: "/api/webhooks/{id}/regenerar-secret",
        summary: "Regenerar secret HMAC del webhook",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(response: 200, description: "Secret regenerado"),
        ]
    )]
    public function regenerarSecret(Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $nuevoSecret = $this->service->regenerarSecret($webhook);

        return $this->successResponse(
            ['secret' => $nuevoSecret],
            'Secret regenerado exitosamente. Guarde el nuevo secret.'
        );
    }

    #[OA\Get(
        path: "/api/webhooks/eventos-disponibles",
        summary: "Listar eventos disponibles para suscripción",
        security: [["sanctum" => []]],
        tags: ["Webhooks"],
        responses: [
            new OA\Response(response: 200, description: "Lista de eventos"),
        ]
    )]
    public function eventosDisponibles(): JsonResponse
    {
        return $this->successResponse(Webhook::EVENTOS_DISPONIBLES);
    }
}
