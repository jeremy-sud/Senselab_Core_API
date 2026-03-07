<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpleadoRequest extends FormRequest
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
        $empleadoId = $this->route('empleado') ?? $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'primer_apellido' => ['sometimes', 'string', 'max:255'],
            'segundo_apellido' => ['nullable', 'string', 'max:255'],
            'tipo_documento' => ['sometimes', 'string', 'in:DNI,PASAPORTE,Cedula_Extranjera,Cedula_Nacional'],
            'numero_documento' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('empleados', 'numero_documento')->where(function ($query) use ($empleadoId) {
                    return $query->where('empresa_id', auth()->user()->empresa_id)
                                 ->where('eliminado', 0)
                                 ->where('id', '!=', $empleadoId);
                })
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'fecha_ingreso' => ['sometimes', 'date'],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'salario' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'activo' => ['sometimes', 'boolean']
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
            'numero_documento.unique' => 'Ya existe un empleado con este número de documento en la empresa',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'salario.min' => 'El salario debe ser mayor o igual a 0',
            'cargo_id.exists' => 'El cargo seleccionado no existe',
            'email.email' => 'El formato del email no es válido'
        ];
    }
}
