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

    public function update(UpdateTipoClienteRequest $request, TipoCliente $tipoCliente)
    {
        $tipoCliente->update($request->validated());

        return new TipoClienteResource($tipoCliente);
    }

    public function destroy(TipoCliente $tipoCliente)
    {
        $tipoCliente->delete();

        return response()->json(['message' => 'Tipo de cliente eliminado correctamente'], 200);
    }
}
