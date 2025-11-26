<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StorePagoCuentaCobrarRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'cuenta_por_cobrar_id' => 'required|integer|exists:cuentas_por_cobrar,id',
            'forma_pago_id' => 'required|integer|exists:formas_pago,id',
            'fecha_pago' => 'required|date|before_or_equal:today',
            'monto_pago' => 'required|numeric|min:0.01',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|max:3|in:CRC,USD',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'cuenta_por_cobrar_id.required' => 'Debe seleccionar una cuenta por cobrar',
            'cuenta_por_cobrar_id.exists' => 'La cuenta por cobrar seleccionada no existe',
            'forma_pago_id.required' => 'Debe seleccionar una forma de pago',
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser futura',
            'monto_pago.required' => 'El monto del pago es obligatorio',
            'monto_pago.min' => 'El monto del pago debe ser mayor a cero',
            'moneda.in' => 'La moneda debe ser CRC o USD',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
        ];
    }

    public function attributes(): array
    {
        return [
            'cuenta_por_cobrar_id' => 'cuenta por cobrar',
            'forma_pago_id' => 'forma de pago',
            'fecha_pago' => 'fecha de pago',
            'monto_pago' => 'monto del pago',
            'numero_referencia' => 'número de referencia',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el monto no exceda el saldo pendiente
            if ($this->has('monto_pago') && $this->has('cuenta_por_cobrar_id')) {
                $cuentaPorCobrar = \App\Models\CuentaPorCobrar::find($this->cuenta_por_cobrar_id);
                if ($cuentaPorCobrar && $this->monto_pago > $cuentaPorCobrar->saldo_pendiente) {
                    $validator->errors()->add(
                        'monto_pago',
                        'El monto del pago no puede exceder el saldo pendiente (₡' . number_format($cuentaPorCobrar->saldo_pendiente, 2) . ')'
                    );
                }
            }
        });
    }
}
