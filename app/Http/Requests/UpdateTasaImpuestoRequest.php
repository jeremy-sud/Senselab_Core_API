<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar Tasa de Impuesto
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateTasaImpuestoRequest extends FormRequest
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
            'tipo_impuesto_id' => ['sometimes', 'integer', 'exists:tipos_impuesto,id'],
            'tasa_porcentaje' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'fecha_inicio_vigencia' => ['sometimes', 'date'],
            'fecha_fin_vigencia' => ['nullable', 'date', 'after:fecha_inicio_vigencia'],
            'descripcion' => ['nullable', 'string', 'max:255'],
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
            'tipo_impuesto_id.exists' => 'El tipo de impuesto seleccionado no existe',
            'tasa_porcentaje.min' => 'La tasa debe ser mayor o igual a 0',
            'tasa_porcentaje.max' => 'La tasa no puede ser mayor a 100%',
            'fecha_fin_vigencia.after' => 'La fecha de fin debe ser posterior a la fecha de inicio'
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
            'tipo_impuesto_id' => 'tipo de impuesto',
            'tasa_porcentaje' => 'tasa porcentual',
            'fecha_inicio_vigencia' => 'fecha de inicio de vigencia',
            'fecha_fin_vigencia' => 'fecha de fin de vigencia',
            'descripcion' => 'descripción',
            'activo' => 'activo'
        ];
    }
}
