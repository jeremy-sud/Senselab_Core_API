<?php

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controlador de Chatbot Asistente ERP
 *
 * Permite realizar consultas en lenguaje natural sobre el sistema ERP.
 * El chatbot puede responder preguntas sobre ventas, inventario, clientes, etc.
 *
 * @package App\Http\Controllers\API\AI
 */
#[OA\Tag(name: 'IA - Chatbot', description: 'Asistente virtual con IA para consultas del ERP')]
class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Enviar mensaje al chatbot
     */
    #[OA\Post(
        path: '/api/ai/chat',
        summary: 'Enviar mensaje al chatbot',
        description: 'Envía un mensaje en lenguaje natural y recibe una respuesta contextualizada con datos del ERP',
        tags: ['IA - Chatbot'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message'],
                properties: [
                    new OA\Property(
                        property: 'message',
                        type: 'string',
                        description: 'Mensaje del usuario',
                        example: '¿Cuánto vendimos hoy?'
                    ),
                    new OA\Property(
                        property: 'history',
                        type: 'array',
                        description: 'Historial de conversación previo',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'role', type: 'string', enum: ['user', 'assistant']),
                                new OA\Property(property: 'content', type: 'string'),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Respuesta del chatbot',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Hoy se han realizado ventas por ₡125,000 en 15 transacciones.'
                        ),
                        new OA\Property(property: 'intent', type: 'string', example: 'sales'),
                        new OA\Property(
                            property: 'data_used',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['ventas', 'top_productos']
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 500, description: 'Error del servidor'),
        ]
    )]
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'array|max:20',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        // Obtener empresa del usuario autenticado
        $user = $request->user();
        if ($user && $user->empresa_id) {
            $this->chatbotService->setEmpresa($user->empresa_id);
        }

        $result = $this->chatbotService->processMessage(
            $request->input('message'),
            [
                'history' => $request->input('history', []),
                'user' => $user ? [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                ] : null,
            ]
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Obtener sugerencias de preguntas
     */
    #[OA\Get(
        path: '/api/ai/chat/suggestions',
        summary: 'Obtener sugerencias de preguntas',
        description: 'Retorna una lista de preguntas sugeridas que el usuario puede hacer',
        tags: ['IA - Chatbot'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de sugerencias',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(
                            property: 'suggestions',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'category', type: 'string'),
                                    new OA\Property(
                                        property: 'questions',
                                        type: 'array',
                                        items: new OA\Items(type: 'string')
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function suggestions(): JsonResponse
    {
        $suggestions = [
            [
                'category' => 'Ventas',
                'icon' => '💰',
                'questions' => [
                    '¿Cuánto vendimos hoy?',
                    '¿Cuáles son las ventas de este mes?',
                    '¿Cuál es el producto más vendido?',
                    '¿Cómo van las ventas comparado con el mes pasado?',
                ],
            ],
            [
                'category' => 'Inventario',
                'icon' => '📦',
                'questions' => [
                    '¿Qué productos tienen bajo stock?',
                    '¿Cuál es el valor total del inventario?',
                    '¿Hay productos sin stock?',
                    '¿Cuántos productos tenemos en total?',
                ],
            ],
            [
                'category' => 'Clientes',
                'icon' => '👥',
                'questions' => [
                    '¿Quiénes son nuestros mejores clientes?',
                    '¿Cuántos clientes nuevos este mes?',
                    '¿Qué clientes tienen facturas pendientes?',
                    '¿Cuánto nos deben en total?',
                ],
            ],
            [
                'category' => 'Cuentas por Cobrar',
                'icon' => '📋',
                'questions' => [
                    '¿Cuántas facturas están vencidas?',
                    '¿Cuál es el monto total pendiente de cobro?',
                    '¿Qué facturas vencen esta semana?',
                ],
            ],
            [
                'category' => 'Estadísticas',
                'icon' => '📊',
                'questions' => [
                    'Dame un resumen del día de hoy',
                    '¿Cuál es el ticket promedio de venta?',
                    'Muéstrame las métricas principales',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Obtener resumen rápido del dashboard
     */
    #[OA\Get(
        path: '/api/ai/chat/quick-stats',
        summary: 'Obtener estadísticas rápidas',
        description: 'Retorna estadísticas clave del negocio para mostrar en dashboard',
        tags: ['IA - Chatbot'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas del negocio',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(
                            property: 'stats',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'ventas_hoy', type: 'object'),
                                new OA\Property(property: 'ventas_mes', type: 'object'),
                                new OA\Property(property: 'inventario', type: 'object'),
                                new OA\Property(property: 'pendientes', type: 'object'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function quickStats(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->empresa_id) {
            $this->chatbotService->setEmpresa($user->empresa_id);
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'ventas' => $this->chatbotService->getSalesSummary(),
                'inventario' => $this->chatbotService->getInventoryStatus(),
                'pendientes' => $this->chatbotService->getPendingInvoices(),
                'bajo_stock' => $this->chatbotService->getLowStockProducts(5),
            ],
        ]);
    }

    /**
     * Verificar estado del servicio de chatbot
     */
    #[OA\Get(
        path: '/api/ai/chat/status',
        summary: 'Estado del servicio de chatbot',
        description: 'Verifica si el chatbot está disponible y configurado',
        tags: ['IA - Chatbot'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado del servicio',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean'),
                        new OA\Property(property: 'api_configured', type: 'boolean'),
                        new OA\Property(property: 'model', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function status(): JsonResponse
    {
        $apiKey = config('openai.api_key');

        return response()->json([
            'success' => true,
            'enabled' => config('openai.features.chatbot.enabled', false),
            'api_configured' => !empty($apiKey),
            'model' => config('openai.models.chat', 'gpt-4o'),
        ]);
    }
}

