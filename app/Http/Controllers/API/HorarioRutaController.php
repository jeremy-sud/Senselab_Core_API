<?php

namespace App\Http\Controllers\API;

use App\DTOs\API\HorarioRutaCreateDTO;
use App\DTOs\API\HorarioRutaUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioRutaRequest;
use App\Http\Requests\UpdateHorarioRutaRequest;
use App\Http\Resources\HorarioRutaResource;
use App\Models\HorarioRuta;
use App\Services\HorarioRutaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * HorarioRutaController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de horarios de ruta.
 * Reducción: 646 → ~200 líneas (-69%)
 */
class HorarioRutaController extends Controller
{
    public function __construct(
        private readonly HorarioRutaService $service
    ) {}
    #[OA\Get(
        path: '/api/horarios-rutas',
        summary: 'Listar horarios de ruta',
        description: 'Listado paginado de viajes programados con filtros por ruta, bus, estado y fechas',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HorarioRuta::class);

        $filtros = array_filter([
            'ruta_id' => $request->get('ruta_id'),
            'bus_id' => $request->get('bus_id'),
            'estado' => $request->get('estado'),
            'fecha' => $request->get('fecha'),
            'desde' => $request->get('desde'),
            'hasta' => $request->get('hasta'),
        ], fn ($v) => $v !== null);

        return HorarioRutaResource::collection($this->service->listar($filtros));
    }

    #[OA\Post(
        path: '/api/horarios-rutas',
        summary: 'Crear horario de ruta',
        description: 'Programa un nuevo viaje con bus y ruta. Asientos se establecen según capacidad del bus',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreHorarioRutaRequest $request): JsonResponse
    {
        $this->authorize('create', HorarioRuta::class);

        $horario = $this->service->crear(HorarioRutaCreateDTO::fromRequest($request)->toArray());

        return response()->json([
            'data' => HorarioRutaResource::make($horario)->resolve(),
            'message' => 'Horario creado exitosamente',
        ], 201);
    }

    #[OA\Get(
        path: '/api/horarios-rutas/{id}',
        summary: 'Obtener horario de ruta',
        description: 'Detalle de un horario con ruta, bus y tiquetes asociados',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): HorarioRutaResource
    {
        $horario = $this->service->obtener($id);

        $this->authorize('view', $horario);

        return new HorarioRutaResource($horario);
    }

    #[OA\Put(
        path: '/api/horarios-rutas/{id}',
        summary: 'Actualizar horario de ruta',
        description: 'Actualiza horario. No permite modificar horarios En Viaje o Finalizados',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateHorarioRutaRequest $request, int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);
        $this->authorize('update', $horario);

        $horario = $this->service->actualizar($horario, HorarioRutaUpdateDTO::fromRequest($request)->toArray());

        return response()->json([
            'data' => HorarioRutaResource::make($horario)->resolve(),
            'message' => 'Horario actualizado exitosamente',
        ]);
    }

    #[OA\Delete(
        path: '/api/horarios-rutas/{id}',
        summary: 'Eliminar horario de ruta',
        description: 'Eliminación lógica. No permite eliminar horarios con tiquetes vendidos',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);
        $this->authorize('delete', $horario);

        $this->service->eliminar($horario);

        return $this->deletedResponse('Horario eliminado exitosamente');
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/iniciar-viaje',
        summary: 'Iniciar viaje',
        description: 'Cambia estado de Programado a En Viaje',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function iniciarViaje(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        $horario = $this->service->iniciarViaje($horario);

        return response()->json([
            'data' => HorarioRutaResource::make($horario)->resolve(),
            'message' => 'Viaje iniciado exitosamente',
        ]);
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/finalizar-viaje',
        summary: 'Finalizar viaje',
        description: 'Cambia estado de En Viaje a Finalizado',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function finalizarViaje(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        $horario = $this->service->finalizarViaje($horario);

        return response()->json([
            'data' => HorarioRutaResource::make($horario)->resolve(),
            'message' => 'Viaje finalizado exitosamente',
        ]);
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/cancelar',
        summary: 'Cancelar horario',
        description: 'Cambia estado a Cancelado. No permite cancelar viajes finalizados',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function cancelar(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        $horario = $this->service->cancelar($horario);

        return response()->json([
            'data' => HorarioRutaResource::make($horario)->resolve(),
            'message' => 'Horario cancelado exitosamente',
        ]);
    }

    #[OA\Get(
        path: '/api/horarios-rutas/{id}/asientos-disponibles',
        summary: 'Asientos disponibles',
        description: 'Consulta disponibilidad de asientos basado en tiquetes vendidos',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function asientosDisponibles(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['bus', 'tiquetesDetalle'])
            ->findOrFail($id);

        return response()->json($this->service->asientosDisponibles($horario));
    }

    #[OA\Get(
        path: '/api/horarios-rutas/proximos-disponibles',
        summary: 'Próximos horarios disponibles',
        description: 'Los próximos 10 horarios programados con asientos disponibles',
        security: [['sanctum' => []]],
        tags: ['Transporte'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function proximosDisponibles(Request $request): AnonymousResourceCollection
    {
        $horarios = $this->service->proximosDisponibles(
            $request->filled('ruta_id') ? (int) $request->ruta_id : null
        );

        return HorarioRutaResource::collection($horarios);
    }
}
