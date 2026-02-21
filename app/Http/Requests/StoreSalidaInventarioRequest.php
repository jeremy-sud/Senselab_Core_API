<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalidaInventarioRequest extends FormRequest
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
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'fecha_salida' => ['required', 'date'],
            'tipo_salida' => ['required', 'string', 'in:Venta,Ajuste Negativo,Devolucion Proveedor,Transferencia,Consumo Interno'],
            'venta_id' => ['nullable', 'integer', 'exists:ventas,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'documento_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['required', 'string'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['sometimes', 'string', 'in:Pendiente,Procesada,Cancelada'],
            'monto_total' => ['sometimes', 'numeric', 'min:0'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.subtotal' => ['sometimes', 'numeric', 'min:0'],
            'detalles.*.lote' => ['nullable', 'string', 'max:100']
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
            'almacen_id.required' => 'El almacén es obligatorio',
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'fecha_salida.required' => 'La fecha de salida es obligatoria',
            'tipo_salida.required' => 'El tipo de salida es obligatorio',
            'tipo_salida.in' => 'El tipo de salida debe ser: Venta, Ajuste Negativo, Devolucion Proveedor, Transferencia o Consumo Interno',
            'venta_id.exists' => 'La venta seleccionada no existe',
            'cliente_id.exists' => 'El cliente seleccionado no existe',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'observaciones.required' => 'Las observaciones son obligatorias',
            'detalles.required' => 'Debe incluir al menos un producto en la salida',
            'detalles.min' => 'Debe incluir al menos un producto en la salida',
            'detalles.*.producto_id.required' => 'El producto es obligatorio en cada línea de detalle',
            'detalles.*.producto_id.exists' => 'Uno o más productos seleccionados no existen',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria en cada línea de detalle',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.costo_unitario.required' => 'El costo unitario es obligatorio en cada línea de detalle',
            'detalles.*.costo_unitario.min' => 'El costo unitario debe ser mayor o igual a 0'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('almacen_id')) {
                $almacen = \App\Models\Almacen::find($this->almacen_id);
                if ($almacen && $almacen->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add('almacen_id', 'El almacén no pertenece a tu empresa');
                }
            }

            if ($this->filled('venta_id')) {
                $venta = \App\Models\Venta::find($this->venta_id);
                if ($venta && $venta->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add('venta_id', 'La venta no pertenece a tu empresa');
                }
            }

            if ($this->filled('cliente_id')) {
                $cliente = \App\Models\Cliente::find($this->cliente_id);
                if ($cliente && $cliente->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add('cliente_id', 'El cliente no pertenece a tu empresa');
                }
            }
        });
    }
}
