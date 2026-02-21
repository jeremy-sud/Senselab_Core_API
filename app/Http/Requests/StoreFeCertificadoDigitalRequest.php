<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFeCertificadoDigitalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasPermissionTo('crear-facturacion_electronica');
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
            'nombre' => 'required|string|max:255',
            'archivo_certificado' => 'required|file|mimes:p12,pfx|max:2048',
            'password_archivo' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'emisor' => 'nullable|string|max:500',
            'sujeto' => 'nullable|string|max:500',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'required|date|after:today',
            'activo' => 'boolean',
            'ambiente' => 'required|in:stag,produccion',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del certificado',
            'archivo_certificado' => 'archivo .p12',
            'password_archivo' => 'contraseña del certificado',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'ambiente' => 'ambiente',
        ];
    }

    /**
     * Get custom error messages.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo_certificado.mimes' => 'El archivo debe ser un certificado digital válido (.p12 o .pfx)',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy',
        ];
    }
}
