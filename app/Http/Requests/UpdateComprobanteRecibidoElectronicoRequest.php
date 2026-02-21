<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComprobanteRecibidoElectronicoRequest extends FormRequest
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
            'proveedor_id' => 'sometimes|nullable|exists:proveedores,id',
            'consecutivo_receptor' => 'sometimes|nullable|string|max:20',
            'entrada_inventario_id' => 'sometimes|nullable|exists:entradas_inventario,id'
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
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'entrada_inventario_id.exists' => 'La entrada de inventario seleccionada no existe'
        ];
    }
}
