<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $usuario = Usuario::where('email', $request->email)
            ->where('activo', true)
            ->where('eliminado', false)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Revocar tokens anteriores
        $usuario->tokens()->delete();

        // Crear token
        $token = $usuario->createToken('api-token')->plainTextToken;

        // Cargar roles y permisos
        $usuario->load(['roles.permisos', 'empresa', 'cargo']);

        return response()->json([
            'success' => true,
            'data' => [
                'usuario' => $usuario,
                'token' => $token,
                'permisos' => $usuario->getAllPermissions()->pluck('slug')->values(),
            ],
            'message' => 'Login exitoso',
        ]);
    }

    /**
     * Logout de usuario
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso',
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        $usuario = $request->user();
        $usuario->load(['roles.permisos', 'empresa', 'cargo']);

        return response()->json([
            'success' => true,
            'data' => [
                'usuario' => $usuario,
                'permisos' => $usuario->getAllPermissions()->pluck('slug')->values(),
            ],
        ]);
    }
}
