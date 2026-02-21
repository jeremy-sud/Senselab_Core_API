<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Asiento Contable
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateAsientoContableRequest extends FormRequest
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
            'fecha_asiento' => ['sometimes', 'date'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Borrador', 'Mayorizado', 'Anulado'])],
            'detalles' => ['sometimes', 'array', 'min:2'],
            'detalles.*.cuenta_contable_id' => ['required_with:detalles', 'integer', 'exists:cuentas_contables,id'],
            'detalles.*.debe' => ['required_with:detalles', 'numeric', 'min:0'],
            'detalles.*.haber' => ['required_with:detalles', 'numeric', 'min:0'],
            'detalles.*.descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
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
            if ($this->has('detalles')) {
                $totalDebe = collect($this->detalles)->sum('debe');
                $totalHaber = collect($this->detalles)->sum('haber');
                
                if (abs($totalDebe - $totalHaber) > 0.01) {
                    $validator->errors()->add('detalles', 'El total del debe debe ser igual al total del haber');
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
            'detalles.min' => 'Un asiento contable debe tener al menos 2 líneas',
            'detalles.*.cuenta_contable_id.exists' => 'La cuenta contable seleccionada no existe'
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
            'fecha_asiento' => 'fecha',
            'descripcion' => 'descripción',
            'estado' => 'estado',
            'detalles' => 'detalles del asiento'
        ];
    }
}
