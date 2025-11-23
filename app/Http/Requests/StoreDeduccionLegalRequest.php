<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeduccionLegalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:10', 'unique:deducciones_legales,codigo'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['required', 'in:ccss_obrero,ccss_patronal,ins_laboral,ins_lpt,impuesto_renta,asociacion_solidarista,embargo,prestamo,otros'],
            'porcentaje_base' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_without:monto_fijo'],
            'monto_fijo' => ['nullable', 'numeric', 'min:0', 'required_without:porcentaje_base'],
            'aplica_sobre' => ['required', 'in:salario_bruto,salario_neto,monto_especifico'],
            'es_obligatoria' => ['boolean'],
            'activa' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya existe',
            'nombre.required' => 'El nombre es obligatorio',
            'tipo.required' => 'El tipo de deducción es obligatorio',
            'porcentaje_base.required_without' => 'Debe especificar porcentaje o monto fijo',
            'monto_fijo.required_without' => 'Debe especificar porcentaje o monto fijo',
            'aplica_sobre.required' => 'Debe especificar sobre qué se aplica la deducción',
        ];
    }
}
