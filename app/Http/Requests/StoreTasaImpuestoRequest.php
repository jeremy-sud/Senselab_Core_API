<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear Tasa de Impuesto
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreTasaImpuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_impuesto_id' => ['required', 'integer', 'exists:tipos_impuesto,id'],
            'tasa_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'fecha_inicio_vigencia' => ['required', 'date'],
            'fecha_fin_vigencia' => ['nullable', 'date', 'after:fecha_inicio_vigencia'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que no exista otra tasa vigente para el mismo tipo en el mismo período
            if ($this->has('tipo_impuesto_id') && $this->has('fecha_inicio_vigencia')) {
                $query = \App\Models\TasaImpuesto::where('tipo_impuesto_id', $this->tipo_impuesto_id)
                    ->where('eliminado', 0)
                    ->where('fecha_inicio_vigencia', '<=', $this->fecha_inicio_vigencia);

                if ($this->has('fecha_fin_vigencia')) {
                    $query->where(function ($q) {
                        $q->whereNull('fecha_fin_vigencia')
                          ->orWhere('fecha_fin_vigencia', '>=', $this->fecha_inicio_vigencia);
                    });
                }

                if ($query->exists()) {
                    $validator->errors()->add('fecha_inicio_vigencia', 'Ya existe una tasa vigente para este tipo de impuesto en el período especificado');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo_impuesto_id.required' => 'El tipo de impuesto es obligatorio',
            'tipo_impuesto_id.exists' => 'El tipo de impuesto seleccionado no existe',
            'tasa_porcentaje.required' => 'La tasa porcentual es obligatoria',
            'tasa_porcentaje.min' => 'La tasa debe ser mayor o igual a 0',
            'tasa_porcentaje.max' => 'La tasa no puede ser mayor a 100%',
            'fecha_inicio_vigencia.required' => 'La fecha de inicio de vigencia es obligatoria',
            'fecha_fin_vigencia.after' => 'La fecha de fin debe ser posterior a la fecha de inicio'
        ];
    }

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
