<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModeloBusRequest;
use App\Http\Requests\UpdateModeloBusRequest;
use App\Http\Resources\ModeloBusResource;
use App\Models\ModeloBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Modelos de Buses
 *
 * Gestiona el catálogo de modelos de buses (tabla global, no multi-tenant).
 * Ej: Paradiso 1800 DD, Viaggio 1050
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ModeloBusController extends Controller
{
    /**
     * Listar todos los modelos de buses
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $modelos = ModeloBus::withCount('busesUnidades')
            ->orderBy('nombre')
            ->paginate(20);

        return ModeloBusResource::collection($modelos);
    }

    /**
     * Crear un nuevo modelo de bus
     *
     * @param StoreModeloBusRequest $request
     * @return ModeloBusResource
     */
    public function store(StoreModeloBusRequest $request): ModeloBusResource
    {
        $modelo = ModeloBus::create([
            'nombre' => $request->nombre
        ]);

        return new ModeloBusResource($modelo);
    }

    /**
     * Mostrar un modelo de bus específico
     *
     * @param int $id
     * @return ModeloBusResource
     */
    public function show(int $id): ModeloBusResource
    {
        $modelo = ModeloBus::withCount('busesUnidades')
            ->findOrFail($id);

        return new ModeloBusResource($modelo);
    }

    /**
     * Actualizar un modelo de bus
     *
     * @param UpdateModeloBusRequest $request
     * @param int $id
     * @return ModeloBusResource
     */
    public function update(UpdateModeloBusRequest $request, int $id): ModeloBusResource
    {
        $modelo = ModeloBus::findOrFail($id);

        $modelo->update([
            'nombre' => $request->nombre
        ]);

        return new ModeloBusResource($modelo);
    }

    /**
     * Eliminar un modelo de bus
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $modelo = ModeloBus::findOrFail($id);

        // Validar que no tenga buses asociados
        if ($modelo->busesUnidades()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un modelo con buses asociados'
            ], 422);
        }

        $modelo->delete();

        return response()->json([
            'message' => 'Modelo de bus eliminado exitosamente'
        ]);
    }

    /**
     * Listar modelos activos para formularios
     *
     * @return JsonResponse
     */
    public function activos(): JsonResponse
    {
        $modelos = ModeloBus::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json($modelos);
    }
}
