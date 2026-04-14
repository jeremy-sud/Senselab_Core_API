<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ReporteProgramado;
use App\Http\Requests\StoreReporteProgramadoRequest;
use App\Http\Requests\UpdateReporteProgramadoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reportes Programados', description: 'CRUD de reportes financieros programados con cron')]
class ReporteProgramadoController extends Controller
{
    #[OA\Get(
        path: '/api/reportes/programados',
        summary: 'Listar reportes programados',
        description: 'Obtiene la lista paginada de reportes programados activos del tenant actual.',
        security: [['sanctum' => []]],
        tags: ['Reportes Programados'],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de reportes programados'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/reportes/programados',
        summary: 'Crear reporte programado',
        description: 'Crea un nuevo reporte financiero programado con expresión cron.',
        security: [['sanctum' => []]],
        tags: ['Reportes Programados'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo_reporte', type: 'string', enum: ['estado_resultados', 'balance_general', 'flujo_caja']),
                    new OA\Property(property: 'formato', type: 'string', enum: ['pdf', 'excel', 'csv']),
                    new OA\Property(property: 'cron_expresion', type: 'string', example: '0 8 1 * *'),
                    new OA\Property(property: 'email_destino', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reporte programado creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/reportes/programados/{id}',
        summary: 'Ver reporte programado',
        description: 'Obtiene los detalles de un reporte programado específico.',
        security: [['sanctum' => []]],
        tags: ['Reportes Programados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del reporte programado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $reporte = ReporteProgramado::with('usuario:id,nombre,apellidos')->findOrFail($id);

        return $this->successResponse($reporte);
    }

    #[OA\Put(
        path: '/api/reportes/programados/{id}',
        summary: 'Actualizar reporte programado',
        description: 'Actualiza la configuración de un reporte programado existente.',
        security: [['sanctum' => []]],
        tags: ['Reportes Programados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte programado actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateReporteProgramadoRequest $request, int $id): JsonResponse
    {
        $reporte = ReporteProgramado::findOrFail($id);
        $reporte->update($request->validated());

        return $this->successResponse($reporte->fresh('usuario:id,nombre,apellidos'), 'Reporte programado actualizado');
    }

    #[OA\Delete(
        path: '/api/reportes/programados/{id}',
        summary: 'Eliminar reporte programado',
        description: 'Desactiva un reporte programado (soft delete).',
        security: [['sanctum' => []]],
        tags: ['Reportes Programados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte programado eliminado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $reporte = ReporteProgramado::findOrFail($id);
        $reporte->update(['activo' => false, 'eliminado' => true]);

        return $this->deletedResponse();
    }
}
