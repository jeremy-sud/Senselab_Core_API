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
    public function index(Request $request): AnonymousResourceCollection
    {
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
    public function store(StoreHorarioRutaRequest $request): HorarioRutaResource
    {
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
    public function show(int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->with(['ruta', 'bus', 'tiquetesDetalle'])
            ->findOrFail($id);

        return new HorarioRutaResource($horario);
    }

    /**
     * Actualizar un horario de ruta
     *
     * @param UpdateHorarioRutaRequest $request
     * @param int $id
     * @return HorarioRutaResource
     */
    public function update(UpdateHorarioRutaRequest $request, int $id): HorarioRutaResource
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);

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
    public function destroy(int $id): JsonResponse
    {
        $horario = HorarioRuta::where('eliminado', 0)
            ->findOrFail($id);

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
