<?php

namespace App\Http\Controllers;

use App\Models\MensajeHacienda;
use App\Http\Requests\StoreMensajeHaciendaRequest;
use App\Http\Requests\UpdateMensajeHaciendaRequest;
use App\Http\Resources\MensajeHaciendaResource;
use Illuminate\Http\Request;

class MensajeHaciendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MensajeHacienda::with(['empresa', 'comprobante']);

        // Filtro por empresa (multi-tenancy)
        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        // Filtro por tipo de mensaje
        if ($request->filled('tipo_mensaje')) {
            $query->where('tipo_mensaje', $request->tipo_mensaje);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Búsqueda por clave numérica o descripción
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('clave_numerica', 'like', "%{$search}%")
                  ->orWhere('mensaje', 'like', "%{$search}%");
            });
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        $mensajes = $query->latest()->paginate($request->per_page ?? 15);

        return MensajeHaciendaResource::collection($mensajes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMensajeHaciendaRequest $request)
    {
        $mensaje = MensajeHacienda::create($request->validated());
        $mensaje->load(['empresa', 'comprobante']);

        return new MensajeHaciendaResource($mensaje);
    }

    /**
     * Display the specified resource.
     */
    public function show(MensajeHacienda $mensajeHacienda)
    {
        $mensajeHacienda->load(['empresa', 'comprobante']);

        return new MensajeHaciendaResource($mensajeHacienda);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMensajeHaciendaRequest $request, MensajeHacienda $mensajeHacienda)
    {
        $mensajeHacienda->update($request->validated());
        $mensajeHacienda->load(['empresa', 'comprobante']);

        return new MensajeHaciendaResource($mensajeHacienda);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MensajeHacienda $mensajeHacienda)
    {
        $mensajeHacienda->delete();

        return response()->json(['message' => 'Mensaje eliminado correctamente'], 200);
    }
}
