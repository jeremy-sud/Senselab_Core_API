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

    public function rules(): array
    {
        return [
            'nombre_periodo' => ['required', 'string', 'max:100'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'fecha_pago_estimada' => ['nullable', 'date', 'after_or_equal:fecha_fin'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Abierto', 'Cerrado', 'Procesado'])],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function withValidator($validator)
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

    public function messages(): array
    {
        return [
            'nombre_periodo.required' => 'El nombre del período es obligatorio',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_fin.required' => 'La fecha de fin es obligatoria',
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
