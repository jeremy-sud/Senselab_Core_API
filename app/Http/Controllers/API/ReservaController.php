<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateReservaRequest;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Reservas',
    description: 'Módulo de gestión de reservas y citas'
)]
class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $service
    ) {
        $this->authorizeResource(Reserva::class, 'reserva');
    }

    #[OA\Get(
        path: '/api/reservas',
        summary: 'Listar reservas',
        description: 'Obtener un listado paginado de reservas con filtros',
        security: [['sanctum' => []]],
        tags: ['Reservas'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', description: 'Filtrar por estado', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'confirmada', 'cancelada', 'completada'])),
            new OA\Parameter(name: 'fecha_desde', in: 'query', description: 'Fecha inicio', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de reservas'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->all();
        $filtros['empresa_id'] = $request->user()->empresa_id;
        
        $reservas = $this->service->listar(
            $filtros,
            (int) $request->input('per_page', 15)
        );

        return response()->json($reservas);
    }

    #[OA\Post(
        path: '/api/reservas',
        summary: 'Crear reserva',
        description: 'Crea una nueva reserva',
        security: [['sanctum' => []]],
        tags: ['Reservas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'servicio', 'fecha_inicio', 'fecha_fin'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer'),
                    new OA\Property(property: 'servicio', type: 'string'),
                    new OA\Property(property: 'fecha_inicio', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'fecha_fin', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'confirmada']),
                    new OA\Property(property: 'monto_total', type: 'number'),
                    new OA\Property(property: 'notas', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reserva creada'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreReservaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['empresa_id'] = $request->user()->empresa_id;
        $data['usuario_id'] = $request->user()->id;

        $reserva = $this->service->crear($data);

        return response()->json([
            'message' => 'Reserva creada correctamente',
            'data' => $reserva
        ], 201);
    }

    #[OA\Get(
        path: '/api/reservas/{id}',
        summary: 'Obtener reserva',
        description: 'Obtener detalles de una reserva específica',
        security: [['sanctum' => []]],
        tags: ['Reservas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de reserva'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(Request $request, Reserva $reserva): JsonResponse
    {
        // El load ya está o podemos devolver directamente ya que authorize() lo filtró por empresa
        $reserva->load(['cliente', 'usuario']);
        
        return response()->json([
            'data' => $reserva
        ]);
    }

    #[OA\Put(
        path: '/api/reservas/{id}',
        summary: 'Actualizar reserva',
        description: 'Actualizar los datos de una reserva',
        security: [['sanctum' => []]],
        tags: ['Reservas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'confirmada', 'cancelada', 'completada']),
                    new OA\Property(property: 'notas', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reserva actualizada'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function update(UpdateReservaRequest $request, Reserva $reserva): JsonResponse
    {
        $updatedReserva = $this->service->actualizar($reserva, $request->validated());

        return response()->json([
            'message' => 'Reserva actualizada correctamente',
            'data' => $updatedReserva
        ]);
    }

    #[OA\Delete(
        path: '/api/reservas/{id}',
        summary: 'Eliminar reserva',
        description: 'Eliminar una reserva (soft delete)',
        security: [['sanctum' => []]],
        tags: ['Reservas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reserva eliminada'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(Request $request, Reserva $reserva): JsonResponse
    {
        $this->service->eliminar($reserva);

        return response()->json([
            'message' => 'Reserva eliminada correctamente'
        ]);
    }
}
