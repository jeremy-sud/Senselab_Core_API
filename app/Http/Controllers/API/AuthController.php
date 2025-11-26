<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    #[OA\Post(
        path: '/api/login',
        operationId: 'login',
        summary: 'Iniciar sesión',
        description: 'Autentica un usuario con email y contraseña. Devuelve un token Bearer para usar en las siguientes peticiones.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@ursol.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'usuario', ref: '#/components/schemas/Usuario'),
                                new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                                new OA\Property(
                                    property: 'permisos',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'productos.ver')
                                )
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Login exitoso')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Credenciales incorrectas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Las credenciales son incorrectas.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'email',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Las credenciales son incorrectas.')
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {

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
            'message' => 'Login exitoso',
            'user' => new UsuarioResource($usuario),
            'token' => $token,
            'permissions' => $usuario->getAllPermissions(),
        ]);
    }

    /**
     * Logout de usuario
     */
    #[OA\Post(
        path: '/api/logout',
        operationId: 'logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token actual del usuario autenticado',
        security: [['sanctum' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout exitoso')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        
        // Revocar el token actual
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    #[OA\Get(
        path: '/api/user',
        operationId: 'getCurrentUser',
        summary: 'Obtener usuario autenticado',
        description: 'Devuelve la información del usuario autenticado incluyendo sus roles, permisos, empresa y cargo',
        security: [['sanctum' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'usuario', ref: '#/components/schemas/Usuario'),
                                new OA\Property(
                                    property: 'permisos',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'productos.ver')
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            )
        ]
    )]
    public function me(Request $request): UsuarioResource
    {
        $usuario = $request->user();
        $usuario->load(['roles.permisos', 'empresa', 'cargo']);
        
        return new UsuarioResource($usuario);
    }
}
