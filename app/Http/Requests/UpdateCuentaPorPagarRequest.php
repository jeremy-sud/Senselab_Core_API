<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Cuenta por Pagar
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateCuentaPorPagarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'proveedor_id' => ['sometimes', 'integer', 'exists:proveedores,id'],
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'comprobante_recibido_id' => ['nullable', 'integer'],
            'documento_referencia_proveedor' => ['sometimes', 'string', 'max:100'],
            'fecha_emision_documento' => ['sometimes', 'date'],
            'fecha_recepcion_documento' => ['nullable', 'date'],
            'fecha_vencimiento' => ['sometimes', 'date', 'after_or_equal:fecha_emision_documento'],
            'moneda' => ['sometimes', 'string', 'size:3', Rule::in(['CRC', 'USD', 'EUR'])],
            'monto_original' => ['sometimes', 'numeric', 'min:0', 'max:99999999999.99999'],
            'monto_pagado' => ['sometimes', 'numeric', 'min:0', 'lte:monto_original'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Pendiente', 'Pagada Parcialmente', 'Pagada Totalmente', 'Vencida', 'Anulada'])],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'orden_compra_id.exists' => 'La orden de compra seleccionada no existe',
            'fecha_emision_documento.date' => 'La fecha de emisión debe ser una fecha válida',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
            'moneda.in' => 'La moneda debe ser CRC, USD o EUR',
            'monto_original.numeric' => 'El monto original debe ser un número',
            'monto_original.min' => 'El monto original debe ser mayor o igual a 0',
            'monto_pagado.lte' => 'El monto pagado no puede ser mayor al monto original'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'orden_compra_id' => 'orden de compra',
            'comprobante_recibido_id' => 'comprobante recibido',
            'documento_referencia_proveedor' => 'número de factura',
            'fecha_emision_documento' => 'fecha de emisión',
            'fecha_recepcion_documento' => 'fecha de recepción',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'moneda' => 'moneda',
            'monto_original' => 'monto original',
            'monto_pagado' => 'monto pagado',
            'estado' => 'estado',
            'observaciones' => 'observaciones',
            'activo' => 'activo'
        ];
    }
}
