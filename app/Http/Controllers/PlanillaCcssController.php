<?php

namespace App\Http\Controllers;

use App\Models\PlanillaCcss;
use App\Http\Requests\StorePlanillaCcssRequest;
use App\Http\Requests\UpdatePlanillaCcssRequest;
use App\Http\Resources\PlanillaCcssResource;
use Illuminate\Http\Request;

class PlanillaCcssController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanillaCcss::with(['empresa', 'periodoNomina']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo', $request->periodo);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_generacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_generacion', '<=', $request->fecha_hasta);
        }

        if ($request->filled('search')) {
            $query->where('numero_planilla', 'like', "%{$request->search}%");
        }

        $planillas = $query->latest('periodo')->paginate($request->per_page ?? 15);

        return PlanillaCcssResource::collection($planillas);
    }

    public function store(StorePlanillaCcssRequest $request)
    {
        $planilla = PlanillaCcss::create($request->validated());
        $planilla->load(['empresa', 'periodoNomina']);

        return new PlanillaCcssResource($planilla);
    }

    public function show(PlanillaCcss $planillaCcss)
    {
        $planillaCcss->load(['empresa', 'periodoNomina']);

        return new PlanillaCcssResource($planillaCcss);
    }

    public function update(UpdatePlanillaCcssRequest $request, PlanillaCcss $planillaCcss)
    {
        $planillaCcss->update($request->validated());
        $planillaCcss->load(['empresa', 'periodoNomina']);

        return new PlanillaCcssResource($planillaCcss);
    }

    public function destroy(PlanillaCcss $planillaCcss)
    {
        $planillaCcss->delete();

        return response()->json(['message' => 'Planilla CCSS eliminada correctamente'], 200);
    }
}
