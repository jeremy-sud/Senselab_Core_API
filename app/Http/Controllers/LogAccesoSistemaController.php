<?php

namespace App\Http\Controllers;

use App\Models\LogAccesoSistema;
use App\Http\Requests\StoreLogAccesoSistemaRequest;
use App\Http\Requests\UpdateLogAccesoSistemaRequest;
use App\Http\Resources\LogAccesoSistemaResource;
use Illuminate\Http\Request;

class LogAccesoSistemaController extends Controller
{
    public function index(Request $request)
    {
        $query = LogAccesoSistema::with('usuario');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('tipo_evento')) {
            $query->where('tipo_evento', $request->tipo_evento);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate($request->per_page ?? 15);

        return LogAccesoSistemaResource::collection($logs);
    }

    public function store(StoreLogAccesoSistemaRequest $request)
    {
        $log = LogAccesoSistema::create($request->validated());
        $log->load('usuario');

        return new LogAccesoSistemaResource($log);
    }

    public function show(LogAccesoSistema $logAccesoSistema)
    {
        $logAccesoSistema->load('usuario');

        return new LogAccesoSistemaResource($logAccesoSistema);
    }

    public function update(UpdateLogAccesoSistemaRequest $request, LogAccesoSistema $logAccesoSistema)
    {
        $logAccesoSistema->update($request->validated());
        $logAccesoSistema->load('usuario');

        return new LogAccesoSistemaResource($logAccesoSistema);
    }

    public function destroy(LogAccesoSistema $logAccesoSistema)
    {
        $logAccesoSistema->delete();

        return response()->json(['message' => 'Log de acceso eliminado correctamente'], 200);
    }
}
