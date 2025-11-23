<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeclaracionTributariaRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'tipo_declaracion' => ['sometimes', 'in:D104,D101,D103,D150,D151'],
            'periodo_fiscal' => ['sometimes', 'string', 'max:7', 'regex:/^\d{4}-\d{2}$|^\d{4}$/'],
            'fecha_inicio_periodo' => ['sometimes', 'date'],
            'fecha_fin_periodo' => ['sometimes', 'date', 'after_or_equal:fecha_inicio_periodo'],
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
            'estado' => ['sometimes', 'in:borrador,enviada,aceptada,rechazada'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
