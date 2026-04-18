<?php

namespace App\Http\Controllers;

use App\Models\GdprDeletionRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

/**
 * Controller: GdprController - Solicitudes de Derecho al Olvido GDPR
 *
 * Maneja solicitudes de eliminación de datos de usuarios conforme GDPR.
 * Proporciona endpoints para:
 * - Crear nuevas solicitudes
 * - Verificar identidad
 * - Ver estado de solicitudes
 * - Aprobar/rechazar (admin)
 *
 * @OA\Tag(
 *     name="GDPR",
 *     description="Gestión de solicitudes de derecho al olvido y eliminación de datos conforme GDPR"
 * )
 *
 * @package App\Http\Controllers
 * @version 1.0.0 - FASE 3
 */
class GdprController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/gdpr/requests",
     *     summary="Crear solicitud de eliminación GDPR",
     *     description="Crea una nueva solicitud de eliminación de datos personales. Envía un código de verificación por email",
     *     operationId="createGdprRequest",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"request_type"},
     *             @OA\Property(property="request_type", type="string", enum={"account", "data", "all"}, example="data"),
     *             @OA\Property(property="reason", type="string", maxLength=1000, example="Deseo eliminar mis datos personales"),
     *             @OA\Property(property="scope", type="array", @OA\Items(type="string"), example={"personal_data", "invoices"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Solicitud creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="request_id", type="string"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="next_step", type="string", example="verify_identity")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_type' => 'required|in:account,data,all',
            'reason' => 'nullable|string|max:1000',
            'scope' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();

            $gdprRequest = GdprDeletionRequest::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'request_type' => $validated['request_type'],
                'status' => 'pending',
                'reason' => $validated['reason'] ?? null,
                'scope' => $validated['scope'] ?? ['all_personal_data'],
                'ip_address' => $request->ip(),
            ]);

            // Registrar en auditoría
            AuditLog::create([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'auditable_type' => GdprDeletionRequest::class,
                'auditable_id' => $gdprRequest->id,
                'action' => 'created',
                'new_values' => $gdprRequest->toArray(),
                'ip_address' => $request->ip(),
                'request_method' => 'POST',
                'request_path' => 'api/gdpr/requests',
                'involves_sensitive_data' => true,
                'change_reason' => 'Nueva solicitud de derecho al olvido GDPR',
            ]);

            // Generar código de verificación y guardarlo en caché por 15 minutos
            $verificationCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            \Illuminate\Support\Facades\Cache::put(
                'gdpr_verification_' . $gdprRequest->id,
                $verificationCode,
                now()->addMinutes(15)
            );

            // Enviar código de verificación por email al usuario
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Su código de verificación GDPR es: {$verificationCode}\n\nEste código expira en 15 minutos.",
                    function ($message) use ($user) {
                        $message->to($user->email)
                                ->subject('Código de verificación GDPR - Ursol CAST');
                    }
                );
            } catch (\Exception $mailException) {
                \Illuminate\Support\Facades\Log::warning('No se pudo enviar email de verificación GDPR', [
                    'user_id' => $user->id,
                    'error' => $mailException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Solicitud creada exitosamente. Por favor verifica tu identidad con el código enviado a tu correo.',
                'request_id' => $gdprRequest->generateGdprRequestId(),
                'status' => 'pending',
                'next_step' => 'verify_identity',
                // 'debug_code' => $verificationCode // Solo para desarrollo
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error creando solicitud: ' . $e->getMessage() : 'Error creando solicitud',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/gdpr/requests/{id}",
     *     summary="Ver estado de solicitud GDPR",
     *     description="Obtiene el estado y detalles de una solicitud de eliminación GDPR",
     *     operationId="getGdprRequest",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la solicitud GDPR (o gdpr_request_id)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solicitud encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="request", type="object"),
     *             @OA\Property(property="details", type="object",
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deadline", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="action_log", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Solicitud no encontrada")
     * )
     */
    public function getRequest(string $id): JsonResponse
    {
        try {
            $gdprRequest = GdprDeletionRequest::where('gdpr_request_id', $id)
                                              ->orWhere('id', $id)
                                              ->firstOrFail();

            // Verificar que es el usuario dueño
            if ($gdprRequest->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            return response()->json([
                'success' => true,
                'request' => $gdprRequest->toApiResponse(),
                'details' => [
                    'created_at' => $gdprRequest->created_at?->toIso8601String(),
                    'updated_at' => $gdprRequest->updated_at?->toIso8601String(),
                    'deadline' => $gdprRequest->delete_after?->toIso8601String(),
                    'action_log' => $gdprRequest->action_log,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Solicitud no encontrada'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/gdpr/requests/{id}/verify",
     *     summary="Verificar identidad del usuario",
     *     description="Verifica la identidad del solicitante mediante código enviado por email. Requerido antes de aprobación",
     *     operationId="verifyGdprIdentity",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la solicitud GDPR",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"verification_code", "method"},
     *             @OA\Property(property="verification_code", type="string", example="123456"),
     *             @OA\Property(property="method", type="string", enum={"email", "2fa", "security_questions"}, example="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Identidad verificada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string", example="verified"),
     *             @OA\Property(property="next_step", type="string", example="wait_approval")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Código expirado, incorrecto o ya verificado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function verifyIdentity(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_code' => 'required|string', // Código enviado por email
            'method' => 'required|in:email,2fa,security_questions',
        ]);

        try {
            $gdprRequest = GdprDeletionRequest::where('gdpr_request_id', $id)
                                              ->orWhere('id', $id)
                                              ->firstOrFail();

            // Verificar permisos
            if ($gdprRequest->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Verificar que no haya sido verificada ya
            if ($gdprRequest->verified_identity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identidad ya fue verificada',
                ], 400);
            }

            // Lógica real de verificación con caché
            $cachedCode = \Illuminate\Support\Facades\Cache::get('gdpr_verification_' . $gdprRequest->id);
            
            if (!$cachedCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código de verificación ha expirado o no existe. Solicite uno nuevo.',
                ], 400);
            }

            if ($cachedCode !== $validated['verification_code']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código de verificación incorrecto.',
                ], 400);
            }

            // Limpiar caché tras verificación exitosa
            \Illuminate\Support\Facades\Cache::forget('gdpr_verification_' . $gdprRequest->id);

            // Marcar como verificada
            $gdprRequest->verifyIdentity($validated['method']);

            return response()->json([
                'success' => true,
                'message' => 'Identidad verificada exitosamente',
                'status' => 'verified',
                'next_step' => 'wait_approval',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error verificando identidad: ' . $e->getMessage() : 'Error verificando identidad',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/gdpr/requests/{id}/approve",
     *     summary="Aprobar solicitud GDPR (Admin)",
     *     description="Aprueba una solicitud de eliminación GDPR. Solo administradores. Requiere identidad verificada",
     *     operationId="approveGdprRequest",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la solicitud GDPR",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solicitud aprobada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string", example="approved"),
     *             @OA\Property(property="deadline", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Identidad no verificada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Solo administradores"),
     *     @OA\Response(response=404, description="Solicitud no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function approveRequest(string $id, Request $request): JsonResponse
    {
        // Verificar que es admin
        if (!Auth::user()?->hasRole('admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $gdprRequest = GdprDeletionRequest::where('gdpr_request_id', $id)
                                              ->orWhere('id', $id)
                                              ->firstOrFail();

            if (!$gdprRequest->verified_identity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario aún no ha verificado identidad',
                ], 400);
            }

            $gdprRequest->approve(Auth::id());

            // Registrar aprobación en auditoría
            AuditLog::create([
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'user_name' => Auth::user()->name,
                'auditable_type' => GdprDeletionRequest::class,
                'auditable_id' => $gdprRequest->id,
                'action' => 'updated',
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'approved'],
                'ip_address' => $request->ip(),
                'request_method' => 'POST',
                'request_path' => "api/gdpr/requests/{$id}/approve",
                'involves_sensitive_data' => true,
                'change_reason' => 'Admin aprobó solicitud GDPR',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada',
                'status' => 'approved',
                'deadline' => $gdprRequest->delete_after?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error aprobando solicitud: ' . $e->getMessage() : 'Error aprobando solicitud',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/gdpr/requests/{id}/reject",
     *     summary="Rechazar solicitud GDPR (Admin)",
     *     description="Rechaza una solicitud de eliminación GDPR con razón obligatoria. Solo administradores",
     *     operationId="rejectGdprRequest",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la solicitud GDPR",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", maxLength=500, example="Datos requeridos por regulación tributaria")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solicitud rechazada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string", example="rejected")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Solo administradores"),
     *     @OA\Response(response=404, description="Solicitud no encontrada"),
     *     @OA\Response(response=422, description="Razón requerida"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function rejectRequest(string $id, Request $request): JsonResponse
    {
        if (!Auth::user()?->hasRole('admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $gdprRequest = GdprDeletionRequest::where('gdpr_request_id', $id)
                                              ->orWhere('id', $id)
                                              ->firstOrFail();

            $gdprRequest->reject(Auth::id(), $validated['reason']);

            AuditLog::create([
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'user_name' => Auth::user()->name,
                'auditable_type' => GdprDeletionRequest::class,
                'auditable_id' => $gdprRequest->id,
                'action' => 'updated',
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'rejected'],
                'ip_address' => $request->ip(),
                'request_method' => 'POST',
                'request_path' => "api/gdpr/requests/{$id}/reject",
                'involves_sensitive_data' => true,
                'change_reason' => 'Admin rechazó solicitud GDPR',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada',
                'status' => 'rejected',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error rechazando solicitud: ' . $e->getMessage() : 'Error rechazando solicitud',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/gdpr/requests",
     *     summary="Listar solicitudes GDPR del usuario",
     *     description="Lista las solicitudes de eliminación GDPR del usuario autenticado",
     *     operationId="listGdprRequests",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solicitudes listadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function listRequests(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $requests = GdprDeletionRequest::where('user_id', $user->id)
                                          ->latest()
                                          ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $requests->items(),
                'pagination' => [
                    'total' => $requests->total(),
                    'per_page' => $requests->perPage(),
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error listando solicitudes: ' . $e->getMessage() : 'Error listando solicitudes',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/gdpr/stats",
     *     summary="Estadísticas GDPR (Admin)",
     *     description="Obtiene estadísticas globales de solicitudes GDPR. Solo administradores",
     *     operationId="getGdprStats",
     *     tags={"GDPR"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total_requests", type="integer"),
     *                 @OA\Property(property="pending_requests", type="integer"),
     *                 @OA\Property(property="approved_requests", type="integer"),
     *                 @OA\Property(property="completed_requests", type="integer"),
     *                 @OA\Property(property="failed_requests", type="integer"),
     *                 @OA\Property(property="rejected_requests", type="integer"),
     *                 @OA\Property(property="requests_last_30_days", type="integer"),
     *                 @OA\Property(property="due_today", type="integer"),
     *                 @OA\Property(property="no_identity_verified", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Solo administradores"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getStats(Request $request): JsonResponse
    {
        if (!Auth::user()?->hasRole('admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $now = Carbon::now();
            $thirtyDaysAgo = $now->copy()->subDays(30);

            $stats = [
                'total_requests' => GdprDeletionRequest::count(),
                'pending_requests' => GdprDeletionRequest::where('status', 'pending')->count(),
                'approved_requests' => GdprDeletionRequest::where('status', 'approved')->count(),
                'completed_requests' => GdprDeletionRequest::where('status', 'completed')->count(),
                'failed_requests' => GdprDeletionRequest::where('status', 'failed')->count(),
                'rejected_requests' => GdprDeletionRequest::where('status', 'rejected')->count(),
                'requests_last_30_days' => GdprDeletionRequest::whereBetween('created_at', [$thirtyDaysAgo, $now])
                                                               ->count(),
                'due_today' => GdprDeletionRequest::dueToday()->count(),
                'no_identity_verified' => GdprDeletionRequest::where('verified_identity', false)->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error obteniendo estadísticas: ' . $e->getMessage() : 'Error obteniendo estadísticas',
            ], 500);
        }
    }
}
