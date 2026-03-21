<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\MensajeHaciendaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `mensajes_hacienda`.
 * Gestiona mensajes de respuesta de Hacienda (DGT) para facturación electrónica.
 *
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MensajeHacienda extends Model
{
    /** @use HasFactory<\Database\Factories\MensajeHaciendaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'mensajes_hacienda';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'comprobante_id',
        'clave_numerica',
        'tipo_mensaje',
        'codigo_respuesta',
        'detalle_mensaje',
        'xml_respuesta',
        'fecha_emision',
        'fecha_procesamiento',
        'estado',
        'intentos_envio',
        'ultimo_error',
        'eliminado',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_procesamiento' => 'datetime',
        'intentos_envio' => 'integer',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function comprobante(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ComprobanteRecibidoElectronico::class, 'comprobante_id');
    }

    /* --------------------- Scopes --------------------- */

    public function scopePendientes(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeProcesados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'procesado');
    }

    public function scopeConError(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'error');
    }

    public function scopePorTipo(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo_mensaje', $tipo);
    }

    /* --------------------- Métodos --------------------- */

    public function esPendiente(): mixed
    {
        return $this->estado === 'pendiente';
    }

    public function esProcesado(): mixed
    {
        return $this->estado === 'procesado';
    }

    public function tieneError(): mixed
    {
        return $this->estado === 'error';
    }

    public function incrementarIntentos(): void
    {
        $this->increment('intentos_envio');
    }

    public function marcarComoProcesado(): void
    {
        $this->update([
            'estado' => 'procesado',
            'fecha_procesamiento' => now(),
        ]);
    }

    public function marcarComoError(mixed $mensaje): void
    {
        $this->update([
            'estado' => 'error',
            'ultimo_error' => $mensaje,
        ]);
    }
}
