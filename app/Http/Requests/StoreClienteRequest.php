<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear clientes
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'tipo_identificacion' => ['required', 'in:01,02,03,04,05,06,07'],
            'numero_identificacion' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'canton' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_identificacion.required' => 'El tipo de identificación es obligatorio',
            'tipo_identificacion.in' => 'Tipo de identificación inválido',
            'numero_identificacion.required' => 'La identificación es obligatoria',
            'nombre.required' => 'El nombre es obligatorio',
            'email.email' => 'El formato del email no es válido',
            'limite_credito.min' => 'El límite de crédito debe ser mayor o igual a 0',
            'dias_credito.min' => 'Los días de crédito deben ser mayor o igual a 0',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    /**
     * @param \Illuminate\Validation\Validator $validator
     */
    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar unicidad de identificación por empresa
            $existe = \App\Models\Cliente::where('empresa_id', $this->empresa_id)
                ->where('numero_identificacion', $this->numero_identificacion)
                ->exists();

            if ($existe) {
                $validator->errors()->add(
                    'numero_identificacion',
                    'Ya existe un cliente con esta identificación en la empresa'
                );
            }
        });
    }
}
