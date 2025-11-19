<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreRolUsuarioRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'usuario_id' => 'required|integer|exists:usuarios,id',
            'rol_id' => 'required|integer|exists:roles,id',
        ];
    }
}
