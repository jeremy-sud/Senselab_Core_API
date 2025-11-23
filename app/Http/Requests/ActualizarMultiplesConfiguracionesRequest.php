<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarMultiplesConfiguracionesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'configuraciones' => 'required|array|min:1',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required',
        ];
    }
}
