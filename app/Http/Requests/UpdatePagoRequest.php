<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Pago
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdatePagoRequest extends FormRequest
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
            'orden_compra_id' => ['sometimes', 'integer', 'exists:ordenes_compra,id'],
            'cuenta_por_pagar_id' => ['sometimes', 'integer', 'exists:cuentas_por_pagar,id'],
            'proveedor_id' => ['sometimes', 'integer', 'exists:proveedores,id'],
            'cliente_id' => ['sometimes', 'integer', 'exists:clientes,id'],
            'cuenta_por_cobrar_id' => ['sometimes', 'integer', 'exists:cuentas_por_cobrar,id'],
            'forma_pago_id' => ['sometimes', 'integer', 'exists:formas_pago,id'],
            'fecha_pago' => ['sometimes', 'date'],
            'monto' => ['sometimes', 'numeric', 'min:0.01'],
            'moneda' => ['sometimes', 'string', 'size:3', Rule::in(['CRC', 'USD', 'EUR'])],
            'descripcion' => ['nullable', 'string'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Pendiente', 'Pagado', 'Cancelado'])],
            'activo' => ['sometimes', 'boolean']
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
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe',
            'monto.min' => 'El monto debe ser mayor a 0'
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
            'orden_compra_id' => 'orden de compra',
            'cuenta_por_pagar_id' => 'cuenta por pagar',
            'proveedor_id' => 'proveedor',
            'cliente_id' => 'cliente',
            'cuenta_por_cobrar_id' => 'cuenta por cobrar',
            'forma_pago_id' => 'forma de pago',
            'fecha_pago' => 'fecha de pago',
            'monto' => 'monto',
            'moneda' => 'moneda',
            'descripcion' => 'descripción',
            'referencia' => 'referencia',
            'estado' => 'estado'
        ];
    }
}
