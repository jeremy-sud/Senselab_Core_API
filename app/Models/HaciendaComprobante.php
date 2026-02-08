<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para comprobantes electrónicos de Hacienda
 *
 * Representa los comprobantes electrónicos enviados al sistema del
 * Ministerio de Hacienda de Costa Rica.
 *
 * @property int $id
 * @property int $comprobante_id
 * @property int $empresa_id
 * @property string $clave Clave de la factura electrónica (29 dígitos)
 * @property string $tipo_comprobante Tipo: 01=Factura, 03=NotaCredito, 04=NotaDebito, 05=Tiquete, 07=ComprobanteEgreso
 * @property string $estado Estado actual del comprobante (pending, signed, sent, accepted, rejected, error)
 * @property string|null $xml_contedido XML con firma digital
 * @property string|null $respuesta_hacienda Respuesta JSON de Hacienda
 * @property string|null $numero_secuencia Número de secuencia asignado por Hacienda
 * @property string|null $fecha_respuesta Fecha y hora de respuesta de Hacienda
 * @property string|null $mensaje_error Error si lo hay
 * @property array|null $metadatos Información adicional (intentos, duraciones, etc.)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class HaciendaComprobante extends Model
{
    use HasFactory;

    protected $table = 'hacienda_comprobantes';

    protected $fillable = [
        'comprobante_id',
        'empresa_id',
        'clave',
        'tipo_comprobante',
        'estado',
        'xml_contenido',
        'respuesta_hacienda',
        'numero_secuencia',
        'fecha_respuesta',
        'mensaje_error',
        'metadatos',
    ];

    protected $casts = [
        'metadatos' => 'array',
        'fecha_respuesta' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $visible = [
        'id',
        'comprobante_id',
        'empresa_id',
        'clave',
        'tipo_comprobante',
        'estado',
        'numero_secuencia',
        'fecha_respuesta',
        'mensaje_error',
        'created_at',
        'updated_at',
    ];

    // ========== RELACIONES ==========

    /**
     * Relación: Pertenece a un Comprobante
     */
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    /**
     * Relación: Pertenece a una Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    // ========== SCOPES ==========

    /**
     * Scope: Filtrar por estado
     */
    public function scopeByEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope: Filtrar por tipo de comprobante
     */
    public function scopeByTipo($query, string $tipo)
    {
        return $query->where('tipo_comprobante', $tipo);
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopeByEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope: Filtrar por clave
     */
    public function scopeByClave($query, string $clave)
    {
        return $query->where('clave', $clave);
    }

    /**
     * Scope: Comprobantes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('estado', 'pending');
    }

    /**
     * Scope: Comprobantes firmados
     */
    public function scopeSigned($query)
    {
        return $query->where('estado', 'signed');
    }

    /**
     * Scope: Comprobantes enviados
     */
    public function scopeSent($query)
    {
        return $query->where('estado', 'sent');
    }

    /**
     * Scope: Comprobantes aceptados
     */
    public function scopeAccepted($query)
    {
        return $query->where('estado', 'accepted');
    }

    /**
     * Scope: Comprobantes rechazados
     */
    public function scopeRejected($query)
    {
        return $query->where('estado', 'rejected');
    }

    /**
     * Scope: Ordenar por fecha de creación descendente (más recientes primero)
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Comprobantes de los últimos N días
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== MÉTODOS ==========

    /**
     * Actualizar estado del comprobante
     */
    public function updateEstado(string $nuevoEstado, ?string $mensaje = null): bool
    {
        $this->estado = $nuevoEstado;
        if ($mensaje) {
            $this->mensaje_error = $mensaje;
        }
        return $this->save();
    }

    /**
     * Marcar como firmado
     */
    public function markAsSigned(): bool
    {
        return $this->updateEstado('signed');
    }

    /**
     * Marcar como enviado
     */
    public function markAsSent(): bool
    {
        return $this->updateEstado('sent');
    }

    /**
     * Marcar como aceptado
     */
    public function markAsAccepted(string $numeroSecuencia = null): bool
    {
        if ($numeroSecuencia) {
            $this->numero_secuencia = $numeroSecuencia;
        }
        return $this->updateEstado('accepted');
    }

    /**
     * Marcar como rechazado
     */
    public function markAsRejected(string $motivo): bool
    {
        return $this->updateEstado('rejected', $motivo);
    }

    /**
     * Marcar como error
     */
    public function markAsError(string $mensaje): bool
    {
        return $this->updateEstado('error', $mensaje);
    }

    /**
     * Obtener etiqueta legible del estado
     */
    public function getEstadoLabel(): string
    {
        return match($this->estado) {
            'pending' => 'Pendiente',
            'signed' => 'Firmado',
            'sent' => 'Enviado',
            'accepted' => 'Aceptado',
            'rejected' => 'Rechazado',
            'error' => 'Error',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener etiqueta legible del tipo de comprobante
     */
    public function getTipoLabel(): string
    {
        return match($this->tipo_comprobante) {
            '01' => 'Factura Electrónica',
            '03' => 'Nota de Crédito',
            '04' => 'Nota de Débito',
            '05' => 'Tiquete Electrónico',
            '07' => 'Comprobante de Egreso',
            default => 'Desconocido'
        };
    }

    /**
     * Verificar si está listo para envío (estado signed)
     */
    public function isReadyForSending(): bool
    {
        return $this->estado === 'signed';
    }

    /**
     * Verificar si está en estado final
     */
    public function isFinal(): bool
    {
        return in_array($this->estado, ['accepted', 'rejected', 'error']);
    }
}
