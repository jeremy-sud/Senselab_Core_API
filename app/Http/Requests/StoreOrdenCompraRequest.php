<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear órdenes de compra
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreOrdenCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'fecha_orden' => ['required', 'date'],
            'fecha_entrega_esperada' => ['nullable', 'date', 'after_or_equal:fecha_orden'],
            'estado' => ['required', 'in:borrador,pendiente,aprobada,recibida,cancelada'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.descripcion' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'proveedor_id.required' => 'El proveedor es obligatorio',
            'usuario_id.required' => 'El usuario es obligatorio',
            'fecha_orden.required' => 'La fecha de orden es obligatoria',
            'fecha_entrega_esperada.after_or_equal' => 'La fecha de entrega debe ser igual o posterior a la fecha de orden',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'Estado inválido',
            'detalles.required' => 'Debe agregar al menos un detalle',
            'detalles.min' => 'Debe agregar al menos un detalle',
            'detalles.*.producto_id.required' => 'El producto es obligatorio en cada detalle',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio',
        ];
    }
}
