<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoCuentaCobrarRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cuenta_por_cobrar_id' => 'sometimes|integer|exists:cuentas_por_cobrar,id',
            'forma_pago_id' => 'sometimes|integer|exists:formas_pago,id',
            'fecha_pago' => 'sometimes|date|before_or_equal:today',
            'monto_pago' => 'sometimes|numeric|min:0.01',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|max:3|in:CRC,USD',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuenta_por_cobrar_id.exists' => 'La cuenta por cobrar seleccionada no existe',
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser futura',
            'monto_pago.min' => 'El monto del pago debe ser mayor a cero',
            'moneda.in' => 'La moneda debe ser CRC o USD',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
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
}
