<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTiqueteDetalleRequest;
use App\Http\Requests\UpdateTiqueteDetalleRequest;
use App\Http\Resources\TiqueteDetalleResource;
use App\Models\TiqueteDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Tiquetes de Transporte
 *
 * Gestiona los tiquetes vendidos con asiento asignado y datos del pasajero.
 * Vinculado a detalle de venta y horario de ruta.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TiqueteDetalleController extends Controller
{
    /**
     * Listar todos los tiquetes
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TiqueteDetalle::with(['horarioRuta.ruta', 'horarioRuta.bus', 'detalleVenta'])
            ->where('eliminado', 0);

        // Filtro por horario de ruta
        if ($request->filled('horario_ruta_id')) {
            $query->where('horario_ruta_id', $request->horario_ruta_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Búsqueda por pasajero
        if ($request->filled('buscar_pasajero')) {
            $buscar = $request->buscar_pasajero;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_pasajero', 'like', "%{$buscar}%")
                  ->orWhere('identificacion_pasajero', 'like', "%{$buscar}%");
            });
        }

        $tiquetes = $query->orderBy('created_at', 'desc')->paginate(15);

        return TiqueteDetalleResource::collection($tiquetes);
    }

    /**
     * Crear un nuevo tiquete
     *
     * @param StoreTiqueteDetalleRequest $request
     * @return TiqueteDetalleResource
     */
    public function store(StoreTiqueteDetalleRequest $request): TiqueteDetalleResource
    {
        DB::beginTransaction();

        try {
            $tiquete = TiqueteDetalle::create([
                'detalle_venta_id' => $request->detalle_venta_id,
                'horario_ruta_id' => $request->horario_ruta_id,
                'asiento_numero' => $request->asiento_numero,
                'nombre_pasajero' => $request->nombre_pasajero,
                'identificacion_pasajero' => $request->identificacion_pasajero,
                'precio_final_tiquete' => $request->precio_final_tiquete,
                'estado' => $request->estado ?? 'Vendido',
                'activo' => $request->activo ?? 1
            ]);

            // Actualizar asientos disponibles en el horario
            $horario = \App\Models\HorarioRuta::findOrFail($request->horario_ruta_id);
            $horario->decrement('asientos_disponibles');

            DB::commit();

            return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al crear el tiquete: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un tiquete específico
     *
     * @param int $id
     * @return TiqueteDetalleResource
     */
    public function show(int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->with(['horarioRuta.ruta', 'horarioRuta.bus', 'detalleVenta'])
            ->findOrFail($id);

        return new TiqueteDetalleResource($tiquete);
    }

    /**
     * Actualizar un tiquete
     *
     * @param UpdateTiqueteDetalleRequest $request
     * @param int $id
     * @return TiqueteDetalleResource
     */
    public function update(UpdateTiqueteDetalleRequest $request, int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no esté usado
        if ($tiquete->estado === 'Usado') {
            abort(422, 'No se puede modificar un tiquete que ya ha sido usado');
        }

        $tiquete->update($request->only([
            'nombre_pasajero',
            'identificacion_pasajero',
            'estado',
            'activo'
        ]));

        return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));
    }

    /**
     * Cancelar un tiquete
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancelar(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tiquete = TiqueteDetalle::where('eliminado', 0)
                ->findOrFail($id);

            if ($tiquete->estado === 'Usado') {
                return response()->json([
                    'message' => 'No se puede cancelar un tiquete que ya ha sido usado'
                ], 422);
            }

            $tiquete->update(['estado' => 'Cancelado']);

            // Liberar el asiento en el horario
            $horario = $tiquete->horarioRuta;
            $horario->increment('asientos_disponibles');

            DB::commit();

            return response()->json([
                'message' => 'Tiquete cancelado exitosamente',
                'tiquete' => new TiqueteDetalleResource($tiquete)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cancelar el tiquete',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar tiquete como usado
     *
     * @param int $id
     * @return TiqueteDetalleResource
     */
    public function marcarUsado(int $id): TiqueteDetalleResource
    {
        $tiquete = TiqueteDetalle::where('eliminado', 0)
            ->findOrFail($id);

        if ($tiquete->estado !== 'Vendido') {
            abort(422, 'Solo se pueden marcar como usados los tiquetes en estado Vendido');
        }

        $tiquete->update(['estado' => 'Usado']);

        return new TiqueteDetalleResource($tiquete->load(['horarioRuta.ruta', 'horarioRuta.bus']));
    }

    /**
     * Listar tiquetes por horario de ruta
     *
     * @param int $horarioRutaId
     * @return AnonymousResourceCollection
     */
    public function porHorarioRuta(int $horarioRutaId): AnonymousResourceCollection
    {
        $tiquetes = TiqueteDetalle::where('horario_ruta_id', $horarioRutaId)
            ->where('eliminado', 0)
            ->with(['horarioRuta.ruta', 'horarioRuta.bus'])
            ->orderBy('asiento_numero')
            ->get();

        return TiqueteDetalleResource::collection($tiquetes);
    }

    /**
     * Mapa de asientos ocupados y disponibles
     *
     * @param int $horarioRutaId
     * @return JsonResponse
     */
    public function mapaAsientos(int $horarioRutaId): JsonResponse
    {
        $horario = \App\Models\HorarioRuta::with(['bus', 'tiquetesDetalle'])
            ->findOrFail($horarioRutaId);

        $asientosOcupados = $horario->tiquetesDetalle()
            ->where('estado', '!=', 'Cancelado')
            ->pluck('asiento_numero')
            ->toArray();

        return response()->json([
            'horario_id' => $horario->id,
            'capacidad_total' => $horario->bus->capacidad_asientos,
            'asientos_ocupados' => $asientosOcupados,
            'total_ocupados' => count($asientosOcupados),
            'total_disponibles' => $horario->asientos_disponibles
        ]);
    }
}
