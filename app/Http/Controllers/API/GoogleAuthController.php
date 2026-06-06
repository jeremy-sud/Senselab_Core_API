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
            $email = strtolower(trim($googleUser->getEmail()));
            $usuario = Usuario::where('email', $email)->first();

            if (!$usuario) {
                // Generar una nueva empresa aislada para cualquier usuario de Google (incluidos jasmm222 y deadmooncr)
                $fullName = $googleUser->getName() ?: 'Usuario';
                $empresaNombre = $fullName . ' Labs S.A.';
                $empresa = Empresa::create([
                    'nombre' => $empresaNombre,
                    'nombre_comercial' => $fullName . ' Labs',
                    'razon_social' => $empresaNombre,
                    'num_identificacion_dgt' => '3-101-' . str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                    'tipo_identificacion' => '02', // '02' = Cédula Jurídica
                    'activo' => true,
                ]);

                // Separar nombre y apellidos si es posible
                $fullName = $googleUser->getName() ?: 'Usuario';
                $parts = explode(' ', $fullName, 2);
                $nombre = $parts[0];
                $apellidos = $parts[1] ?? 'Google';

                // Si es un correo fundador exceptuado, asignarle el cargo 'Fundador'
                $isFounderEmail = in_array($email, ['jasmm222@gmail.com', 'deadmooncr@gmail.com']);
                if ($isFounderEmail) {
                    $cargo = Cargo::where('nombre', 'Fundador')->first()
                        ?: Cargo::where('nombre', 'Administrador')->first()
                        ?: Cargo::where('nombre', 'like', '%Admin%')->first()
                        ?: Cargo::first();
                } else {
                    $cargo = Cargo::where('nombre', 'Administrador')->first()
                        ?: Cargo::where('nombre', 'like', '%Admin%')->first()
                        ?: Cargo::first();
                }

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

                // Inicializar suscripción y uso para el nuevo inquilino
                $plan = $isFounderEmail ? 'business' : 'free';
                $tenantId = 'sl_tenant_' . str_pad((string)$empresa->id, 6, '0', STR_PAD_LEFT);
                \App\Models\Subscription::create([
                    'tenant_id' => $tenantId,
                    'empresa_id' => $empresa->id,
                    'usuario_id' => $usuario->id,
                    'plan' => $plan,
                    'status' => 'active',
                    'max_users' => $plan === 'business' ? 999999 : 1,
                    'max_invoices_month' => $plan === 'business' ? 999999 : 10,
                    'max_ai_queries_month' => $plan === 'business' ? 999999 : 5,
                    'current_period_end' => now()->addYear(),
                ]);

                \App\Models\TenantUsage::create([
                    'tenant_id' => $tenantId,
                    'active_users_count' => 1,
                    'invoices_count_current_month' => 0,
                    'ai_queries_count_current_month' => 0,
                ]);
            }

            // Verificar si el usuario está inactivo o eliminado
            if (!$usuario->activo || $usuario->eliminado) {
                return redirect()->away($redirectOrigin . '?error=user_inactive');
            }

            // Generar Token de Laravel Sanctum
            $token = $usuario->createToken('google-sso')->plainTextToken;

            // Retornar al frontend con el token y los datos de usuario
            $subscription = \App\Models\Subscription::where('empresa_id', $usuario->empresa_id)->first();
            $isFounderEmail = in_array($usuario->email, ['admin@scisenselab.com', 'admin@senselab.com', 'jasmm222@gmail.com', 'deadmooncr@gmail.com']);
            $plan = $subscription ? $subscription->plan : (($isFounderEmail || $usuario->empresa_id == 1) ? 'business' : 'free');

            $userData = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'empresa_id' => $usuario->empresa_id,
                'plan' => $plan,
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
