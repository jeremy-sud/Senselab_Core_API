<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Período de Nómina
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdatePeriodoNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_periodo' => ['sometimes', 'string', 'max:100'],
            'fecha_inicio' => ['sometimes', 'date'],
            'fecha_fin' => ['sometimes', 'date', 'after:fecha_inicio'],
            'fecha_pago_estimada' => ['nullable', 'date', 'after_or_equal:fecha_fin'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Abierto', 'Cerrado', 'Procesado'])],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
            'fecha_pago_estimada.after_or_equal' => 'La fecha de pago estimada debe ser igual o posterior a la fecha de fin del período',
            'estado.in' => 'El estado debe ser Abierto, Cerrado o Procesado'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_periodo' => 'nombre del período',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'fecha_pago_estimada' => 'fecha de pago estimada',
            'estado' => 'estado',
            'observaciones' => 'observaciones'
        ];
    }
}
