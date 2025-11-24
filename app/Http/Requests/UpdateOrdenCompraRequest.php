<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar órdenes de compra
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateOrdenCompraRequest extends FormRequest
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
            'fecha_orden' => ['sometimes', 'required', 'date'],
            'fecha_entrega_esperada' => ['nullable', 'date', 'after_or_equal:fecha_orden'],
            'estado' => ['sometimes', 'required', 'in:borrador,pendiente,aprobada,recibida,cancelada'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_orden.required' => 'La fecha de orden es obligatoria',
            'fecha_entrega_esperada.after_or_equal' => 'La fecha de entrega debe ser igual o posterior a la fecha de orden',
            'estado.in' => 'Estado inválido (borrador, pendiente, aprobada, recibida, cancelada)',
        ];
    }

    /**
     * Validación adicional: solo permitir editar en estado borrador o pendiente
     */
    /**
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ordenId = $this->route('ordenes_compra');
            $orden = \App\Models\OrdenCompra::find($ordenId);

            if ($orden && !in_array($orden->estado, ['borrador', 'pendiente'])) {
                $validator->errors()->add(
                    'estado',
                    'Solo se pueden editar órdenes en estado borrador o pendiente'
                );
            }
        });
    }
}
