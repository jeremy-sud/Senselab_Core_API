<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusUnidadRequest;
use App\Http\Requests\UpdateBusUnidadRequest;
use App\Http\Resources\BusUnidadResource;
use App\Models\BusUnidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Buses/Unidades de Transporte
 *
 * Gestiona las unidades físicas de transporte (buses) con placa, modelo y capacidad.
 * Incluye consultas de disponibilidad y estado de flota.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class BusUnidadController extends Controller
{
    /**
     * Listar todos los buses de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'modelo']);

        // Filtro por modelo
        if ($request->filled('modelo_id')) {
            $query->where('modelo_id', $request->modelo_id);
        }

        // Filtro por activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda por placa o identificador
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('placa', 'like', "%{$buscar}%")
                  ->orWhere('identificador_interno', 'like', "%{$buscar}%");
            });
        }

        $buses = $query->orderBy('identificador_interno')->paginate(15);

        return BusUnidadResource::collection($buses);
    }

    /**
     * Crear un nuevo bus
     *
     * @param StoreBusUnidadRequest $request
     * @return BusUnidadResource
     */
    public function store(StoreBusUnidadRequest $request): BusUnidadResource
    {
        $empresaId = $request->user()->empresa_id;

        $bus = BusUnidad::create([
            'empresa_id' => $empresaId,
            'placa' => $request->placa,
            'modelo_id' => $request->modelo_id,
            'capacidad_asientos' => $request->capacidad_asientos,
            'identificador_interno' => $request->identificador_interno,
            'activo' => $request->activo ?? 1
        ]);

        return new BusUnidadResource($bus->load(['empresa', 'modelo']));
    }

    /**
     * Mostrar un bus específico
     *
     * @param int $id
     * @param Request $request
     * @return BusUnidadResource
     */
    public function show(int $id, Request $request): BusUnidadResource
    {
        $empresaId = $request->user()->empresa_id;

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'modelo', 'horariosRuta'])
            ->findOrFail($id);

        return new BusUnidadResource($bus);
    }

    /**
     * Actualizar un bus
     *
     * @param UpdateBusUnidadRequest $request
     * @param int $id
     * @return BusUnidadResource
     */
    public function update(UpdateBusUnidadRequest $request, int $id): BusUnidadResource
    {
        $empresaId = $request->user()->empresa_id;

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $bus->update($request->only([
            'placa',
            'modelo_id',
            'capacidad_asientos',
            'identificador_interno',
            'activo'
        ]));

        return new BusUnidadResource($bus->load(['empresa', 'modelo']));
    }

    /**
     * Eliminar (soft delete) un bus
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $bus = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no tenga horarios activos asignados
        if ($bus->horariosRuta()->where('estado', '!=', 'Finalizado')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un bus con horarios de ruta activos'
            ], 422);
        }

        $bus->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Bus eliminado exitosamente'
        ]);
    }

    /**
     * Listar buses disponibles (activos sin horarios en curso)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function disponibles(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $buses = BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->with(['modelo'])
            ->whereDoesntHave('horariosRuta', function ($query) {
                $query->where('estado', 'En Viaje');
            })
            ->select('id', 'placa', 'modelo_id', 'capacidad_asientos', 'identificador_interno')
            ->get();

        return response()->json($buses);
    }

    /**
     * Obtener resumen de la flota
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resumenFlota(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = [
            'total_buses' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->count(),
            'buses_activos' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->where('activo', 1)
                ->count(),
            'buses_en_viaje' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->whereHas('horariosRuta', function ($query) {
                    $query->where('estado', 'En Viaje');
                })
                ->count(),
            'capacidad_total' => BusUnidad::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->where('activo', 1)
                ->sum('capacidad_asientos')
        ];

        return response()->json($resumen);
    }

    /**
     * Listar buses por modelo
     *
     * @param int $modeloId
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function porModelo(int $modeloId, Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;

        $buses = BusUnidad::where('empresa_id', $empresaId)
            ->where('modelo_id', $modeloId)
            ->where('eliminado', 0)
            ->with(['modelo'])
            ->get();

        return BusUnidadResource::collection($buses);
    }
}
