<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoComprobanteFeRequest extends FormRequest
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
        $id = $this->route('tipo_comprobante_fe');
        
        return [
            'codigo_dgt' => ['sometimes', 'string', 'size:2', 'unique:tipos_comprobantes_fe,codigo_dgt,' . $id],
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'requiere_referencia' => ['boolean'],
            'permite_exportacion' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }
}
