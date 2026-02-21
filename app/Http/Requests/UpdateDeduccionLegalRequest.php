<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeduccionLegalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('deduccion_legal');
        
        return [
            'codigo' => ['sometimes', 'string', 'max:10', 'unique:deducciones_legales,codigo,' . $id],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['sometimes', 'in:ccss_obrero,ccss_patronal,ins_laboral,ins_lpt,impuesto_renta,asociacion_solidarista,embargo,prestamo,otros'],
            'porcentaje_base' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'monto_fijo' => ['nullable', 'numeric', 'min:0'],
            'aplica_sobre' => ['sometimes', 'in:salario_bruto,salario_neto,monto_especifico'],
            'es_obligatoria' => ['boolean'],
            'activa' => ['boolean'],
        ];
    }
}
