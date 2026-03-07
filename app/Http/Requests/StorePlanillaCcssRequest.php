<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanillaCcssRequest extends FormRequest
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
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'periodo_nomina_id' => ['nullable', 'exists:periodos_nomina,id'],
            'periodo' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'fecha_generacion' => ['required', 'date'],
            'fecha_presentacion' => ['nullable', 'date'],
            'numero_planilla' => ['nullable', 'string', 'max:50'],
            'total_empleados' => ['required', 'integer', 'min:1'],
            'total_salarios' => ['required', 'numeric', 'min:0'],
            'total_cuota_obrera' => ['required', 'numeric', 'min:0'],
            'total_cuota_patronal' => ['required', 'numeric', 'min:0'],
            'total_a_pagar' => ['required', 'numeric', 'min:0'],
            'archivo_xml' => ['nullable', 'string', 'max:255'],
            'archivo_pdf' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:borrador,enviada,aceptada,rechazada,pagada'],
            'fecha_pago' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
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
            'empresa_id.required' => 'La empresa es obligatoria',
            'periodo.required' => 'El período es obligatorio',
            'periodo.regex' => 'Formato del período: YYYY-MM',
            'fecha_generacion.required' => 'La fecha de generación es obligatoria',
            'total_empleados.required' => 'El total de empleados es obligatorio',
            'total_empleados.min' => 'Debe haber al menos 1 empleado',
            'total_salarios.required' => 'El total de salarios es obligatorio',
            'total_cuota_obrera.required' => 'La cuota obrera es obligatoria',
            'total_cuota_patronal.required' => 'La cuota patronal es obligatoria',
            'total_a_pagar.required' => 'El total a pagar es obligatorio',
        ];
    }
}
