<?php

namespace App\Http\Controllers;

use App\Models\TipoComprobanteFe;
use App\Http\Requests\StoreTipoComprobanteFeRequest;
use App\Http\Requests\UpdateTipoComprobanteFeRequest;
use App\Http\Resources\TipoComprobanteFeResource;
use Illuminate\Http\Request;

class TipoComprobanteFeController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoComprobanteFe::query();

        // Filtro por activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Filtro específico por código DGT
        if ($request->filled('codigo_dgt')) {
            $query->where('codigo_dgt', $request->codigo_dgt);
        }

        // Búsqueda por código o nombre
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_dgt', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        // Filtro por requiere referencia
        if ($request->filled('requiere_referencia')) {
            $query->where('requiere_referencia', $request->boolean('requiere_referencia'));
        }

        // Filtro por permite exportación
        if ($request->boolean('permite_exportacion')) {
            $query->where('permite_exportacion', true);
        }

        $tipos = $query->orderBy('codigo_dgt')->paginate($request->per_page ?? 15);

        return TipoComprobanteFeResource::collection($tipos);
    }

    public function store(StoreTipoComprobanteFeRequest $request)
    {
        $tipo = TipoComprobanteFe::create($request->validated());

        return new TipoComprobanteFeResource($tipo);
    }

    public function show(TipoComprobanteFe $tipoComprobanteFe)
    {
        return new TipoComprobanteFeResource($tipoComprobanteFe);
    }

    public function update(UpdateTipoComprobanteFeRequest $request, TipoComprobanteFe $tipoComprobanteFe)
    {
        $tipoComprobanteFe->update($request->validated());

        return new TipoComprobanteFeResource($tipoComprobanteFe);
    }

    public function destroy(TipoComprobanteFe $tipoComprobanteFe)
    {
        $tipoComprobanteFe->delete();

        return response()->json(['message' => 'Tipo de comprobante eliminado correctamente'], 200);
    }
}
