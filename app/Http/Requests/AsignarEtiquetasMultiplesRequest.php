<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarEtiquetasMultiplesRequest extends FormRequest
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
        return [
            'etiqueta_ids' => 'required|array|min:1',
            'etiqueta_ids.*' => 'required|integer|exists:etiquetas,id',
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
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
            'etiqueta_ids.required' => 'Debe especificar al menos una etiqueta.',
            'etiqueta_ids.array' => 'Las etiquetas deben ser un arreglo.',
            'etiqueta_ids.min' => 'Debe especificar al menos una etiqueta.',
            'etiqueta_ids.*.exists' => 'Una o más etiquetas especificadas no existen.',
            'entidad_tipo.required' => 'El tipo de entidad es obligatorio.',
            'entidad_tipo.max' => 'El tipo de entidad no puede tener más de 50 caracteres.',
            'entidad_id.required' => 'El ID de la entidad es obligatorio.',
            'entidad_id.min' => 'El ID de la entidad debe ser mayor que 0.',
        ];
    }
}
