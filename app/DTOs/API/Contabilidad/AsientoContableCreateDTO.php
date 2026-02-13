<?php

namespace App\DTOs\API\Contabilidad;

use Illuminate\Http\Request;

/**
 * AsientoContableCreateDTO - DTO para crear asientos contables
 * 
 * Valida estructura de asiento con debe/haber balanceados.
 */
final class AsientoContableCreateDTO
{
    private function __construct(
        public readonly string $numero_asiento,
        public readonly \DateTime $fecha,
        public readonly string $concepto,
        public readonly array $detalles, // array de DetalleAsientoDTO
        public readonly ?string $referencia = null,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        $detalles = $request->input('detalles', []);
        
        return new self(
            numero_asiento: $request->input('numero_asiento'),
            fecha: new \DateTime($request->input('fecha')),
            concepto: $request->input('concepto'),
            detalles: $detalles,
            referencia: $request->input('referencia'),
            observaciones: $request->input('observaciones'),
        );
    }

    /**
     * Suma del debe
     */
    public function totalDebe(): float
    {
        return (float) array_reduce(
            $this->detalles,
            fn($carry, $detalle) => $carry + ($detalle['debe'] ?? 0),
            0
        );
    }

    /**
     * Suma del haber
     */
    public function totalHaber(): float
    {
        return (float) array_reduce(
            $this->detalles,
            fn($carry, $detalle) => $carry + ($detalle['haber'] ?? 0),
            0
        );
    }

    /**
     * Verifica que asiento esté balanceado
     */
    public function estáBalanceado(): bool
    {
        return abs($this->totalDebe() - $this->totalHaber()) < 0.01;
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'numero_asiento' => $this->numero_asiento,
            'fecha' => $this->fecha->format('Y-m-d'),
            'concepto' => $this->concepto,
            'referencia' => $this->referencia,
            'observaciones' => $this->observaciones,
            'detalles' => json_encode($this->detalles),
            'total_debe' => $this->totalDebe(),
            'total_haber' => $this->totalHaber(),
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'numero_asiento' => 'required|string|max:50|unique:asientos_contables,numero_asiento',
            'fecha' => 'required|date',
            'concepto' => 'required|string|max:500',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
            'detalles' => 'required|array|min:2',
            'detalles.*.cuenta_id' => 'required|integer|exists:cuentas_contables,id',
            'detalles.*.debe' => 'nullable|numeric|min:0',
            'detalles.*.haber' => 'nullable|numeric|min:0',
            'detalles.*.descripcion' => 'nullable|string|max:500',
        ];
    }

    public static function messages(): array
    {
        return [
            'numero_asiento.unique' => 'El número de asiento ya existe',
            'fecha.required' => 'La fecha es requerida',
            'detalles.min' => 'Al menos 2 líneas de detalle son requeridas',
            'detalles.*.cuenta_id.exists' => 'La cuenta contable no existe',
        ];
    }
}
