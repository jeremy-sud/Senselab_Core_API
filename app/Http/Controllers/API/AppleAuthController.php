<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppleAuthController extends Controller
{
    /**
     * Redirige al usuario a la página de consentimiento de Apple.
     */
    public function redirectToApple(Request $request)
    {
        $redirectOrigin = $request->query('redirect_origin', 'https://scisenselab.com');
        $state = encrypt(['redirect_origin' => $redirectOrigin]);

        $query = http_build_query([
            'client_id' => env('APPLE_CLIENT_ID', 'com.scisenselab.service'),
            'redirect_uri' => env('APPLE_REDIRECT_URI', 'https://api.scisenselab.com/api/auth/apple/callback'),
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope' => 'name email',
            'state' => $state,
        ]);

        return redirect()->away('https://appleid.apple.com/auth/authorize?' . $query);
    }

    /**
     * Maneja la respuesta del callback de Apple (POST debido a form_post).
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
                    Log::error('Error desencriptando el estado en Apple OAuth callback: ' . $e->getMessage());
                }
            }

            $idToken = $request->input('id_token');
            if (!$idToken) {
                Log::error('No se recibió el id_token en el Apple callback.');
                return redirect()->away($redirectOrigin . '?error=apple_token_missing');
            }

            // Decodificar el id_token (JWT) de Apple
            $parts = explode('.', $idToken);
            if (count($parts) < 2) {
                Log::error('Formato de id_token inválido.');
                return redirect()->away($redirectOrigin . '?error=apple_invalid_token');
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            
            if (!$payload || !isset($payload['email'])) {
                Log::error('No se pudo obtener el email del id_token de Apple.');
                return redirect()->away($redirectOrigin . '?error=apple_email_missing');
            }

            $email = $payload['email'];

            // Buscar o crear usuario
            $usuario = Usuario::where('email', $email)->first();

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

                // Apple envía los datos del usuario en la primera autenticación en el parámetro 'user'
                $nombre = 'Usuario';
                $apellidos = 'Apple';
                if ($request->has('user')) {
                    try {
                        $appleUser = json_decode($request->input('user'), true);
                        if (isset($appleUser['name'])) {
                            $nombre = $appleUser['name']['firstName'] ?? 'Usuario';
                            $apellidos = $appleUser['name']['lastName'] ?? 'Apple';
                        }
                    } catch (\Exception $e) {
                        Log::error('Error decodificando el parámetro user de Apple: ' . $e->getMessage());
                    }
                }

                $cargo = Cargo::first();

                $usuario = Usuario::create([
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'email' => $email,
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
            $token = $usuario->createToken('apple-sso')->plainTextToken;

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
            Log::error('Error en el proceso de autenticación de Apple: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->away($redirectOrigin . '?error=oauth_exception');
        }
    }
}
