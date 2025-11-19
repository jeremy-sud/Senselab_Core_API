<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarioProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'producto_id' => 'required|integer|exists:productos,id',
            'stock_actual' => 'required|numeric|min:0|max:99999999.99',
            'costo_promedio' => 'required|numeric|min:0|max:99999999.99',
            'stock_minimo' => 'nullable|numeric|min:0|max:99999999.99',
            'stock_maximo' => 'nullable|numeric|min:0|max:99999999.99',
            'ubicacion' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'almacen_id.required' => 'El almacén es requerido',
            'almacen_id.exists' => 'El almacén no existe',
            'producto_id.required' => 'El producto es requerido',
            'producto_id.exists' => 'El producto no existe',
            'stock_actual.required' => 'El stock actual es requerido',
            'costo_promedio.required' => 'El costo promedio es requerido',
        ];
    }
}
