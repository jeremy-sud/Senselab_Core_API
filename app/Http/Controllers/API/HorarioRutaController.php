<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioRutaRequest;
use App\Http\Requests\UpdateHorarioRutaRequest;
use App\Http\Resources\HorarioRutaResource;
use App\Models\HorarioRuta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Horarios de Ruta (Viajes Programados)
 *
 * Gestiona la programación de viajes específicos: qué bus, en qué ruta y a qué hora.
 * Incluye control de asientos disponibles y estados del viaje.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class HorarioRutaController extends Controller
{
    /**
     * Listar todos los horarios de ruta
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/horarios-rutas",
        summary: "Listar horarios de ruta",
        description: "Obtiene la lista paginada de horarios de ruta (viajes programados). Permite filtrar por ruta, bus, estado, fecha y rango de fechas.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "ruta_id",
                in: "query",
                description: "Filtrar por ruta",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "bus_id",
                in: "query",
                description: "Filtrar por bus",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado del viaje",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Programado", "En Viaje", "Finalizado", "Cancelado"], example: "Programado")
            ),
            new OA\Parameter(
                name: "fecha",
                in: "query",
                description: "Filtrar por fecha exacta de salida",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-25")
            ),
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha inicio del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de horarios obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HorarioRuta::class);
        
        $query = HorarioRuta::with(['ruta', 'bus'])
            ->where('eliminado', 0);

        // Filtro por ruta
        if ($request->filled('ruta_id')) {
            $query->where('ruta_id', $request->ruta_id);
        }

        // Filtro por bus
        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por fecha
        if ($request->filled('fecha')) {
            $query->whereDate('fecha_salida', $request->fecha);
        }

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_salida', [$request->desde, $request->hasta]);
        }

        $horarios = $query->orderBy('fecha_salida', 'desc')
            ->orderBy('hora_salida', 'desc')
            ->paginate(15);

        return HorarioRutaResource::collection($horarios);
    }

    /**
     * Crear un nuevo horario de ruta
     *
     * @param StoreHorarioRutaRequest $request
     * @return HorarioRutaResource
     */
    #[OA\Post(
        path: "/api/horarios-rutas",
        summary: "Crear horario de ruta",
        description: "Programa un nuevo viaje asignando bus y ruta con fechas/horas de salida y llegada. Los asientos disponibles se establecen automáticamente según la capacidad del bus.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["ruta_id", "bus_id", "fecha_salida", "hora_salida", "fecha_llegada_estimada", "hora_llegada_estimada"],
                properties: [
                    new OA\Property(property: "ruta_id", type: "integer", example: 1),
                    new OA\Property(property: "bus_id", type: "integer", example: 1),
                    new OA\Property(property: "fecha_salida", type: "string", format: "date", example: "2024-12-25"),
                    new OA\Property(property: "hora_salida", type: "string", format: "time", example: "08:00:00"),
                    new OA\Property(property: "fecha_llegada_estimada", type: "string", format: "date", example: "2024-12-25"),
                    new OA\Property(property: "hora_llegada_estimada", type: "string", format: "time", example: "11:00:00"),
                    new OA\Property(property: "estado", type: "string", enum: ["Programado", "En Viaje", "Finalizado", "Cancelado"], example: "Programado", description: "Opcional, por defecto 'Programado'"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1, description: "Opcional, por defecto 1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Horario creado exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 500, description: "Error al crear el horario"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreHorarioRutaRequest $request): HorarioRutaResource
    {
        $this->authorize('create', HorarioRuta::class);
        
        DB::beginTransaction();

        try {
            // Obtener capacidad del bus
            $bus = \App\Models\BusUnidad::findOrFail($request->bus_id);

            $horario = HorarioRuta::create([
                'ruta_id' => $request->ruta_id,
                'bus_id' => $request->bus_id,
                'fecha_salida' => $request->fecha_salida,
                'hora_salida' => $request->hora_salida,
                'fecha_llegada_estimada' => $request->fecha_llegada_estimada,
                'hora_llegada_estimada' => $request->hora_llegada_estimada,
                'asientos_disponibles' => $bus->capacidad_asientos,
                'estado' => $request->estado ?? 'Programado',
                'activo' => $request->activo ?? 1
            ]);

            DB::commit();

            return new HorarioRutaResource($horario->load(['ruta', 'bus']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al crear el horario de ruta: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un horario de ruta específico
     *
     * @param int $id
     * @return HorarioRutaResource
     */
    #[OA\Get(
        path: "/api/horarios-rutas/{id}",
        summary: "Obtener horario de ruta",
        description: "Obtiene el detalle de un horario de ruta con ruta, bus y tiquetes vendidos asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Horario obtenido exitosamente"),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['ruta', 'bus', 'tiquetesDetalle'])
            ->findOrFail($id);
        
        $this->authorize('view', $horario);

        return new HorarioRutaResource($horario);
    }

    /**
     * Actualizar un horario de ruta
     *
     * @param UpdateHorarioRutaRequest $request
     * @param int $id
     * @return HorarioRutaResource
     */
    #[OA\Put(
        path: "/api/horarios-rutas/{id}",
        summary: "Actualizar horario de ruta",
        description: "Actualiza un horario de ruta. No permite modificar horarios en estado 'En Viaje' o 'Finalizado'.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ruta_id", type: "integer", example: 1),
                    new OA\Property(property: "bus_id", type: "integer", example: 1),
                    new OA\Property(property: "fecha_salida", type: "string", format: "date", example: "2024-12-25"),
                    new OA\Property(property: "hora_salida", type: "string", format: "time", example: "08:00:00"),
                    new OA\Property(property: "fecha_llegada_estimada", type: "string", format: "date", example: "2024-12-25"),
                    new OA\Property(property: "hora_llegada_estimada", type: "string", format: "time", example: "11:00:00"),
                    new OA\Property(property: "estado", type: "string", enum: ["Programado", "En Viaje", "Finalizado", "Cancelado"], example: "Programado"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Horario actualizado exitosamente"),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un horario en estado En Viaje o Finalizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateHorarioRutaRequest $request, int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('update', $horario);

        // Validar que no esté en viaje o finalizado
        if (in_array($horario->estado, ['En Viaje', 'Finalizado'])) {
            abort(422, 'No se puede modificar un horario en estado En Viaje o Finalizado');
        }

        $horario->update($request->only([
            'ruta_id',
            'bus_id',
            'fecha_salida',
            'hora_salida',
            'fecha_llegada_estimada',
            'hora_llegada_estimada',
            'estado',
            'activo'
        ]));

        return new HorarioRutaResource($horario->load(['ruta', 'bus']));
    }

    /**
     * Eliminar (soft delete) un horario de ruta
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/horarios-rutas/{id}",
        summary: "Eliminar horario de ruta",
        description: "Elimina lógicamente un horario de ruta. No permite eliminar horarios con tiquetes vendidos.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Horario eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Horario de ruta eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un horario con tiquetes vendidos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('delete', $horario);

        // Validar que no tenga tiquetes vendidos
        if ($horario->tiquetesDetalle()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un horario con tiquetes vendidos'
            ], 422);
        }

        $horario->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Horario de ruta eliminado exitosamente'
        ]);
    }

    /**
     * Iniciar un viaje (cambiar estado a En Viaje)
     *
     * @param int $id
     * @return HorarioRutaResource
     */
    #[OA\Post(
        path: "/api/horarios-rutas/{id}/iniciar-viaje",
        summary: "Iniciar viaje",
        description: "Cambia el estado del horario de 'Programado' a 'En Viaje'. Solo permite iniciar viajes programados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Viaje iniciado exitosamente"),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 422, description: "Solo se pueden iniciar viajes en estado Programado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function iniciarViaje(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);

        if ($horario->estado !== 'Programado') {
            abort(422, 'Solo se pueden iniciar viajes en estado Programado');
        }

        $horario->update(['estado' => 'En Viaje']);

        return new HorarioRutaResource($horario->load(['ruta', 'bus']));
    }

    /**
     * Finalizar un viaje
     *
     * @param int $id
     * @return HorarioRutaResource
     */
    #[OA\Post(
        path: "/api/horarios-rutas/{id}/finalizar-viaje",
        summary: "Finalizar viaje",
        description: "Cambia el estado del horario de 'En Viaje' a 'Finalizado'. Solo permite finalizar viajes en curso.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Viaje finalizado exitosamente"),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 422, description: "Solo se pueden finalizar viajes en estado En Viaje"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function finalizarViaje(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);

        if ($horario->estado !== 'En Viaje') {
            abort(422, 'Solo se pueden finalizar viajes en estado En Viaje');
        }

        $horario->update(['estado' => 'Finalizado']);

        return new HorarioRutaResource($horario->load(['ruta', 'bus']));
    }

    /**
     * Cancelar un horario de ruta
     *
     * @param int $id
     * @return HorarioRutaResource
     */
    #[OA\Post(
        path: "/api/horarios-rutas/{id}/cancelar",
        summary: "Cancelar horario",
        description: "Cambia el estado del horario a 'Cancelado'. No permite cancelar viajes finalizados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Horario cancelado exitosamente"),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 422, description: "No se puede cancelar un viaje finalizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function cancelar(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);

        if ($horario->estado === 'Finalizado') {
            abort(422, 'No se puede cancelar un viaje finalizado');
        }

        $horario->update(['estado' => 'Cancelado']);

        return new HorarioRutaResource($horario->load(['ruta', 'bus']));
    }

    /**
     * Consultar asientos disponibles
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/horarios-rutas/{id}/asientos-disponibles",
        summary: "Asientos disponibles",
        description: "Consulta la disponibilidad de asientos de un horario específico con base en tiquetes vendidos.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del horario de ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Información de disponibilidad obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "horario_id", type: "integer", example: 1),
                        new OA\Property(property: "capacidad_total", type: "integer", example: 48),
                        new OA\Property(property: "tiquetes_vendidos", type: "integer", example: 32),
                        new OA\Property(property: "asientos_disponibles", type: "integer", example: 16),
                        new OA\Property(property: "porcentaje_ocupacion", type: "number", format: "decimal", example: 66.67)
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Horario no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function asientosDisponibles(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['bus', 'tiquetesDetalle'])
            ->findOrFail($id);

        $tiquetesVendidos = $horario->tiquetesDetalle()
            ->where('estado', '!=', 'Cancelado')
            ->count();

        $asientosDisponibles = $horario->bus->capacidad_asientos - $tiquetesVendidos;

        return response()->json([
            'horario_id' => $horario->id,
            'capacidad_total' => $horario->bus->capacidad_asientos,
            'tiquetes_vendidos' => $tiquetesVendidos,
            'asientos_disponibles' => $asientosDisponibles,
            'porcentaje_ocupacion' => $horario->bus->capacidad_asientos > 0 
                ? round(($tiquetesVendidos / $horario->bus->capacidad_asientos) * 100, 2)
                : 0
        ]);
    }

    /**
     * Listar próximos horarios disponibles
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/horarios-rutas/proximos-disponibles",
        summary: "Próximos horarios disponibles",
        description: "Obtiene los próximos 10 horarios programados con asientos disponibles, ordenados por fecha/hora de salida.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "ruta_id",
                in: "query",
                description: "Filtrar por ruta específica (opcional)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de próximos horarios obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function proximosDisponibles(Request $request): AnonymousResourceCollection
    {
        $query = HorarioRuta::with(['ruta', 'bus'])
            ->where('eliminado', 0)
            ->where('estado', 'Programado')
            ->where('fecha_salida', '>=', now()->toDateString())
            ->where('asientos_disponibles', '>', 0);

        // Filtro opcional por ruta
        if ($request->filled('ruta_id')) {
            $query->where('ruta_id', $request->ruta_id);
        }

        $horarios = $query->orderBy('fecha_salida')
            ->orderBy('hora_salida')
            ->limit(10)
            ->get();

        return HorarioRutaResource::collection($horarios);
    }
}
