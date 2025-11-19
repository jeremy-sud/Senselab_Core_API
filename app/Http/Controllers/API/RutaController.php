<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRutaRequest;
use App\Http\Requests\UpdateRutaRequest;
use App\Http\Resources\RutaResource;
use App\Models\Ruta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Rutas de Transporte
 *
 * Gestiona las rutas de transporte definiendo trayectos (origen-destino).
 * Incluye distancia, duración estimada y tarifa base.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class RutaController extends Controller
{
    /**
     * Listar todas las rutas de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa']);

        // Filtro por origen o destino
        if ($request->filled('origen')) {
            $query->where('origen', 'like', '%' . $request->origen . '%');
        }

        if ($request->filled('destino')) {
            $query->where('destino', 'like', '%' . $request->destino . '%');
        }

        // Filtro por activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        $rutas = $query->orderBy('nombre')->paginate(15);

        return RutaResource::collection($rutas);
    }

    /**
     * Crear una nueva ruta
     *
     * @param StoreRutaRequest $request
     * @return RutaResource
     */
    public function store(StoreRutaRequest $request): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::create([
            'empresa_id' => $empresaId,
            'nombre' => $request->nombre,
            'origen' => $request->origen,
            'destino' => $request->destino,
            'distancia_km' => $request->distancia_km,
            'duracion_estimada' => $request->duracion_estimada,
            'tarifa_base' => $request->tarifa_base,
            'observaciones' => $request->observaciones,
            'activo' => $request->activo ?? 1
        ]);

        return new RutaResource($ruta->load(['empresa']));
    }

    /**
     * Mostrar una ruta específica
     *
     * @param int $id
     * @param Request $request
     * @return RutaResource
     */
    public function show(int $id, Request $request): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'horariosRuta'])
            ->findOrFail($id);

        return new RutaResource($ruta);
    }

    /**
     * Actualizar una ruta
     *
     * @param UpdateRutaRequest $request
     * @param int $id
     * @return RutaResource
     */
    public function update(UpdateRutaRequest $request, int $id): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $ruta->update($request->only([
            'nombre',
            'origen',
            'destino',
            'distancia_km',
            'duracion_estimada',
            'tarifa_base',
            'observaciones',
            'activo'
        ]));

        return new RutaResource($ruta->load(['empresa']));
    }

    /**
     * Eliminar (soft delete) una ruta
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no tenga horarios activos
        if ($ruta->horariosRuta()->where('estado', '!=', 'Finalizado')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una ruta con horarios activos'
            ], 422);
        }

        $ruta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Ruta eliminada exitosamente'
        ]);
    }

    /**
     * Listar rutas activas para selección
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $rutas = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre', 'origen', 'destino', 'tarifa_base', 'duracion_estimada')
            ->orderBy('nombre')
            ->get();

        return response()->json($rutas);
    }

    /**
     * Calcular tarifa estimada con parámetros adicionales
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function calcularTarifa(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $cantidadPasajeros = $request->input('cantidad_pasajeros', 1);
        $descuento = $request->input('descuento_porcentaje', 0);

        $tarifaBase = $ruta->tarifa_base * $cantidadPasajeros;
        $montoDescuento = ($tarifaBase * $descuento) / 100;
        $tarifaFinal = $tarifaBase - $montoDescuento;

        return response()->json([
            'ruta' => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'origen' => $ruta->origen,
                'destino' => $ruta->destino
            ],
            'calculo' => [
                'tarifa_base_unitaria' => number_format($ruta->tarifa_base, 2),
                'cantidad_pasajeros' => $cantidadPasajeros,
                'subtotal' => number_format($tarifaBase, 2),
                'descuento_porcentaje' => $descuento,
                'monto_descuento' => number_format($montoDescuento, 2),
                'tarifa_final' => number_format($tarifaFinal, 2)
            ]
        ]);
    }

    /**
     * Obtener estadísticas de una ruta
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticas(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['horariosRuta'])
            ->findOrFail($id);

        $totalViajes = $ruta->horariosRuta()->count();
        $viajesFinalizados = $ruta->horariosRuta()->where('estado', 'Finalizado')->count();
        $viajesEnCurso = $ruta->horariosRuta()->where('estado', 'En Viaje')->count();
        $viajesProgramados = $ruta->horariosRuta()->where('estado', 'Programado')->count();

        return response()->json([
            'ruta' => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'origen' => $ruta->origen,
                'destino' => $ruta->destino,
                'distancia_km' => $ruta->distancia_km
            ],
            'estadisticas' => [
                'total_viajes' => $totalViajes,
                'finalizados' => $viajesFinalizados,
                'en_curso' => $viajesEnCurso,
                'programados' => $viajesProgramados
            ]
        ]);
    }
}
