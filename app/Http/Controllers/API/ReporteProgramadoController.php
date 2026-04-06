<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ReporteProgramado;
use App\Http\Requests\StoreReporteProgramadoRequest;
use App\Http\Requests\UpdateReporteProgramadoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteProgramadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = $request->user();

        $reportes = ReporteProgramado::where('empresa_id', $usuario->empresa_id)
            ->where('activo', true)
            ->where('eliminado', false)
            ->with('usuario:id,nombre,apellidos')
            ->orderBy('creado_en', 'desc')
            ->paginate(15);

        return $this->paginatedResponse($reportes);
    }

    public function store(StoreReporteProgramadoRequest $request): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = $request->user();

        $reporte = ReporteProgramado::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            ...$request->validated(),
        ]);

        return $this->createdResponse($reporte->load('usuario:id,nombre,apellidos'));
    }

    public function show(int $id): JsonResponse
    {
        $reporte = ReporteProgramado::with('usuario:id,nombre,apellidos')->findOrFail($id);

        return $this->successResponse($reporte);
    }

    public function update(UpdateReporteProgramadoRequest $request, int $id): JsonResponse
    {
        $reporte = ReporteProgramado::findOrFail($id);
        $reporte->update($request->validated());

        return $this->successResponse($reporte->fresh('usuario:id,nombre,apellidos'), 'Reporte programado actualizado');
    }

    public function destroy(int $id): JsonResponse
    {
        $reporte = ReporteProgramado::findOrFail($id);
        $reporte->update(['activo' => false, 'eliminado' => true]);

        return $this->deletedResponse();
    }
}
