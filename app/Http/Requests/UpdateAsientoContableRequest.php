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

    public function rules(): array
    {
        return [
            'fecha' => ['sometimes', 'date'],
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

    public function withValidator($validator)
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

    public function messages(): array
    {
        return [
            'detalles.min' => 'Un asiento contable debe tener al menos 2 líneas',
            'detalles.*.cuenta_contable_id.exists' => 'La cuenta contable seleccionada no existe'
        ];
    }

    public function attributes(): array
    {
        return [
            'fecha' => 'fecha',
            'descripcion' => 'descripción',
            'estado' => 'estado',
            'detalles' => 'detalles del asiento'
        ];
    }
}
