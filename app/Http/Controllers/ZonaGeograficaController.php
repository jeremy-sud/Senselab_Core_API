<?php

namespace App\Http\Controllers;

use App\Models\ZonaGeografica;
use App\Http\Requests\StoreZonaGeograficaRequest;
use App\Http\Requests\UpdateZonaGeograficaRequest;
use App\Http\Resources\ZonaGeograficaResource;
use Illuminate\Http\Request;

class ZonaGeograficaController extends Controller
{
    public function index(Request $request)
    {
        $query = ZonaGeografica::with(['empresa', 'zonaPadre', 'vendedorAsignado']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        if ($request->filled('zona_padre_id')) {
            $query->where('zona_padre_id', $request->zona_padre_id);
        }

        if ($request->filled('vendedor_id')) {
            $query->where('vendedor_asignado_id', $request->vendedor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        $zonas = $query->orderBy('codigo')->paginate($request->per_page ?? 15);

        return ZonaGeograficaResource::collection($zonas);
    }

    public function store(StoreZonaGeograficaRequest $request)
    {
        $zona = ZonaGeografica::create($request->validated());
        $zona->load(['empresa', 'zonaPadre', 'vendedorAsignado']);

        return new ZonaGeograficaResource($zona);
    }

    public function show(ZonaGeografica $zonaGeografica)
    {
        $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);

        return new ZonaGeograficaResource($zonaGeografica);
    }

    public function update(UpdateZonaGeograficaRequest $request, ZonaGeografica $zonaGeografica)
    {
        $zonaGeografica->update($request->validated());
        $zonaGeografica->load(['empresa', 'zonaPadre', 'vendedorAsignado']);

        return new ZonaGeograficaResource($zonaGeografica);
    }

    public function destroy(ZonaGeografica $zonaGeografica)
    {
        $zonaGeografica->delete();

        return response()->json(['message' => 'Zona geográfica eliminada correctamente'], 200);
    }
}
