<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHorarioRutaRequest;
use App\Http\Requests\UpdateHorarioRutaRequest;
use App\Http\Resources\HorarioRutaResource;
use App\Models\HorarioRuta;
use App\Models\BusUnidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * HorarioRutaController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de horarios de ruta.
 * Reducción: 646 → ~200 líneas (-69%)
 */
class HorarioRutaController extends Controller
{
    #[OA\Get(
        path: '/api/horarios-rutas',
        summary: 'Listar horarios de ruta',
        description: 'Listado paginado de viajes programados con filtros por ruta, bus, estado y fechas',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HorarioRuta::class);

        $query = HorarioRuta::with(['ruta', 'bus'])->where('eliminado', 0);

        if ($request->filled('ruta_id')) {
            $query->where('ruta_id', $request->ruta_id);
        }
        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha')) {
            $query->whereDate('fecha_salida', $request->fecha);
        }
        if ($request->filled(['desde', 'hasta'])) {
            $query->whereBetween('fecha_salida', [$request->desde, $request->hasta]);
        }

        return HorarioRutaResource::collection(
            $query->orderByDesc('fecha_salida')->orderByDesc('hora_salida')->paginate(15)
        );
    }

    #[OA\Post(
        path: '/api/horarios-rutas',
        summary: 'Crear horario de ruta',
        description: 'Programa un nuevo viaje con bus y ruta. Asientos se establecen según capacidad del bus',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function store(StoreHorarioRutaRequest $request): JsonResponse
    {
        $this->authorize('create', HorarioRuta::class);

        try {
            DB::beginTransaction();
            $bus = BusUnidad::findOrFail($request->bus_id);

            $horario = HorarioRuta::create([
                ...$request->validated(),
                'asientos_disponibles' => $bus->capacidad_asientos,
                'estado' => $request->estado ?? 'Programado',
                'activo' => 1
            ]);
            DB::commit();

            return response()->json([
                'data' => HorarioRutaResource::make($horario->load(['ruta', 'bus']))->resolve(),
                'message' => 'Horario creado exitosamente'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear horario', 'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'], 500);
        }
    }

    #[OA\Get(
        path: '/api/horarios-rutas/{id}',
        summary: 'Obtener horario de ruta',
        description: 'Detalle de un horario con ruta, bus y tiquetes asociados',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function show(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['ruta', 'bus', 'tiquetesDetalle'])
            ->findOrFail($id);

        $this->authorize('view', $horario);
        return new HorarioRutaResource($horario);
    }

    #[OA\Put(
        path: '/api/horarios-rutas/{id}',
        summary: 'Actualizar horario de ruta',
        description: 'Actualiza horario. No permite modificar horarios En Viaje o Finalizados',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function update(UpdateHorarioRutaRequest $request, int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);
        $this->authorize('update', $horario);

        if (in_array($horario->estado, ['En Viaje', 'Finalizado'])) {
            return response()->json(['message' => 'No se puede modificar un horario En Viaje o Finalizado'], 422);
        }

        $horario->update($request->validated());

        return response()->json([
            'data' => HorarioRutaResource::make($horario->fresh(['ruta', 'bus']))->resolve(),
            'message' => 'Horario actualizado exitosamente'
        ]);
    }

    #[OA\Delete(
        path: '/api/horarios-rutas/{id}',
        summary: 'Eliminar horario de ruta',
        description: 'Eliminación lógica. No permite eliminar horarios con tiquetes vendidos',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function destroy(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);
        $this->authorize('delete', $horario);

        if ($horario->tiquetesDetalle()->exists()) {
            return response()->json(['message' => 'No se puede eliminar un horario con tiquetes vendidos'], 422);
        }

        $horario->update(['eliminado' => 1, 'activo' => 0]);
        return response()->json(['message' => 'Horario eliminado exitosamente']);
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/iniciar-viaje',
        summary: 'Iniciar viaje',
        description: 'Cambia estado de Programado a En Viaje',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function iniciarViaje(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        if ($horario->estado !== 'Programado') {
            return response()->json(['message' => 'Solo se pueden iniciar viajes Programados'], 422);
        }

        $horario->update(['estado' => 'En Viaje']);

        return response()->json([
            'data' => HorarioRutaResource::make($horario->fresh(['ruta', 'bus']))->resolve(),
            'message' => 'Viaje iniciado exitosamente'
        ]);
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/finalizar-viaje',
        summary: 'Finalizar viaje',
        description: 'Cambia estado de En Viaje a Finalizado',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function finalizarViaje(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        if ($horario->estado !== 'En Viaje') {
            return response()->json(['message' => 'Solo se pueden finalizar viajes En Viaje'], 422);
        }

        $horario->update(['estado' => 'Finalizado']);

        return response()->json([
            'data' => HorarioRutaResource::make($horario->fresh(['ruta', 'bus']))->resolve(),
            'message' => 'Viaje finalizado exitosamente'
        ]);
    }

    #[OA\Post(
        path: '/api/horarios-rutas/{id}/cancelar',
        summary: 'Cancelar horario',
        description: 'Cambia estado a Cancelado. No permite cancelar viajes finalizados',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function cancelar(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)->findOrFail($id);

        if ($horario->estado === 'Finalizado') {
            return response()->json(['message' => 'No se puede cancelar un viaje finalizado'], 422);
        }

        $horario->update(['estado' => 'Cancelado']);

        return response()->json([
            'data' => HorarioRutaResource::make($horario->fresh(['ruta', 'bus']))->resolve(),
            'message' => 'Horario cancelado exitosamente'
        ]);
    }

    #[OA\Get(
        path: '/api/horarios-rutas/{id}/asientos-disponibles',
        summary: 'Asientos disponibles',
        description: 'Consulta disponibilidad de asientos basado en tiquetes vendidos',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function asientosDisponibles(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['bus', 'tiquetesDetalle'])
            ->findOrFail($id);

        $tiquetesVendidos = $horario->tiquetesDetalle()
            ->where('estado', '!=', 'Cancelado')
            ->count();

        $capacidad = $horario->bus->capacidad_asientos;

        return response()->json([
            'horario_id' => $horario->id,
            'capacidad_total' => $capacidad,
            'tiquetes_vendidos' => $tiquetesVendidos,
            'asientos_disponibles' => $capacidad - $tiquetesVendidos,
            'porcentaje_ocupacion' => $capacidad > 0 ? round(($tiquetesVendidos / $capacidad) * 100, 2) : 0
        ]);
    }

    #[OA\Get(
        path: '/api/horarios-rutas/proximos-disponibles',
        summary: 'Próximos horarios disponibles',
        description: 'Los próximos 10 horarios programados con asientos disponibles',
        security: [['sanctum' => []]],
        tags: ['Transporte']
    )]
    public function proximosDisponibles(Request $request): AnonymousResourceCollection
    {
        $query = HorarioRuta::with(['ruta', 'bus'])
            ->where('eliminado', 0)
            ->where('estado', 'Programado')
            ->where('fecha_salida', '>=', now()->toDateString())
            ->where('asientos_disponibles', '>', 0);

        if ($request->filled('ruta_id')) {
            $query->where('ruta_id', $request->ruta_id);
        }

        return HorarioRutaResource::collection(
            $query->orderBy('fecha_salida')->orderBy('hora_salida')->limit(10)->get()
        );
    }
}
