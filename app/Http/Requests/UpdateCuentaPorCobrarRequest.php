<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Cuenta por Cobrar
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateCuentaPorCobrarRequest extends FormRequest
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'venta_id' => ['nullable', 'integer', 'exists:ventas,id'],
            'numero_documento' => ['nullable', 'string', 'max:100'],
            'fecha_emision' => ['sometimes', 'date'],
            'fecha_vencimiento' => ['sometimes', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['sometimes', 'string', 'size:3', Rule::in(['CRC', 'USD', 'EUR'])],
            'monto_original' => ['sometimes', 'numeric', 'min:0', 'max:99999999999.99999'],
            'monto_pagado' => ['sometimes', 'numeric', 'min:0', 'lte:monto_original'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Pendiente', 'Pagada Parcialmente', 'Pagada Totalmente', 'Vencida', 'Anulada'])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente_id.exists' => 'El cliente seleccionado no existe',
            'venta_id.exists' => 'La venta seleccionada no existe',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida',
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
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'venta_id' => 'venta',
            'numero_documento' => 'número de documento',
            'fecha_emision' => 'fecha de emisión',
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
