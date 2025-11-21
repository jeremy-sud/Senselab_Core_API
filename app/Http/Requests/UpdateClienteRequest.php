<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar clientes
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_identificacion' => ['sometimes', 'required', 'in:fisica,juridica,dimex,nite,extranjero'],
            'numero_identificacion' => ['sometimes', 'required', 'string', 'max:50'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'canton' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_identificacion.in' => 'Tipo de identificación inválido',
            'nombre.required' => 'El nombre es obligatorio',
            'email.email' => 'El formato del email no es válido',
        ];
    }

    /**
     * Validación adicional para unicidad de identificación
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clienteId = $this->route('cliente');
            $cliente = \App\Models\Cliente::find($clienteId);

            if ($this->has('numero_identificacion') && $this->numero_identificacion !== $cliente->numero_identificacion) {
                $existe = \App\Models\Cliente::where('empresa_id', $cliente->empresa_id)
                    ->where('numero_identificacion', $this->numero_identificacion)
                    ->where('id', '!=', $clienteId)
                    ->exists();

                if ($existe) {
                    $validator->errors()->add(
                        'numero_identificacion',
                        'Ya existe un cliente con esta identificación en la empresa'
                    );
                }
            }
        });
    }
}
