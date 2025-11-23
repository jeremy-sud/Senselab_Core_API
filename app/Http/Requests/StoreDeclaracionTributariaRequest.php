<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeclaracionTributariaRequest extends FormRequest
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
            'empresa_id' => ['required', 'exists:empresas,id'],
            'tipo_declaracion' => ['required', 'in:D104,D101,D103,D150,D151'],
            'periodo_fiscal' => ['required', 'string', 'max:7', 'regex:/^\d{4}-\d{2}$|^\d{4}$/'],
            'fecha_inicio_periodo' => ['required', 'date'],
            'fecha_fin_periodo' => ['required', 'date', 'after_or_equal:fecha_inicio_periodo'],
            'fecha_presentacion' => ['nullable', 'date'],
            'monto_base_imponible' => ['nullable', 'numeric', 'min:0'],
            'monto_impuesto' => ['nullable', 'numeric', 'min:0'],
            'monto_creditos' => ['nullable', 'numeric', 'min:0'],
            'monto_debitos' => ['nullable', 'numeric', 'min:0'],
            'monto_a_pagar' => ['nullable', 'numeric', 'min:0'],
            'monto_a_favor' => ['nullable', 'numeric', 'min:0'],
            'numero_confirmacion' => ['nullable', 'string', 'max:50'],
            'archivo_xml' => ['nullable', 'string', 'max:255'],
            'archivo_pdf' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:borrador,enviada,aceptada,rechazada'],
            'notas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'tipo_declaracion.required' => 'El tipo de declaración es obligatorio',
            'tipo_declaracion.in' => 'Tipo válido: D104 (IVA), D101 (Renta), D103, D150, D151',
            'periodo_fiscal.required' => 'El período fiscal es obligatorio',
            'periodo_fiscal.regex' => 'Formato: YYYY-MM (mensual) o YYYY (anual)',
            'fecha_fin_periodo.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha inicio',
        ];
    }
}
