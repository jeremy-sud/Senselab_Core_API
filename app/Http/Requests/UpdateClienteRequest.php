<?php

namespace App\Http\Requests;

use App\Rules\CrIdentificacion;
use App\Rules\CrTelefono;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para actualizar clientes
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class UpdateClienteRequest extends FormRequest
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
            'tipo_identificacion' => ['sometimes', 'required', 'in:01,02,03,04,05,06,07'],
            'numero_identificacion' => ['sometimes', 'required', 'string', 'max:50', new CrIdentificacion()],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50', new CrTelefono()],
            'celular' => ['nullable', 'string', 'max:50', new CrTelefono()],
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
            'tipo_identificacion.in' => 'Tipo de identificación inválido',
            'nombre.required' => 'El nombre es obligatorio',
            'email.email' => 'El formato del email no es válido',
        ];
    }

    /**
     * Validación adicional para unicidad de identificación
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
