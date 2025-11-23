<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SincronizarEtiquetasRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entidad_tipo' => 'required|string|max:50',
            'entidad_id' => 'required|integer|min:1',
            'etiqueta_ids' => 'required|array',
            'etiqueta_ids.*' => 'required|integer|exists:etiquetas,id',
        ];
    }
}
