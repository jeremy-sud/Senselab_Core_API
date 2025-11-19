<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'primer_apellido' => ['required', 'string', 'max:255'],
            'segundo_apellido' => ['nullable', 'string', 'max:255'],
            'tipo_documento' => ['required', 'string', 'in:DNI,PASAPORTE,Cedula_Extranjera,Cedula_Nacional'],
            'numero_documento' => [
                'required',
                'string',
                'max:50',
                Rule::unique('empleados', 'numero_documento')->where(function ($query) {
                    return $query->where('empresa_id', auth()->user()->empresa_id)
                                 ->where('eliminado', 0);
                })
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'in:Masculino,Femenino,Otro'],
            'fecha_contratacion' => ['required', 'date'],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'salario' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del empleado es obligatorio',
            'primer_apellido.required' => 'El primer apellido es obligatorio',
            'tipo_documento.required' => 'El tipo de documento es obligatorio',
            'tipo_documento.in' => 'El tipo de documento debe ser: DNI, PASAPORTE, Cedula_Extranjera o Cedula_Nacional',
            'numero_documento.required' => 'El número de documento es obligatorio',
            'numero_documento.unique' => 'Ya existe un empleado con este número de documento en la empresa',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'fecha_contratacion.required' => 'La fecha de contratación es obligatoria',
            'salario.required' => 'El salario es obligatorio',
            'salario.min' => 'El salario debe ser mayor o igual a 0',
            'cargo_id.exists' => 'El cargo seleccionado no existe',
            'usuario_id.exists' => 'El usuario seleccionado no existe',
            'email.email' => 'El formato del email no es válido'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'primer_apellido' => 'primer apellido',
            'segundo_apellido' => 'segundo apellido',
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'genero' => 'género',
            'fecha_contratacion' => 'fecha de contratación',
            'cargo_id' => 'cargo',
            'salario' => 'salario',
            'usuario_id' => 'usuario',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'activo' => 'activo'
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('usuario_id')) {
                $usuario = \App\Models\Usuario::find($this->usuario_id);
                if ($usuario && $usuario->empresa_id != auth()->user()->empresa_id) {
                    $validator->errors()->add(
                        'usuario_id',
                        'El usuario no pertenece a tu empresa'
                    );
                }
            }

            if ($this->filled('fecha_contratacion') && $this->filled('fecha_nacimiento')) {
                $fechaNac = \Carbon\Carbon::parse($this->fecha_nacimiento);
                $fechaContrat = \Carbon\Carbon::parse($this->fecha_contratacion);
                
                if ($fechaContrat->diffInYears($fechaNac) < 15) {
                    $validator->errors()->add(
                        'fecha_contratacion',
                        'El empleado debe tener al menos 15 años al momento de la contratación'
                    );
                }
            }
        });
    }
}
