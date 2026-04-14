<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Obtiene KPIs del dashboard para el tenant actual.
     */
    #[OA\Get(
        path: '/api/dashboard',
        summary: 'KPIs del dashboard',
        description: 'Obtiene indicadores clave de rendimiento (KPIs) del dashboard para el tenant actual. Incluye ventas del mes, cuentas vencidas, inventario bajo y nómina pendiente.',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'sucursal_id', description: 'Filtrar KPIs por sucursal', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'KPIs del dashboard obtenidos exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = $request->user();

        $sucursalId = $request->filled('sucursal_id')
            ? (int) $request->input('sucursal_id')
            : null;

        $kpis = $this->dashboardService->obtenerKpis(
            (int) $usuario->empresa_id,
            $sucursalId,
        );

        return $this->successResponse($kpis, 'KPIs del dashboard');
    }

    /**
     * Invalida cache del dashboard.
     */
    #[OA\Post(
        path: '/api/dashboard/invalidar-cache',
        summary: 'Invalidar cache del dashboard',
        description: 'Invalida la cache de KPIs del dashboard para forzar recálculo en la próxima consulta.',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Cache invalidado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function invalidarCache(): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = request()->user();
        $this->dashboardService->invalidarCache((int) $usuario->empresa_id);

        return $this->successResponse(message: 'Cache del dashboard invalidado');
    }
}
