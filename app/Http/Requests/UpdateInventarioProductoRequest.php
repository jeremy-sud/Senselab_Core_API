<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventarioProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almacen_id' => 'sometimes|integer|exists:almacenes,id',
            'producto_id' => 'sometimes|integer|exists:productos,id',
            'stock_actual' => 'sometimes|numeric|min:0|max:99999999.99',
            'costo_promedio' => 'sometimes|numeric|min:0|max:99999999.99',
            'stock_minimo' => 'sometimes|numeric|min:0|max:99999999.99',
            'stock_maximo' => 'sometimes|numeric|min:0|max:99999999.99',
            'ubicacion' => 'nullable|string|max:100',
        ];
    }
}
