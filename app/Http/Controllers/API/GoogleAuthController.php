<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redirige al usuario a la página de consentimiento de Google.
     */
    public function redirectToGoogle(Request $request)
    {
        $redirectOrigin = $request->query('redirect_origin', 'https://scisenselab.com');

        return Socialite::driver('google')
            ->stateless()
            ->with([
                'state' => encrypt([
                    'redirect_origin' => $redirectOrigin
                ])
            ])
            ->redirect();
    }

    /**
     * Maneja la respuesta del callback de Google.
     */
    public function handleCallback(Request $request)
    {
        try {
            // Desencriptar el estado para obtener la URL de redirección del frontend
            $redirectOrigin = 'https://scisenselab.com';
            if ($request->has('state')) {
                try {
                    $state = decrypt($request->input('state'));
                    if (is_array($state) && isset($state['redirect_origin'])) {
                        $redirectOrigin = $state['redirect_origin'];
                    }
                } catch (\Exception $e) {
                    Log::error('Error desencriptando el estado en Google OAuth callback: ' . $e->getMessage());
                }
            }

            // Obtener datos del usuario desde Google
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            if (!$googleUser || !$googleUser->getEmail()) {
                Log::error('No se pudieron obtener los datos de usuario de Google.');
                return redirect()->away($redirectOrigin . '?error=oauth_failed');
            }

            // Buscar o crear usuario
            $usuario = Usuario::where('email', $googleUser->getEmail())->first();

            if (!$usuario) {
                // Obtener o crear empresa por defecto
                $empresa = Empresa::first();
                if (!$empresa) {
                    $empresa = Empresa::create([
                        'nombre' => 'Senselab Labs S.A.',
                        'nombre_comercial' => 'Senselab Labs',
                        'razon_social' => 'Senselab Labs S.A.',
                        'num_identificacion_dgt' => '3-101-789012',
                        'tipo_identificacion' => 'Físico',
                        'activo' => true,
                    ]);
                }

                // Separar nombre y apellidos si es posible
                $fullName = $googleUser->getName() ?: 'Usuario';
                $parts = explode(' ', $fullName, 2);
                $nombre = $parts[0];
                $apellidos = $parts[1] ?? 'Google';

                $cargo = Cargo::first();

                $usuario = Usuario::create([
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'email' => $googleUser->getEmail(),
                    'password_hash' => bcrypt(Str::random(16)),
                    'empresa_id' => $empresa->id,
                    'cargo_id' => $cargo?->id,
                    'activo' => true,
                    'eliminado' => false,
                ]);

                // Asignar rol por defecto
                $rol = \App\Models\Rol::where('nombre', 'Administrador')->first() 
                    ?: \App\Models\Rol::where('nombre', 'Usuario')->first()
                    ?: \App\Models\Rol::first();
                if ($rol) {
                    $usuario->assignRoles([$rol->id]);
                }
            }

            // Verificar si el usuario está inactivo o eliminado
            if (!$usuario->activo || $usuario->eliminado) {
                return redirect()->away($redirectOrigin . '?error=user_inactive');
            }

            // Generar Token de Laravel Sanctum
            $token = $usuario->createToken('google-sso')->plainTextToken;

            // Retornar al frontend con el token y los datos de usuario
            $userData = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'empresa_id' => $usuario->empresa_id,
            ];

            $redirectUrl = $redirectOrigin . (str_contains($redirectOrigin, '?') ? '&' : '?') . http_build_query([
                'token' => $token,
                'user' => json_encode($userData)
            ]);

            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('Error en el proceso de autenticación de Google: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->away('https://scisenselab.com?error=oauth_exception');
        }
    }
}
