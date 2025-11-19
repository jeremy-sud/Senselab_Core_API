<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntradaInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'fecha_entrada' => ['required', 'date'],
            'tipo_entrada' => ['required', 'string', 'in:Compra,Ajuste Positivo,Devolucion Cliente,Transferencia,Produccion'],
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'documento_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],
            'estado' => ['sometimes', 'string', 'in:Pendiente,Procesada,Cancelada'],
            'monto_total' => ['sometimes', 'numeric', 'min:0'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.subtotal' => ['sometimes', 'numeric', 'min:0'],
            'detalles.*.lote' => ['nullable', 'string', 'max:100'],
            'detalles.*.fecha_vencimiento' => ['nullable', 'date', 'after:today']
        ];
    }

    public function messages(): array
    {
        return [
            'almacen_id.required' => 'El almacén es obligatorio',
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria',
            'tipo_entrada.required' => 'El tipo de entrada es obligatorio',
            'tipo_entrada.in' => 'El tipo de entrada debe ser: Compra, Ajuste Positivo, Devolucion Cliente, Transferencia o Produccion',
            'orden_compra_id.exists' => 'La orden de compra seleccionada no existe',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'detalles.required' => 'Debe incluir al menos un producto en la entrada',
            'detalles.min' => 'Debe incluir al menos un producto en la entrada',
            'detalles.*.producto_id.required' => 'El producto es obligatorio en cada línea de detalle',
            'detalles.*.producto_id.exists' => 'Uno o más productos seleccionados no existen',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria en cada línea de detalle',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio en cada línea de detalle',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor o igual a 0',
            'detalles.*.fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy'
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('almacen_id')) {
                $almacen = \App\Models\Almacen::find($this->almacen_id);
                if ($almacen && $almacen->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add('almacen_id', 'El almacén no pertenece a tu empresa');
                }
            }

            if ($this->filled('orden_compra_id')) {
                $orden = \App\Models\OrdenCompra::find($this->orden_compra_id);
                if ($orden && $orden->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add('orden_compra_id', 'La orden de compra no pertenece a tu empresa');
                }
            }
        });
    }
}
