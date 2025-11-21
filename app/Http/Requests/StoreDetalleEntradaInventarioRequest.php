<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDetalleEntradaInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entrada_inventario_id' => 'required|exists:entradas_inventario,id',
            'producto_id' => 'required|exists:productos,id',
            'numero_linea' => 'required|integer|min:1',
            'cantidad' => 'required|numeric|min:0.01',
            'costo_unitario' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'porcentaje_impuesto' => 'nullable|numeric|min:0|max:100',
            'monto_impuesto' => 'nullable|numeric|min:0',
            'total_linea' => 'required|numeric|min:0',
            'lote' => 'nullable|string|max:100',
            'fecha_vencimiento' => 'nullable|date|after:today',
            'observaciones' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'entrada_inventario_id.required' => 'La entrada de inventario es obligatoria',
            'entrada_inventario_id.exists' => 'La entrada de inventario no existe',
            'producto_id.required' => 'El producto es obligatorio',
            'producto_id.exists' => 'El producto seleccionado no existe',
            'cantidad.required' => 'La cantidad es obligatoria',
            'cantidad.numeric' => 'La cantidad debe ser un número',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'costo_unitario.required' => 'El costo unitario es obligatorio',
            'costo_unitario.numeric' => 'El costo unitario debe ser un número',
            'costo_unitario.min' => 'El costo unitario debe ser mayor o igual a 0',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy'
        ];
    }
}
