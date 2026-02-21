<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar sucursales
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateSucursalRequest extends FormRequest
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
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'canton' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'es_principal' => ['boolean'],
            'activo' => ['boolean'],
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
            'nombre.required' => 'El nombre de la sucursal es obligatorio',
            'email.email' => 'El formato del email no es válido',
        ];
    }

    /**
     * Validación adicional: solo una sucursal principal por empresa
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
            $sucursalId = $this->route('sucursale');
            $sucursal = \App\Models\Sucursal::find($sucursalId);

            if ($this->has('es_principal') && $this->es_principal) {
                $existe = \App\Models\Sucursal::where('empresa_id', $sucursal->empresa_id)
                    ->where('es_principal', true)
                    ->where('id', '!=', $sucursalId)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'es_principal',
                        'Ya existe otra sucursal principal para esta empresa'
                    );
                }
            }
        });
    }
}
