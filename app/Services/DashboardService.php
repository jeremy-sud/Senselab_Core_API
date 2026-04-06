<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\InventarioProducto;
use App\Models\NominaEmpleado;
use App\Models\PeriodoNomina;
use App\Models\Venta;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Obtiene todos los KPIs del dashboard.
     *
     * @return array<string, mixed>
     */
    public function obtenerKpis(int $empresaId, ?int $sucursalId = null): array
    {
        $cacheKey = "dashboard:{$empresaId}:" . ($sucursalId ?? 'all');

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, 900, function () use ($empresaId, $sucursalId): array {
            return [
                'ventas_mes' => $this->ventasDelMes($empresaId, $sucursalId),
                'cuentas_vencidas' => $this->cuentasVencidas($empresaId),
                'inventario_bajo_minimo' => $this->inventarioBajoMinimo($empresaId),
                'nomina_pendiente' => $this->nominaPendiente($empresaId),
                'resumen_financiero' => $this->resumenFinanciero($empresaId, $sucursalId),
                'generado_en' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Invalida cache del dashboard.
     */
    public function invalidarCache(int $empresaId): void
    {
        Cache::forget("dashboard:{$empresaId}:all");
    }

    /**
     * @return array<string, mixed>
     */
    private function ventasDelMes(int $empresaId, ?int $sucursalId): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $inicioMesAnterior = now()->subMonth()->startOfMonth();
        $finMesAnterior = now()->subMonth()->endOfMonth();

        $query = Venta::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false);

        if ($sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        $totalActual = (clone $query)
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->sum('monto_total_venta');

        $cantidadActual = (clone $query)
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->count();

        $totalAnterior = (clone $query)
            ->whereBetween('fecha_venta', [$inicioMesAnterior, $finMesAnterior])
            ->sum('monto_total_venta');

        $variacion = $totalAnterior > 0
            ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 2)
            : 0;

        return [
            'total' => (string) $totalActual,
            'cantidad' => $cantidadActual,
            'mes_anterior' => (string) $totalAnterior,
            'variacion_porcentaje' => $variacion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cuentasVencidas(int $empresaId): array
    {
        $porCobrar = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->where('estado', '!=', 'pagada')
            ->where('fecha_vencimiento', '<', now())
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto_pendiente), 0) as total')
            ->first();

        $porPagar = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->where('estado', '!=', 'pagada')
            ->where('fecha_vencimiento', '<', now())
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto_pendiente), 0) as total')
            ->first();

        return [
            'cuentas_por_cobrar' => [
                'cantidad' => $porCobrar->cantidad ?? 0,
                'total' => (string) ($porCobrar->total ?? 0),
            ],
            'cuentas_por_pagar' => [
                'cantidad' => $porPagar->cantidad ?? 0,
                'total' => (string) ($porPagar->total ?? 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventarioBajoMinimo(int $empresaId): array
    {
        $productos = InventarioProducto::join('productos', 'inventario_productos.producto_id', '=', 'productos.id')
            ->where('productos.empresa_id', $empresaId)
            ->where('inventario_productos.activo', true)
            ->where('inventario_productos.eliminado', false)
            ->whereRaw('inventario_productos.stock_actual <= inventario_productos.stock_minimo')
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                'inventario_productos.stock_actual',
                'inventario_productos.stock_minimo',
                'inventario_productos.almacen_id'
            )
            ->orderByRaw('inventario_productos.stock_actual - inventario_productos.stock_minimo ASC')
            ->limit(20)
            ->get();

        return [
            'cantidad' => $productos->count(),
            'productos' => $productos->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nominaPendiente(int $empresaId): array
    {
        $periodoActual = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('estado', 'pendiente')
            ->where('activo', true)
            ->where('eliminado', false)
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$periodoActual) {
            return [
                'periodo_pendiente' => false,
                'total_neto' => '0.00',
                'empleados' => 0,
            ];
        }

        $nominas = NominaEmpleado::where('periodo_nomina_id', $periodoActual->id)
            ->where('activo', true)
            ->where('eliminado', false);

        return [
            'periodo_pendiente' => true,
            'periodo' => $periodoActual->nombre_periodo,
            'fecha_pago' => $periodoActual->fecha_pago,
            'total_neto' => (string) $nominas->sum('salario_neto'),
            'empleados' => $nominas->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenFinanciero(int $empresaId, ?int $sucursalId): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        $ventasQuery = Venta::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->whereBetween('fecha_venta', [$inicioMes, $finMes]);

        if ($sucursalId) {
            $ventasQuery->where('sucursal_id', $sucursalId);
        }

        $totalCxC = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->where('estado', '!=', 'pagada')
            ->sum('monto_pendiente');

        $totalCxP = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->where('estado', '!=', 'pagada')
            ->sum('monto_pendiente');

        return [
            'ingresos_mes' => (string) $ventasQuery->sum('monto_total_venta'),
            'cuentas_por_cobrar_pendientes' => (string) $totalCxC,
            'cuentas_por_pagar_pendientes' => (string) $totalCxP,
            'posicion_neta' => bcsub((string) $totalCxC, (string) $totalCxP, 2),
        ];
    }
}
