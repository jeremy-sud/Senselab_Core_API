<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalidaInventarioRequest extends FormRequest
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
            'fecha_salida' => 'sometimes|required|date',
            'tipo_salida' => 'sometimes|required|string|in:Venta,Ajuste Negativo,Devolución Proveedor,Transferencia,Consumo Interno,Merma',
            'venta_id' => 'nullable|exists:ventas,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'documento_referencia' => 'nullable|string|max:100',
            'observaciones' => 'sometimes|required|string',
            'descripcion' => 'nullable|string'
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
            'fecha_salida.date' => 'La fecha de salida debe ser una fecha válida',
            'tipo_salida.in' => 'El tipo de salida debe ser: Venta, Ajuste Negativo, Devolución Proveedor, Transferencia, Consumo Interno o Merma'
        ];
    }
}
