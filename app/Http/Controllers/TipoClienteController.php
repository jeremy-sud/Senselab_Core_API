<?php

namespace App\Http\Controllers;

use App\Models\TipoCliente;
use App\Http\Requests\StoreTipoClienteRequest;
use App\Http\Requests\UpdateTipoClienteRequest;
use App\Http\Resources\TipoClienteResource;
use Illuminate\Http\Request;

class TipoClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoCliente::query();

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('con_descuento')) {
            $query->where('descuento_default', '>', 0);
        }

        if ($request->boolean('con_credito')) {
            $query->where('dias_credito_default', '>', 0);
        }

        $tipos = $query->orderBy('codigo')->paginate($request->per_page ?? 15);

        return TipoClienteResource::collection($tipos);
    }

    public function store(StoreTipoClienteRequest $request)
    {
        $tipo = TipoCliente::create($request->validated());

        return new TipoClienteResource($tipo);
    }

    public function show(TipoCliente $tipoCliente)
    {
        return new TipoClienteResource($tipoCliente);
    }

    public function update(UpdateTipoClienteRequest $request, $tiposCliente)
    {
        $tipo = TipoCliente::findOrFail($tiposCliente);
        $tipo->update($request->validated());

        return new TipoClienteResource($tipo);
    }

    public function destroy($tiposCliente)
    {
        $tipo = TipoCliente::findOrFail($tiposCliente);
        $tipo->delete();

        return response()->json(['message' => 'Tipo de cliente eliminado correctamente'], 200);
    }
}
