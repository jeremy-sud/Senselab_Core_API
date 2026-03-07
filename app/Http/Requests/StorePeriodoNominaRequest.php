<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Período de Nómina
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StorePeriodoNominaRequest extends FormRequest
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
            'nombre_periodo' => ['required', 'string', 'max:100'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'fecha_pago' => ['nullable', 'date', 'after_or_equal:fecha_fin'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Abierto', 'Cerrado', 'Procesado'])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que no exista solapamiento de fechas para la misma empresa
            if ($this->has('fecha_inicio') && $this->has('fecha_fin')) {
                $empresaId = $this->user()->empresa_id;
                
                $solapamiento = \App\Models\PeriodoNomina::where('empresa_id', $empresaId)
                    ->where('eliminado', 0)
                    ->where(function ($query) {
                        $query->whereBetween('fecha_inicio', [$this->fecha_inicio, $this->fecha_fin])
                            ->orWhereBetween('fecha_fin', [$this->fecha_inicio, $this->fecha_fin])
                            ->orWhere(function ($q) {
                                $q->where('fecha_inicio', '<=', $this->fecha_inicio)
                                  ->where('fecha_fin', '>=', $this->fecha_fin);
                            });
                    })
                    ->exists();

                if ($solapamiento) {
                    $validator->errors()->add('fecha_inicio', 'Ya existe un período de nómina que se solapa con estas fechas');
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_periodo.required' => 'El nombre del período es obligatorio',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_fin.required' => 'La fecha de fin es obligatoria',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
            'fecha_pago.after_or_equal' => 'La fecha de pago debe ser igual o posterior a la fecha de fin del período',
            'estado.in' => 'El estado debe ser Abierto, Cerrado o Procesado'
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
            'nombre_periodo' => 'nombre del período',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'fecha_pago' => 'fecha de pago',
            'estado' => 'estado',
            'observaciones' => 'observaciones'
        ];
    }
}
