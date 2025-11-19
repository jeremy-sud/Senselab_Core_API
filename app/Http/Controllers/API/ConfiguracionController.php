<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConfiguracionRequest;
use App\Http\Requests\UpdateConfiguracionRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para Configuraciones del Sistema
 * 
 * Gestiona configuraciones clave-valor por empresa (moneda, idioma, tasas, etc.).
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ConfiguracionController extends Controller
{
    /**
     * Listar configuraciones de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuraciones = Configuracion::where('empresa_id', $empresaId)
            ->orderBy('clave', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ConfiguracionResource::collection($configuraciones)
        ]);
    }

    /**
     * Crear nueva configuración
     */
    public function store(StoreConfiguracionRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::create([
            'empresa_id' => $empresaId,
            'clave' => $request->clave,
            'valor' => $request->valor,
            'tipo_dato' => $request->tipo_dato,
            'descripcion' => $request->descripcion
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración creada exitosamente',
            'data' => new ConfiguracionResource($configuracion)
        ], 201);
    }

    /**
     * Mostrar configuración específica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ConfiguracionResource($configuracion)
        ]);
    }

    /**
     * Actualizar configuración
     */
    public function update(UpdateConfiguracionRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        $configuracion->update($request->only([
            'clave',
            'valor',
            'tipo_dato',
            'descripcion'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
            'data' => new ConfiguracionResource($configuracion)
        ]);
    }

    /**
     * Eliminar configuración
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        $configuracion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuración eliminada exitosamente'
        ]);
    }

    /**
     * Obtener configuración por clave
     */
    public function porClave(Request $request, string $clave): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->first();

        if (!$configuracion) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ConfiguracionResource($configuracion)
        ]);
    }

    /**
     * Obtener valor de configuración por clave
     */
    public function obtenerValor(Request $request, string $clave): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $configuracion = Configuracion::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->first();

        if (!$configuracion) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada',
                'valor' => null
            ], 404);
        }

        // Convertir valor según tipo_dato
        $valor = $configuracion->valor;
        switch ($configuracion->tipo_dato) {
            case 'numero':
                $valor = is_numeric($valor) ? (float) $valor : $valor;
                break;
            case 'booleano':
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'json':
                $valor = json_decode($valor, true);
                break;
        }

        return response()->json([
            'success' => true,
            'clave' => $configuracion->clave,
            'valor' => $valor,
            'tipo_dato' => $configuracion->tipo_dato
        ]);
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function actualizarMultiples(Request $request): JsonResponse
    {
        $request->validate([
            'configuraciones' => 'required|array|min:1',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required|string'
        ]);

        $empresaId = $request->user()->empresa_id;
        $actualizadas = 0;

        foreach ($request->configuraciones as $config) {
            $configuracion = Configuracion::where('empresa_id', $empresaId)
                ->where('clave', $config['clave'])
                ->first();

            if ($configuracion) {
                $configuracion->update(['valor' => $config['valor']]);
                $actualizadas++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se actualizaron {$actualizadas} configuraciones exitosamente",
            'total_actualizadas' => $actualizadas
        ]);
    }
}
