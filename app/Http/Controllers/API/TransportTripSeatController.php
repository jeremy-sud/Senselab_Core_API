<?php

namespace App\Http\Controllers\API;

use App\Exceptions\SeatHoldConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\HoldSeatRequest;
use App\Http\Requests\ReleaseSeatRequest;
use App\Services\TransportSeatHoldingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransportTripSeatController extends Controller
{
    public function __construct(
        private readonly TransportSeatHoldingService $holdingService
    ) {}

    #[OA\Get(
        path: '/api/v1/transport/trips/{trip}/seatmap',
        summary: 'Mapa físico de asientos y disponibilidad en tiempo real estilo TIG',
        description: 'Retorna la configuración física del autobús (pisos, filas, columnas, pasillo) y el estado exacto de cada asiento: available, locked o booked con locked_until.',
        tags: ['Transporte'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                in: 'path',
                description: 'ID del viaje / horario de ruta',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'session_id',
                in: 'query',
                description: 'ID de sesión web o carrito para identificar asientos propios',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'sess_9f82ab3c')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mapa de asientos y configuración física del bus obtenido exitosamente'
            ),
            new OA\Response(response: 404, description: 'Viaje no encontrado')
        ]
    )]
    public function seatmap(Request $request, int $trip): JsonResponse
    {
        $sessionId = $request->query('session_id') ?? $request->header('X-Session-ID');
        $seatmapData = $this->holdingService->getSeatMap($trip, $sessionId);

        return response()->json([
            'success' => true,
            'data' => $seatmapData,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/transport/trips/{trip}/hold-seat',
        summary: 'Retención atómica temporal de un asiento (Holding Time 10 minutos)',
        description: 'Bloquea atómicamente un asiento mediante transacción pesimista durante 10 minutos para evitar ventas dobles. Si ya expiró una retención previa, permite reclamarla.',
        tags: ['Transporte'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                in: 'path',
                description: 'ID del viaje / horario de ruta',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['seat_number'],
                properties: [
                    new OA\Property(property: 'seat_number', type: 'string', example: '14'),
                    new OA\Property(property: 'passenger_session_id', type: 'string', example: 'sess_9f82ab3c'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Asiento retenido exitosamente por 10 minutos'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflicto: El asiento ya está ocupado o retenido por otro usuario'
            ),
            new OA\Response(response: 422, description: 'Error de validación en parámetros')
        ]
    )]
    public function holdSeat(HoldSeatRequest $request, int $trip): JsonResponse
    {
        $validated = $request->validated();
        $seatNumber = (string) $validated['seat_number'];
        $sessionId = $validated['passenger_session_id'] ?? $request->header('X-Session-ID');
        $userId = $validated['user_id'] ?? (auth('sanctum')->check() ? auth('sanctum')->id() : null);
        $ip = $request->ip();

        try {
            $result = $this->holdingService->holdSeat($trip, $seatNumber, $sessionId, $userId, $ip);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (SeatHoldConflictException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Conflict',
                'message' => $e->getMessage(),
                'seat_number' => $seatNumber,
                'trip_id' => $trip,
            ], 409);
        }
    }

    #[OA\Post(
        path: '/api/v1/transport/trips/{trip}/release-seat',
        summary: 'Liberación voluntaria de un asiento retenido',
        description: 'Permite al usuario deseleccionar voluntariamente el asiento o abandonar la compra antes del vencimiento del holding time.',
        tags: ['Transporte'],
        parameters: [
            new OA\Parameter(
                name: 'trip',
                in: 'path',
                description: 'ID del viaje / horario de ruta',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['seat_number'],
                properties: [
                    new OA\Property(property: 'seat_number', type: 'string', example: '14'),
                    new OA\Property(property: 'passenger_session_id', type: 'string', example: 'sess_9f82ab3c'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Asiento liberado correctamente'
            )
        ]
    )]
    public function releaseSeat(ReleaseSeatRequest $request, int $trip): JsonResponse
    {
        $validated = $request->validated();
        $seatNumber = (string) $validated['seat_number'];
        $sessionId = $validated['passenger_session_id'] ?? $request->header('X-Session-ID');
        $userId = $validated['user_id'] ?? (auth('sanctum')->check() ? auth('sanctum')->id() : null);

        $result = $this->holdingService->releaseSeat($trip, $seatNumber, $sessionId, $userId);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }
}
