<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DataRetentionPolicy;
use App\Services\ComplianceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

/**
 * Controller: ComplianceDashboardController - Dashboard de Compliance GDPR
 *
 * Proporciona métricas, reportes y estadísticas sobre:
 * - Cumplimiento GDPR
 * - Auditoría de cambios
 * - Retención de datos
 * - Solicitudes de derecho al olvido
 *
 * @OA\Tag(
 *     name="Compliance",
 *     description="Dashboard de cumplimiento GDPR, auditoría, retención de datos y reportes de compliance"
 * )
 *
 * @package App\Http\Controllers
 * @version 1.0.0 - FASE 3
 */
class ComplianceDashboardController extends Controller
{
    /**
     * Middleware de autorización
     */
    public function __construct(
        private readonly ComplianceDashboardService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('can:view compliance dashboard');
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/dashboard",
     *     summary="Panel principal de compliance",
     *     description="Obtiene un resumen del estado de compliance: auditoría, GDPR, retención de datos y protección",
     *     operationId="getComplianceDashboard",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="dashboard", type="object",
     *                 @OA\Property(property="audit_logs", type="object"),
     *                 @OA\Property(property="gdpr_requests", type="object"),
     *                 @OA\Property(property="retention_policies", type="object"),
     *                 @OA\Property(property="data_protection", type="object"),
     *                 @OA\Property(property="recent_sensitive_changes", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos de compliance"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $summary = $this->service->getDashboardSummary();

            return response()->json([
                'success' => true,
                'dashboard' => $summary,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error obteniendo dashboard: ' . $e->getMessage() : 'Error obteniendo dashboard',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/audit-logs",
     *     summary="Listar audit logs",
     *     description="Obtiene logs de auditoría con filtros complejos: acción, usuario, modelo, datos sensibles, rango de fechas",
     *     operationId="getComplianceAuditLogs",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="action", in="query", required=false, description="Filtrar por acción",
     *         @OA\Schema(type="string", enum={"created", "updated", "deleted"})
     *     ),
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="model_type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="sensitive_only", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="ip", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Response(
     *         response=200,
     *         description="Logs obtenidos",
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
     *     @OA\Response(response=403, description="Sin permisos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only(['action', 'user_id', 'model_type', 'ip', 'date_from', 'date_to']);
            if ($request->boolean('sensitive_only')) {
                $filtros['sensitive_only'] = true;
            }

            $logs = $this->service->getAuditLogs($filtros, (int) $request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error obteniendo logs: ' . $e->getMessage() : 'Error obteniendo logs',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/audit-logs/{id}",
     *     summary="Detalle de audit log",
     *     description="Obtiene el detalle completo de un registro de auditoría específico",
     *     operationId="getComplianceAuditLogDetail",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del audit log",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle del log",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="summary", type="string"),
     *                 @OA\Property(property="changes", type="object"),
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="context", type="object"),
     *                 @OA\Property(property="sensitive_data", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Log no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getAuditLogDetail(string $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getAuditLogDetail($id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Log no encontrado'], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/retention-policies",
     *     summary="Listar políticas de retención",
     *     description="Obtiene las políticas de retención de datos configuradas",
     *     operationId="getRetentionPolicies",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Políticas listadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getRetentionPolicies(Request $request): JsonResponse
    {
        try {
            $policies = $this->service->getRetentionPolicies((int) $request->input('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $policies->items(),
                'pagination' => [
                    'total' => $policies->total(),
                    'per_page' => $policies->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error obteniendo políticas: ' . $e->getMessage() : 'Error obteniendo políticas',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/retention-policies/{id}",
     *     summary="Detalle de política de retención",
     *     description="Obtiene detalles completos de una política de retención de datos incluyendo estadísticas de ejecución",
     *     operationId="getRetentionPolicyDetail",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la política",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle de política",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="configuration", type="object"),
     *                 @OA\Property(property="statistics", type="object"),
     *                 @OA\Property(property="creator", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Política no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getRetentionPolicyDetail(string $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->getRetentionPolicyDetail($id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Política no encontrada'], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/compliance/retention-policies/{id}/execute",
     *     summary="Ejecutar política de retención",
     *     description="Ejecuta manualmente una política de retención de datos. Requiere que la política esté habilitada",
     *     operationId="executeRetentionPolicy",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la política",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Política ejecutada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="details", type="object",
     *                 @OA\Property(property="action", type="string"),
     *                 @OA\Property(property="affected_rows", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Política deshabilitada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Política no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function executeRetentionPolicy(string $id, Request $request): JsonResponse
    {
        try {
            $result = $this->service->executeRetentionPolicy($id);

            // Registrar en auditoría
            AuditLog::create([
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'user_name' => Auth::user()->name,
                'auditable_type' => DataRetentionPolicy::class,
                'auditable_id' => $id,
                'action' => 'updated',
                'new_values' => ['last_execution_at' => Carbon::now()],
                'ip_address' => $request->ip(),
                'request_method' => 'POST',
                'request_path' => "api/compliance/retention-policies/{$id}/execute",
                'change_reason' => "Política de retención ejecutada manualmente por {$request->user()->email}",
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => [
                    'action' => $result['action'] ?? null,
                    'affected_rows' => $result['affected'] ?? 0,
                ],
            ]);
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error ejecutando política: ' . $e->getMessage() : 'Error ejecutando política',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compliance/report/gdpr",
     *     summary="Reporte de cumplimiento GDPR",
     *     description="Genera un reporte detallado de cumplimiento GDPR para el período especificado",
     *     operationId="getGdprComplianceReport",
     *     tags={"Compliance"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="date_from", in="query", required=false, description="Fecha inicio (default: 3 meses atrás)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(name="date_to", in="query", required=false, description="Fecha fin (default: hoy)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte generado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="report", type="object",
     *                 @OA\Property(property="period", type="object"),
     *                 @OA\Property(property="deletion_requests", type="object"),
     *                 @OA\Property(property="sensitive_data_access", type="object"),
     *                 @OA\Property(property="data_retention_compliance", type="object"),
     *                 @OA\Property(property="user_activity", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getGdprComplianceReport(Request $request): JsonResponse
    {
        try {
            $from = Carbon::parse($request->input('date_from', Carbon::now()->subMonths(3)));
            $to = Carbon::parse($request->input('date_to', Carbon::now()));

            $report = $this->service->getGdprComplianceReport($from, $to);

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? 'Error generando reporte: ' . $e->getMessage() : 'Error generando reporte',
            ], 500);
        }
    }
}
