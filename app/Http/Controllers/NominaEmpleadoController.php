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
        $query = NominaEmpleado::where('eliminado', 0);

        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }

        $query->orderBy('created_at', 'desc');

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
        $data = $request->validated();
        
        $nomina = new NominaEmpleado($data);
        $nomina->calcularTotales();
        $nomina->save();

        return response()->json([
            'success' => true,
            'message' => 'Registro de nómina creado exitosamente',
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
        $nominaEmpleado->fill($request->validated());
        $nominaEmpleado->calcularTotales();
        $nominaEmpleado->save();

        return response()->json([
            'success' => true,
            'message' => 'Registro de nómina actualizado exitosamente',
            'data' => new NominaEmpleadoResource($nominaEmpleado)
        ]);
    }

    public function destroy(NominaEmpleado $nominaEmpleado): JsonResponse
    {
        $nominaEmpleado->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Registro de nómina eliminado exitosamente'
        ]);
    }

    public function porPeriodo(int $periodoId): JsonResponse
    {
        $nominas = NominaEmpleado::where('periodo_nomina_id', $periodoId)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => NominaEmpleadoResource::collection($nominas),
            'resumen' => [
                'total_empleados' => $nominas->count(),
                'total_devengado' => $nominas->sum('total_devengado'),
                'total_deducciones' => $nominas->sum('total_deducciones'),
                'total_neto' => $nominas->sum('salario_neto')
            ]
        ]);
    }

    public function porEmpleado(int $empleadoId): JsonResponse
    {
        $nominas = NominaEmpleado::where('empleado_id', $empleadoId)
            ->where('eliminado', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => NominaEmpleadoResource::collection($nominas)
        ]);
    }

    public function resumenGeneral(Request $request): JsonResponse
    {
        $query = NominaEmpleado::where('eliminado', 0);

        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        $nominas = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_empleados' => $nominas->count(),
                'total_salario_bruto' => $nominas->sum('salario_bruto'),
                'total_horas_extras' => $nominas->sum('monto_horas_extras'),
                'total_bonificaciones' => $nominas->sum('bonificaciones'),
                'total_devengado' => $nominas->sum('total_devengado'),
                'total_ccss' => $nominas->sum('deducciones_ccss'),
                'total_renta' => $nominas->sum('deducciones_impuesto_renta'),
                'total_otras_deducciones' => $nominas->sum('otras_deducciones'),
                'total_deducciones' => $nominas->sum('total_deducciones'),
                'total_neto_pagar' => $nominas->sum('salario_neto')
            ]
        ]);
    }
}
