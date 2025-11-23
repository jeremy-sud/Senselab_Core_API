<?php

namespace App\Http\Controllers;

use App\Models\RetencionImpuesto;
use App\Http\Requests\StoreRetencionImpuestoRequest;
use App\Http\Requests\UpdateRetencionImpuestoRequest;
use App\Http\Resources\RetencionImpuestoResource;
use Illuminate\Http\Request;

class RetencionImpuestoController extends Controller
{
    public function index(Request $request)
    {
        $query = RetencionImpuesto::with(['empresa', 'proveedor']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('tipo_retencion')) {
            $query->where('tipo_retencion', $request->tipo_retencion);
        }

        if ($request->filled('declarado')) {
            $query->where('declarado', $request->declarado);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo_declaracion', $request->periodo);
        }

        if ($request->filled('search')) {
            $query->where('numero_comprobante', 'like', "%{$request->search}%");
        }

        $retenciones = $query->latest('fecha_retencion')->paginate($request->per_page ?? 15);

        return RetencionImpuestoResource::collection($retenciones);
    }

    public function store(StoreRetencionImpuestoRequest $request)
    {
        $retencion = RetencionImpuesto::create($request->validated());
        $retencion->load(['empresa', 'proveedor']);

        return new RetencionImpuestoResource($retencion);
    }

    public function show(RetencionImpuesto $retencionImpuesto)
    {
        $retencionImpuesto->load(['empresa', 'proveedor']);

        return new RetencionImpuestoResource($retencionImpuesto);
    }

    public function update(UpdateRetencionImpuestoRequest $request, RetencionImpuesto $retencionImpuesto)
    {
        $retencionImpuesto->update($request->validated());
        $retencionImpuesto->load(['empresa', 'proveedor']);

        return new RetencionImpuestoResource($retencionImpuesto);
    }

    public function destroy(RetencionImpuesto $retencionImpuesto)
    {
        $retencionImpuesto->delete();

        return response()->json(['message' => 'Retención eliminada correctamente'], 200);
    }
}
