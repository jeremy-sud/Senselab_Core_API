<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Pago
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden_compra_id' => ['nullable', 'integer', 'exists:ordenes_compra,id'],
            'cuenta_por_pagar_id' => ['nullable', 'integer', 'exists:cuentas_por_pagar,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'cuenta_por_cobrar_id' => ['nullable', 'integer', 'exists:cuentas_por_cobrar,id'],
            'forma_pago_id' => ['required', 'integer', 'exists:formas_pago,id'],
            'fecha_pago' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'moneda' => ['nullable', 'string', 'size:3', Rule::in(['CRC', 'USD', 'EUR'])],
            'descripcion' => ['nullable', 'string'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Pendiente', 'Pagado', 'Cancelado'])],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que tenga al menos un destino (proveedor o cliente)
            if (!$this->proveedor_id && !$this->cliente_id) {
                $validator->errors()->add('proveedor_id', 'Debe especificar un proveedor o un cliente');
            }
            
            // Validar que no tenga ambos
            if ($this->proveedor_id && $this->cliente_id) {
                $validator->errors()->add('cliente_id', 'No puede especificar proveedor y cliente al mismo tiempo');
            }
        });
    }

    public function messages(): array
    {
        return [
            'forma_pago_id.required' => 'La forma de pago es obligatoria',
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'monto.required' => 'El monto es obligatorio',
            'monto.min' => 'El monto debe ser mayor a 0'
        ];
    }

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
