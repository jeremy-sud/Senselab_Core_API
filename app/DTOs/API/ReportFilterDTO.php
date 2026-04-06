<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

class ReportFilterDTO
{
    public function __construct(
        public readonly int $empresaId,
        public readonly string $tipoReporte,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
        public readonly ?int $sucursalId = null,
        public readonly string $moneda = 'CRC',
        public readonly ?string $periodoComparacion = null,
        public readonly string $formato = 'json',
    ) {}

    public static function fromRequest(Request $request): self
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = $request->user();

        return new self(
            empresaId: (int) $usuario->empresa_id,
            tipoReporte: (string) $request->input('tipo', 'estado_resultados'),
            fechaInicio: (string) $request->input('fecha_inicio', now()->startOfMonth()->toDateString()),
            fechaFin: (string) $request->input('fecha_fin', now()->toDateString()),
            sucursalId: $request->filled('sucursal_id') ? (int) $request->input('sucursal_id') : null,
            moneda: (string) $request->input('moneda', 'CRC'),
            periodoComparacion: $request->input('periodo_comparacion'),
            formato: (string) $request->input('formato', 'json'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresaId,
            'tipo_reporte' => $this->tipoReporte,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'sucursal_id' => $this->sucursalId,
            'moneda' => $this->moneda,
            'periodo_comparacion' => $this->periodoComparacion,
            'formato' => $this->formato,
        ];
    }
}
