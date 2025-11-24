<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear ventas
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreVentaRequest extends FormRequest
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
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'forma_pago_id' => ['required', 'exists:formas_pago,id'],
            'fecha_venta' => ['required', 'date'],
            'tipo_comprobante' => ['required', 'in:factura,tiquete,nota_credito,nota_debito'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.porcentaje_impuesto' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
            'sucursal_id.required' => 'La sucursal es obligatoria',
            'cliente_id.required' => 'El cliente es obligatorio',
            'usuario_id.required' => 'El usuario es obligatorio',
            'forma_pago_id.required' => 'La forma de pago es obligatoria',
            'fecha_venta.required' => 'La fecha de venta es obligatoria',
            'tipo_comprobante.required' => 'El tipo de comprobante es obligatorio',
            'tipo_comprobante.in' => 'Tipo de comprobante inválido',
            'detalles.required' => 'Debe agregar al menos un detalle a la venta',
            'detalles.min' => 'Debe agregar al menos un detalle a la venta',
            'detalles.*.producto_id.required' => 'El producto es obligatorio en cada detalle',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio',
            'detalles.*.precio_unitario.min' => 'El precio debe ser mayor o igual a 0',
        ];
    }
}
