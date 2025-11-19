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
            'fecha_pago' => 'required|date',
            'monto_pago' => 'required|numeric|min:0',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|max:3',
            'observaciones' => 'nullable|string',
        ];
    }
}
