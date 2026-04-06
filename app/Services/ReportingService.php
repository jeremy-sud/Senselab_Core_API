<?php

namespace App\Services;

use App\DTOs\API\ReportFilterDTO;
use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\DetalleAsiento;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Genera Estado de Resultados (P&L).
     *
     * @return array<string, mixed>
     */
    public function estadoResultados(ReportFilterDTO $filtro): array
    {
        $cacheKey = $this->cacheKey('estado_resultados', $filtro);

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, $this->cacheTtl('estado_resultados'), function () use ($filtro): array {
            // Ingresos: tipos de cuenta cuyo nombre empieza con 'Ingreso'
            $ingresos = $this->sumarMovimientos($filtro, ['Ingreso%'], 'haber', 'debe');

            // Gastos: tipos de cuenta cuyo nombre empieza con 'Gasto'
            $gastos = $this->sumarMovimientos($filtro, ['Gasto%'], 'debe', 'haber');

            // Costos de venta
            $costos = $this->sumarMovimientos($filtro, ['Costo%'], 'debe', 'haber');

            $utilidadBruta = bcsub((string) $ingresos, (string) $costos, 2);
            $utilidadNeta = bcsub($utilidadBruta, (string) $gastos, 2);

            $resultado = [
                'tipo_reporte' => 'estado_resultados',
                'periodo' => ['inicio' => $filtro->fechaInicio, 'fin' => $filtro->fechaFin],
                'moneda' => $filtro->moneda,
                'ingresos' => $this->desglosePorCuenta($filtro, ['Ingreso%'], 'haber', 'debe'),
                'costos_venta' => $this->desglosePorCuenta($filtro, ['Costo%'], 'debe', 'haber'),
                'gastos_operativos' => $this->desglosePorCuenta($filtro, ['Gasto%'], 'debe', 'haber'),
                'totales' => [
                    'total_ingresos' => $ingresos,
                    'total_costos' => $costos,
                    'utilidad_bruta' => $utilidadBruta,
                    'total_gastos' => $gastos,
                    'utilidad_neta' => $utilidadNeta,
                ],
            ];

            if ($filtro->periodoComparacion !== null) {
                $resultado['comparacion'] = $this->generarComparacion($filtro, 'estado_resultados');
            }

            return $resultado;
        });
    }

    /**
     * Genera Balance General.
     *
     * @return array<string, mixed>
     */
    public function balanceGeneral(ReportFilterDTO $filtro): array
    {
        $cacheKey = $this->cacheKey('balance_general', $filtro);

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, $this->cacheTtl('balance_general'), function () use ($filtro): array {
            $activos = $this->saldosPorNaturaleza($filtro, ['Activo%']);
            $pasivos = $this->saldosPorNaturaleza($filtro, ['Pasivo%']);
            $capital = $this->saldosPorNaturaleza($filtro, ['Patrimonio%']);

            $totalActivos = collect($activos)->sum('saldo');
            $totalPasivos = collect($pasivos)->sum('saldo');
            $totalCapital = collect($capital)->sum('saldo');

            return [
                'tipo_reporte' => 'balance_general',
                'fecha_corte' => $filtro->fechaFin,
                'moneda' => $filtro->moneda,
                'activos' => $activos,
                'pasivos' => $pasivos,
                'capital' => $capital,
                'totales' => [
                    'total_activos' => (string) $totalActivos,
                    'total_pasivos' => (string) $totalPasivos,
                    'total_capital' => (string) $totalCapital,
                    'diferencia' => bcsub((string) $totalActivos, bcadd((string) $totalPasivos, (string) $totalCapital, 2), 2),
                ],
            ];
        });
    }

    /**
     * Genera Flujo de Caja.
     *
     * @return array<string, mixed>
     */
    public function flujoCaja(ReportFilterDTO $filtro): array
    {
        $cacheKey = $this->cacheKey('flujo_caja', $filtro);

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, $this->cacheTtl('flujo_caja'), function () use ($filtro): array {
            // Ingresos: pagos recibidos de clientes
            $ingresosPagos = Pago::where('empresa_id', $filtro->empresaId)
                ->whereBetween('fecha_pago', [$filtro->fechaInicio, $filtro->fechaFin])
                ->whereNotNull('cliente_id')
                ->where('estado', 'completado')
                ->when($filtro->moneda !== 'CRC', fn ($q) => $q->where('moneda', $filtro->moneda))
                ->sum('monto');

            // Ventas en efectivo
            $ventasEfectivo = Venta::where('empresa_id', $filtro->empresaId)
                ->whereBetween('fecha_venta', [$filtro->fechaInicio, $filtro->fechaFin])
                ->where('condicion_pago', 'contado')
                ->when($filtro->sucursalId, fn ($q) => $q->where('sucursal_id', $filtro->sucursalId))
                ->sum('monto_total_venta');

            // Egresos: pagos a proveedores
            $egresosPagos = Pago::where('empresa_id', $filtro->empresaId)
                ->whereBetween('fecha_pago', [$filtro->fechaInicio, $filtro->fechaFin])
                ->whereNotNull('proveedor_id')
                ->where('estado', 'completado')
                ->when($filtro->moneda !== 'CRC', fn ($q) => $q->where('moneda', $filtro->moneda))
                ->sum('monto');

            // Cobros de cuentas por cobrar
            $cobrosCxC = CuentaPorCobrar::where('empresa_id', $filtro->empresaId)
                ->whereBetween('actualizado_en', [$filtro->fechaInicio, $filtro->fechaFin])
                ->where('estado', 'pagada')
                ->sum('monto_pagado');

            // Pagos cuentas por pagar
            $pagosCxP = CuentaPorPagar::where('empresa_id', $filtro->empresaId)
                ->whereBetween('actualizado_en', [$filtro->fechaInicio, $filtro->fechaFin])
                ->where('estado', 'pagada')
                ->sum('monto_pagado');

            $totalIngresos = bcadd(bcadd((string) $ingresosPagos, (string) $ventasEfectivo, 2), (string) $cobrosCxC, 2);
            $totalEgresos = bcadd((string) $egresosPagos, (string) $pagosCxP, 2);
            $flujoNeto = bcsub($totalIngresos, $totalEgresos, 2);

            return [
                'tipo_reporte' => 'flujo_caja',
                'periodo' => ['inicio' => $filtro->fechaInicio, 'fin' => $filtro->fechaFin],
                'moneda' => $filtro->moneda,
                'actividades_operativas' => [
                    'cobros_clientes' => (string) $ingresosPagos,
                    'ventas_contado' => (string) $ventasEfectivo,
                    'cobros_cuentas_cobrar' => (string) $cobrosCxC,
                    'pagos_proveedores' => '-' . (string) $egresosPagos,
                    'pagos_cuentas_pagar' => '-' . (string) $pagosCxP,
                ],
                'totales' => [
                    'total_ingresos' => $totalIngresos,
                    'total_egresos' => $totalEgresos,
                    'flujo_neto' => $flujoNeto,
                ],
            ];
        });
    }

    /**
     * Genera reporte según tipo solicitado.
     *
     * @return array<string, mixed>
     */
    public function generar(ReportFilterDTO $filtro): array
    {
        return match ($filtro->tipoReporte) {
            'estado_resultados' => $this->estadoResultados($filtro),
            'balance_general' => $this->balanceGeneral($filtro),
            'flujo_caja' => $this->flujoCaja($filtro),
            default => throw new \InvalidArgumentException("Tipo de reporte no soportado: {$filtro->tipoReporte}"),
        };
    }

    /**
     * Invalida cache de reportes para un tenant.
     */
    public function invalidarCache(int $empresaId): void
    {
        $tipos = ['estado_resultados', 'balance_general', 'flujo_caja'];
        foreach ($tipos as $tipo) {
            Cache::forget("reporte:{$empresaId}:{$tipo}");
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * @param string[] $nombrePatterns
     */
    private function sumarMovimientos(ReportFilterDTO $filtro, array $nombrePatterns, string $sumColumn, string $restColumn): string
    {
        $suma = (string) DetalleAsiento::join('asientos_contables', 'detalle_asientos.asiento_contable_id', '=', 'asientos_contables.id')
            ->join('cuentas_contables', 'detalle_asientos.cuenta_contable_id', '=', 'cuentas_contables.id')
            ->join('tipos_cuentas', 'cuentas_contables.tipo_cuenta_id', '=', 'tipos_cuentas.id')
            ->where('asientos_contables.empresa_id', $filtro->empresaId)
            ->whereBetween('asientos_contables.fecha_asiento', [$filtro->fechaInicio, $filtro->fechaFin])
            ->where('asientos_contables.estado', 'Confirmado')
            ->where(function ($q) use ($nombrePatterns) {
                foreach ($nombrePatterns as $patron) {
                    $q->orWhere('tipos_cuentas.nombre', 'like', $patron);
                }
            })
            ->selectRaw("COALESCE(SUM(detalle_asientos.{$sumColumn}) - SUM(detalle_asientos.{$restColumn}), 0) as total")
            ->value('total');

        return $suma ?: '0.00';
    }

    /**
     * @param string[] $nombrePatterns
     * @return array<int, array<string, mixed>>
     */
    private function desglosePorCuenta(ReportFilterDTO $filtro, array $nombrePatterns, string $sumColumn, string $restColumn): array
    {
        /** @var array<int, array<string, mixed>> */
        return DetalleAsiento::join('asientos_contables', 'detalle_asientos.asiento_contable_id', '=', 'asientos_contables.id')
            ->join('cuentas_contables', 'detalle_asientos.cuenta_contable_id', '=', 'cuentas_contables.id')
            ->join('tipos_cuentas', 'cuentas_contables.tipo_cuenta_id', '=', 'tipos_cuentas.id')
            ->where('asientos_contables.empresa_id', $filtro->empresaId)
            ->whereBetween('asientos_contables.fecha_asiento', [$filtro->fechaInicio, $filtro->fechaFin])
            ->where('asientos_contables.estado', 'Confirmado')
            ->where(function ($q) use ($nombrePatterns) {
                foreach ($nombrePatterns as $patron) {
                    $q->orWhere('tipos_cuentas.nombre', 'like', $patron);
                }
            })
            ->groupBy('cuentas_contables.id', 'cuentas_contables.codigo', 'cuentas_contables.nombre')
            ->selectRaw("cuentas_contables.id, cuentas_contables.codigo, cuentas_contables.nombre, COALESCE(SUM(detalle_asientos.{$sumColumn}) - SUM(detalle_asientos.{$restColumn}), 0) as monto")
            ->orderBy('cuentas_contables.codigo')
            ->get()
            ->toArray();
    }

    /**
     * Saldos acumulados por naturaleza de cuenta (para balance general).
     *
     * @param string[] $nombrePatterns
     * @return array<int, array<string, mixed>>
     */
    private function saldosPorNaturaleza(ReportFilterDTO $filtro, array $nombrePatterns): array
    {
        /** @var array<int, array<string, mixed>> */
        return CuentaContable::join('tipos_cuentas', 'cuentas_contables.tipo_cuenta_id', '=', 'tipos_cuentas.id')
            ->where('cuentas_contables.empresa_id', $filtro->empresaId)
            ->where(function ($q) use ($nombrePatterns) {
                foreach ($nombrePatterns as $patron) {
                    $q->orWhere('tipos_cuentas.nombre', 'like', $patron);
                }
            })
            ->where('cuentas_contables.activo', true)
            ->where('cuentas_contables.permite_movimientos', true)
            ->select('cuentas_contables.id', 'cuentas_contables.codigo', 'cuentas_contables.nombre', 'cuentas_contables.saldo_actual as saldo')
            ->orderBy('cuentas_contables.codigo')
            ->get()
            ->toArray();
    }

    /**
     * Genera datos de comparación con período anterior.
     *
     * @return array<string, mixed>
     */
    private function generarComparacion(ReportFilterDTO $filtro, string $tipo): array
    {
        $periodoAnterior = $this->calcularPeriodoAnterior($filtro);

        $filtroAnterior = new ReportFilterDTO(
            empresaId: $filtro->empresaId,
            tipoReporte: $filtro->tipoReporte,
            fechaInicio: $periodoAnterior['inicio'],
            fechaFin: $periodoAnterior['fin'],
            sucursalId: $filtro->sucursalId,
            moneda: $filtro->moneda,
        );

        $datosAnteriores = match ($tipo) {
            'estado_resultados' => [
                'ingresos' => $this->sumarMovimientos($filtroAnterior, ['Ingreso%'], 'haber', 'debe'),
                'gastos' => $this->sumarMovimientos($filtroAnterior, ['Gasto%'], 'debe', 'haber'),
                'costos' => $this->sumarMovimientos($filtroAnterior, ['Costo%'], 'debe', 'haber'),
            ],
            default => [],
        };

        return [
            'periodo_anterior' => $periodoAnterior,
            'datos' => $datosAnteriores,
        ];
    }

    /**
     * Calcula período anterior según tipo de comparación.
     *
     * @return array{inicio: string, fin: string}
     */
    private function calcularPeriodoAnterior(ReportFilterDTO $filtro): array
    {
        $inicio = \Carbon\Carbon::parse($filtro->fechaInicio);
        $fin = \Carbon\Carbon::parse($filtro->fechaFin);

        return match ($filtro->periodoComparacion) {
            'mes' => [
                'inicio' => $inicio->subMonth()->toDateString(),
                'fin' => $fin->subMonth()->toDateString(),
            ],
            'trimestre' => [
                'inicio' => $inicio->subMonths(3)->toDateString(),
                'fin' => $fin->subMonths(3)->toDateString(),
            ],
            'anio' => [
                'inicio' => $inicio->subYear()->toDateString(),
                'fin' => $fin->subYear()->toDateString(),
            ],
            default => [
                'inicio' => $inicio->subMonth()->toDateString(),
                'fin' => $fin->subMonth()->toDateString(),
            ],
        };
    }

    private function cacheKey(string $tipo, ReportFilterDTO $filtro): string
    {
        $parts = [
            "reporte:{$filtro->empresaId}:{$tipo}",
            $filtro->fechaInicio,
            $filtro->fechaFin,
            $filtro->sucursalId ?? 'all',
            $filtro->moneda,
        ];

        return implode(':', $parts);
    }

    private function cacheTtl(string $tipo): int
    {
        /** @var array<string, int> $ttls */
        $ttls = config('cache.report_ttl', []);

        return $ttls[$tipo] ?? 3600; // 1 hora por defecto
    }
}
