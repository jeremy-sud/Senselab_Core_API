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
            'fecha_presentacion' => ['nullable', 'date', 'before_or_equal:today'],
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
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
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
            'empresa_id.exists' => 'La empresa seleccionada no existe',
            'tipo_declaracion.in' => 'El tipo de declaración no es válido. Debe ser D104 (IVA), D101 (Renta), D103 (Retenciones), D150 o D151',
            'periodo_fiscal.regex' => 'El período fiscal debe tener formato YYYY-MM o YYYY (ej: 2024-01 o 2024)',
            'fecha_fin_periodo.after_or_equal' => 'La fecha fin del período debe ser posterior o igual a la fecha de inicio',
            'fecha_presentacion.before_or_equal' => 'La fecha de presentación no puede ser futura',
            'monto_base_imponible.min' => 'La base imponible no puede ser negativa',
            'monto_impuesto.min' => 'El monto del impuesto no puede ser negativo',
            'monto_a_pagar.min' => 'El monto a pagar no puede ser negativo',
            'monto_a_favor.min' => 'El monto a favor no puede ser negativo',
            'estado.in' => 'El estado debe ser: borrador, enviada, aceptada o rechazada',
            'notas.max' => 'Las notas no pueden exceder 1000 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'empresa_id' => 'empresa',
            'tipo_declaracion' => 'tipo de declaración',
            'periodo_fiscal' => 'período fiscal',
            'fecha_inicio_periodo' => 'fecha de inicio del período',
            'fecha_fin_periodo' => 'fecha de fin del período',
            'fecha_presentacion' => 'fecha de presentación',
            'monto_base_imponible' => 'base imponible',
            'monto_impuesto' => 'monto del impuesto',
            'monto_creditos' => 'créditos',
            'monto_debitos' => 'débitos',
            'monto_a_pagar' => 'monto a pagar',
            'monto_a_favor' => 'monto a favor',
            'numero_confirmacion' => 'número de confirmación',
        ];
    }

    /**
     * Configure the validator instance.
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
            // Validar que no se especifiquen simultáneamente monto a pagar y a favor
            if ($this->has('monto_a_pagar') && $this->has('monto_a_favor')) {
                if ($this->monto_a_pagar > 0 && $this->monto_a_favor > 0) {
                    $validator->errors()->add(
                        'monto_a_pagar',
                        'No puede haber simultáneamente monto a pagar y monto a favor. Solo uno puede ser mayor a cero.'
                    );
                }
            }

            // Validar que la fecha de presentación esté dentro del período válido para el tipo
            if ($this->has('fecha_presentacion') && $this->has('fecha_fin_periodo') && $this->has('tipo_declaracion')) {
                $fechaPresentacion = strtotime($this->fecha_presentacion);
                $fechaFinPeriodo = strtotime($this->fecha_fin_periodo);
                
                // Para IVA (D104): presentación debe ser dentro del mes siguiente
                if ($this->tipo_declaracion === 'D104') {
                    $limiteD104 = strtotime('+1 month +15 days', $fechaFinPeriodo);
                    if ($fechaPresentacion > $limiteD104) {
                        $validator->errors()->add(
                            'fecha_presentacion',
                            'Para declaraciones de IVA, la presentación debe ser dentro del mes siguiente al período'
                        );
                    }
                }
            }
        });
    }
}
