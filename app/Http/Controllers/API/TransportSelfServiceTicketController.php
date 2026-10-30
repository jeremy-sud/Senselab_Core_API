<?php

namespace App\Http\Controllers\API;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTripRequest;
use App\Http\Requests\VerifyFailedBookingRequest;
use App\Services\TransportSelfServiceTicketService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class TransportSelfServiceTicketController extends Controller
{
    public function __construct(
        private readonly TransportSelfServiceTicketService $selfService
    ) {}

    #[OA\Post(
        path: '/api/v1/transport/tickets/change-trip',
        summary: 'Autoservicio: Cambia tu viaje (TIG Style)',
        description: 'Permite al pasajero cambiar la fecha, hora, ruta o asiento de su viaje. Valida el código de boleto e identificación, exige un mínimo de anticipación respecto a la salida original, libera el asiento anterior, reserva el nuevo y liquida la diferencia tarifaria emitiendo el comprobante correspondiente.',
        tags: ['Transporte - Autoservicio'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['codigo_boleto', 'identificacion_pasajero', 'nuevo_horario_ruta_id', 'nuevo_asiento_numero'],
                properties: [
                    new OA\Property(property: 'codigo_boleto', type: 'string', example: 'TIG-2026-X8Y9Z'),
                    new OA\Property(property: 'identificacion_pasajero', type: 'string', example: '1-1234-0567'),
                    new OA\Property(property: 'nuevo_horario_ruta_id', type: 'integer', example: 12),
                    new OA\Property(property: 'nuevo_asiento_numero', type: 'string', example: '18'),
                    new OA\Property(property: 'motivo', type: 'string', example: 'Cambio de itinerario por reunión de trabajo'),
                    new OA\Property(property: 'horas_minimas_anticipacion', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cambio de viaje procesado exitosamente con comprobante y nuevo boleto'
            ),
            new OA\Response(response: 404, description: 'Boleto o viaje no encontrado'),
            new OA\Response(response: 409, description: 'El nuevo asiento solicitado ya está ocupado o bloqueado'),
            new OA\Response(response: 422, description: 'No se cumple la política de anticipación mínima u horario inválido')
        ]
    )]
    public function changeTrip(ChangeTripRequest $request): JsonResponse
    {
        try {
            $result = $this->selfService->changeTrip($request->validated());

            return response()->json($result, 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        }
    }

    #[OA\Post(
        path: '/api/v1/transport/tickets/verify-failed-booking',
        summary: 'Autoservicio: Auditoría y resolución de Reserva Fallida',
        description: 'Consulta por referencia bancaria/pasarela si un cobro fue procesado pero la emisión del boleto quedó interrumpida (pending/failed). Permite diagnosticar el estado, forzar la reemisión del boleto si el asiento sigue disponible o disparar el webhook de reembolso.',
        tags: ['Transporte - Autoservicio'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['referencia_pasarela'],
                properties: [
                    new OA\Property(property: 'referencia_pasarela', type: 'string', example: 'BAC-TX-987654321'),
                    new OA\Property(property: 'action', type: 'string', enum: ['audit', 'reissue', 'refund'], example: 'audit'),
                    new OA\Property(property: 'session_id', type: 'string', example: 'sess_9f82ab3c'),
                    new OA\Property(property: 'horario_ruta_id', type: 'integer', example: 12),
                    new OA\Property(property: 'asiento_numero', type: 'string', example: '14')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Auditoría completada exitosamente o acción ejecutada'
            ),
            new OA\Response(response: 404, description: 'Viaje o reserva no localizada'),
            new OA\Response(response: 422, description: 'Error en parámetros de auditoría o resolución')
        ]
    )]
    public function verifyFailedBooking(VerifyFailedBookingRequest $request): JsonResponse
    {
        try {
            $result = $this->selfService->verifyFailedBooking($request->validated());

            return response()->json($result, 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        }
    }
}
