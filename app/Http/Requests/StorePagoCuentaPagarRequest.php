<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StorePagoCuentaPagarRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'cuenta_por_pagar_id' => 'required|integer|exists:cuentas_por_pagar,id',
            'forma_pago_id' => 'required|integer|exists:formas_pago,id',
            'fecha_pago' => 'required|date',
            'monto_pago' => 'required|numeric|min:0',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|max:3',
            'observaciones' => 'nullable|string',
        ];
    }
}
