<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComprobanteRecibidoElectronicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => 'sometimes|nullable|exists:proveedores,id',
            'consecutivo_receptor' => 'sometimes|nullable|string|max:20',
            'entrada_inventario_id' => 'sometimes|nullable|exists:entradas_inventario,id'
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'entrada_inventario_id.exists' => 'La entrada de inventario seleccionada no existe'
        ];
    }
}
