<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Obtiene KPIs del dashboard para el tenant actual.
     */
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
    public function invalidarCache(): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = request()->user();
        $this->dashboardService->invalidarCache((int) $usuario->empresa_id);

        return $this->successResponse(message: 'Cache del dashboard invalidado');
    }
}
