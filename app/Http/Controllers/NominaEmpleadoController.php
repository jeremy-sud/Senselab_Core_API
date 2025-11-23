<?php

namespace App\Http\Controllers;

use App\Models\NominaEmpleado;
use App\Http\Requests\StoreNominaEmpleadoRequest;
use App\Http\Requests\UpdateNominaEmpleadoRequest;
use App\Http\Resources\NominaEmpleadoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NominaEmpleadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Multi-tenancy: Filtrar por empresa del usuario autenticado
        $empresaId = $request->user()->empresa_id;
        
        $query = NominaEmpleado::where('eliminado', 0)
            ->with(['empleado', 'periodoNomina'])
            ->whereHas('empleado', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }

        $query->orderBy('creado_en', 'desc');

        $nominas = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => NominaEmpleadoResource::collection($nominas),
            'meta' => [
                'current_page' => $nominas->currentPage(),
                'last_page' => $nominas->lastPage(),
                'per_page' => $nominas->perPage(),
                'total' => $nominas->total(),
            ]
        ]);
    }

    public function store(StoreNominaEmpleadoRequest $request): JsonResponse
    {
        $nomina = NominaEmpleado::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Nómina de empleado creada exitosamente',
            'data' => new NominaEmpleadoResource($nomina)
        ], 201);
    }

    public function show(NominaEmpleado $nominaEmpleado): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new NominaEmpleadoResource($nominaEmpleado)
        ]);
    }

    public function update(UpdateNominaEmpleadoRequest $request, NominaEmpleado $nominaEmpleado): JsonResponse
    {
        $nominaEmpleado->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Nómina de empleado actualizada exitosamente',
            'data' => new NominaEmpleadoResource($nominaEmpleado)
        ]);
    }

    public function destroy(NominaEmpleado $nominaEmpleado): JsonResponse
    {
        $nominaEmpleado->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Nómina de empleado eliminada exitosamente'
        ]);
    }

    public function porPeriodo(int $periodoId): JsonResponse
    {
        $nominas = NominaEmpleado::where('periodo_nomina_id', $periodoId)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => NominaEmpleadoResource::collection($nominas)
        ]);
    }

    public function porEmpleado(int $empleadoId): JsonResponse
    {
        $nominas = NominaEmpleado::where('empleado_id', $empleadoId)
            ->where('eliminado', 0)
            ->orderBy('creado_en', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => NominaEmpleadoResource::collection($nominas)
        ]);
    }

    public function resumenPorPeriodo(int $periodoId): JsonResponse
    {
        $resumen = NominaEmpleado::where('periodo_nomina_id', $periodoId)
            ->where('eliminado', 0)
            ->selectRaw('count(*) as total_empleados, sum(salario_bruto) as total_bruto, sum(total_deducciones) as total_deducciones, sum(salario_neto) as total_neto')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
