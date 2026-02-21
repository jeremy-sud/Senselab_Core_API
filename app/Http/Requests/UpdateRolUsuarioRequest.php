<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolUsuarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'usuario_id' => 'sometimes|integer|exists:usuarios,id',
            'rol_id' => 'sometimes|integer|exists:roles,id',
            'activo' => 'sometimes|boolean',
        ];
    }
}
