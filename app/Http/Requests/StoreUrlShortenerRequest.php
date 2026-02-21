<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUrlShortenerRequest extends FormRequest
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
            'empresa_id' => 'required|exists:empresas,id',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'url_original' => 'required|url|max:2000',
            'slug' => 'nullable|string|max:50|unique:url_shorter_db,slug',
            'descripcion' => 'nullable|string|max:255',
            'expira_en' => 'nullable|date|after:now',
            'activo' => 'boolean',
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
            'empresa_id.required' => 'La empresa es requerida',
            'empresa_id.exists' => 'La empresa especificada no existe',
            'usuario_id.exists' => 'El usuario especificado no existe',
            'url_original.required' => 'La URL original es requerida',
            'url_original.url' => 'Debe proporcionar una URL válida',
            'url_original.max' => 'La URL no puede exceder 2000 caracteres',
            'slug.unique' => 'Este slug ya está en uso',
            'slug.max' => 'El slug no puede exceder 50 caracteres',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres',
            'expira_en.date' => 'La fecha de expiración debe ser una fecha válida',
            'expira_en.after' => 'La fecha de expiración debe ser posterior a la fecha actual',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso',
        ];
    }
}
