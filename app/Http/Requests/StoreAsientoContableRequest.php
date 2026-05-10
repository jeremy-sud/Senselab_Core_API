<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Asiento Contable
 *
 * @package App\Http\Requests
 * @author Senselab - Jeremy Arias Solano
 */
class StoreAsientoContableRequest extends FormRequest
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
            'fecha_asiento' => ['required', 'date'],
            'concepto' => ['required', 'string'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Borrador', 'Mayorizado', 'Anulado'])],
            'detalles' => ['required', 'array', 'min:2'],
            'detalles.*.cuenta_contable_id' => ['required', 'integer', 'exists:cuentas_contables,id'],
            'detalles.*.debe' => ['required', 'numeric', 'min:0'],
            'detalles.*.haber' => ['required', 'numeric', 'min:0'],
            'detalles.*.descripcion' => ['nullable', 'string', 'max:1000'],
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
            // Validar que debe = haber
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
            'fecha_asiento.required' => 'La fecha del asiento es obligatoria',
            'detalles.required' => 'Debe agregar al menos 2 líneas de detalle',
            'detalles.min' => 'Un asiento contable debe tener al menos 2 líneas',
            'detalles.*.cuenta_contable_id.required' => 'Cada línea debe tener una cuenta contable',
            'detalles.*.cuenta_contable_id.exists' => 'La cuenta contable seleccionada no existe',
            'detalles.*.debe.required' => 'El monto del debe es obligatorio',
            'detalles.*.haber.required' => 'El monto del haber es obligatorio'
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
