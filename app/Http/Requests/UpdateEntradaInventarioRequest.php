<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntradaInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'almacen_id' => 'sometimes|required|exists:almacenes,id',
            'fecha_entrada' => 'sometimes|required|date',
            'tipo_entrada' => 'sometimes|required|string|in:Compra,Ajuste Positivo,Devolución Cliente,Transferencia,Producción',
            'orden_compra_id' => 'nullable|exists:ordenes_compra,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'documento_referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string'
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
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'fecha_entrada.date' => 'La fecha de entrada debe ser una fecha válida',
            'tipo_entrada.in' => 'El tipo de entrada debe ser: Compra, Ajuste Positivo, Devolución Cliente, Transferencia o Producción'
        ];
    }
}
