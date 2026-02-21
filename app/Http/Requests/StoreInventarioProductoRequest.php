<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarioProductoRequest extends FormRequest
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
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'producto_id' => 'required|integer|exists:productos,id',
            'stock_actual' => 'required|numeric|min:0',
            'costo_promedio' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'stock_maximo' => 'required|numeric|min:0',
            'ubicacion' => 'nullable|string|max:100',
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
            'almacen_id.required' => 'El almacén es requerido',
            'almacen_id.exists' => 'El almacén especificado no existe',
            'producto_id.required' => 'El producto es requerido',
            'producto_id.exists' => 'El producto especificado no existe',
            'stock_actual.required' => 'El stock actual es requerido',
            'costo_promedio.required' => 'El costo promedio es requerido',
            'stock_minimo.required' => 'El stock mínimo es requerido',
            'stock_maximo.required' => 'El stock máximo es requerido',
        ];
    }
}
