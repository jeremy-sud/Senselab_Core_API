<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanillaCcssRequest extends FormRequest
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
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'periodo_nomina_id' => ['nullable', 'exists:periodos_nomina,id'],
            'periodo' => ['sometimes', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'fecha_generacion' => ['sometimes', 'date'],
            'fecha_presentacion' => ['nullable', 'date'],
            'numero_planilla' => ['nullable', 'string', 'max:50'],
            'total_empleados' => ['sometimes', 'integer', 'min:1'],
            'total_salarios' => ['sometimes', 'numeric', 'min:0'],
            'total_cuota_obrera' => ['sometimes', 'numeric', 'min:0'],
            'total_cuota_patronal' => ['sometimes', 'numeric', 'min:0'],
            'total_a_pagar' => ['sometimes', 'numeric', 'min:0'],
            'archivo_xml' => ['nullable', 'string', 'max:255'],
            'archivo_pdf' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'in:borrador,enviada,aceptada,rechazada,pagada'],
            'fecha_pago' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
