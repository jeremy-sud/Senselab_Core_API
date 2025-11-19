<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoCuentaPagarRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'cuenta_por_pagar_id' => 'sometimes|integer|exists:cuentas_por_pagar,id',
            'forma_pago_id' => 'sometimes|integer|exists:formas_pago,id',
            'fecha_pago' => 'sometimes|date',
            'monto_pago' => 'sometimes|numeric|min:0',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|max:3',
            'observaciones' => 'nullable|string',
        ];
    }
}
