<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modelo GdprDeletionRequest - Solicitudes de Derecho al Olvido GDPR
 *
 * Gestiona solicitudes de eliminación de datos según GDPR.
 * Proporciona trazabilidad completa de solicitudes y cumplimiento.
 *
 * @package App\Models
 * @version 1.0.0 - FASE 3 Compliance
 */
class GdprDeletionRequest extends Model
{
    protected $table = 'gdpr_deletion_requests';

    protected $fillable = [
        'user_id',
        'email',
        'request_type',
        'status',
        'approved_at',
        'completed_at',
        'approved_by',
        'reason',
        'scope',
        'data_summary',
        'ip_address',
        'action_log',
        'rejection_reason',
        'gdpr_request_id',
        'verified_identity',
        'identity_verified_at',
        'verification_method',
        'delete_after',
        'retry_count',
        'last_error',
    ];

    protected $casts = [
        'scope' => 'array',
        'data_summary' => 'array',
        'action_log' => 'array',
        'verified_identity' => 'boolean',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'identity_verified_at' => 'datetime',
        'delete_after' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
    ];

    // ════════════════════════════════════════════════════════════════
    // RELACIONES
    // ════════════════════════════════════════════════════════════════

    /**
     * Usuario que solicita eliminación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin que aprobó la solicitud
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════

    /**
     * Scope: Solicitudes pendientes de aprobación
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Solicitudes aprobadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Solicitudes completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Solicitudes que vencen hoy
     */
    public function scopeDueToday($query)
    {
        return $query->where('delete_after', '<=', Carbon::now())
                     ->where('status', 'approved')
                     ->where('completed_at', null);
    }

    /**
     * Scope: Por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Por intervalo de fechas
     */
    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Con identidad verificada
     */
    public function scopeVerified($query)
    {
        return $query->where('verified_identity', true);
    }

    // ════════════════════════════════════════════════════════════════
    // MÉTODOS DE NEGOCIO
    // ════════════════════════════════════════════════════════════════

    /**
     * Generar ID único de solicitud GDPR para tracking legal
     */
    public function generateGdprRequestId(): string
    {
        if ($this->gdpr_request_id) {
            return $this->gdpr_request_id;
        }

        $id = 'GDPR-' . strtoupper(bin2hex(random_bytes(8))) . '-' . $this->id;
        $this->update(['gdpr_request_id' => $id]);

        return $id;
    }

    /**
     * Verificar identidad del usuario
     */
    public function verifyIdentity(string $method): bool
    {
        if ($this->verified_identity) {
            return true;
        }

        // Aquí iría lógica de verificación (email confirmation, 2FA, etc)
        $this->update([
            'verified_identity' => true,
            'identity_verified_at' => Carbon::now(),
            'verification_method' => $method,
        ]);

        $this->logAction('verified_identity', "Identidad verificada por {$method}");

        return true;
    }

    /**
     * Aprobar solicitud de eliminación
     */
    public function approve(int $approverId, ?string $reason = null): bool
    {
        if (!$this->verified_identity) {
            throw new \Exception('No se puede aprobar solicitud sin identidad verificada');
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => Carbon::now(),
            'approved_by' => $approverId,
            'delete_after' => Carbon::now()->addDays(30), // GDPR: máximo 30 días
        ]);

        $this->logAction('approved', "Solicitud aprobada por admin #{$approverId}");

        return true;
    }

    /**
     * Rechazar solicitud
     */
    public function reject(int $rejectedBy, string $reason): bool
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'rejection_reason' => $reason,
        ]);

        $this->logAction('rejected', "Solicitud rechazada: {$reason}");

        return true;
    }

    /**
     * Marcar como procesando
     */
    public function markAsProcessing(): bool
    {
        $this->update(['status' => 'processing']);
        $this->logAction('processing', 'Iniciando proceso de eliminación');

        return true;
    }

    /**
     * Marcar como completada
     */
    public function markAsCompleted(): bool
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

        $this->logAction('completed', 'Eliminación de datos completada');

        return true;
    }

    /**
     * Registrar intento fallido de eliminación
     */
    public function recordFailedAttempt(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);

        $this->logAction('failed', "Error: {$error}");
    }

    /**
     * Registrar acción en el log
     */
    public function logAction(string $action, string $description): void
    {
        $log = $this->action_log ?? [];
        $log[] = [
            'action' => $action,
            'description' => $description,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];

        $this->update(['action_log' => $log]);
    }

    /**
     * Obtener resumen de datos a eliminar
     */
    public function getDataSummaryReport(): array
    {
        return [
            'request_id' => $this->gdpr_request_id ?? $this->generateGdprRequestId(),
            'user_id' => $this->user_id,
            'user_email' => $this->email ?? $this->user?->email,
            'request_type' => $this->request_type,
            'scope' => $this->scope ?? 'all_personal_data',
            'data_categories' => $this->data_summary ?? [],
            'created' => $this->created_at,
            'deadline' => $this->delete_after ?? Carbon::now()->addDays(30),
        ];
    }

    /**
     * Convertir a respuesta de API
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->gdpr_request_id ?? $this->generateGdprRequestId(),
            'status' => $this->status,
            'request_type' => $this->request_type,
            'created_at' => $this->created_at->toIso8601String(),
            'deadline' => $this->delete_after?->toIso8601String(),
            'verified' => $this->verified_identity,
            'message' => $this->getStatusMessage(),
        ];
    }

    /**
     * Obtener mensaje de estado amigable
     */
    public function getStatusMessage(): string
    {
        $messages = [
            'pending' => 'Tu solicitud está siendo revisada. Por favor verifica tu identidad.',
            'approved' => 'Tu solicitud fue aprobada. Tus datos serán eliminados en 30 días.',
            'processing' => 'Estamos eliminando tus datos. Por favor no borres este correo.',
            'completed' => 'Tus datos han sido eliminados completamente.',
            'rejected' => 'Tu solicitud fue rechazada: ' . $this->rejection_reason,
            'failed' => 'Hubo un error al procesar tu solicitud. Por favor intenta de nuevo.',
        ];

        return $messages[$this->status] ?? 'Estado desconocido';
    }
}
