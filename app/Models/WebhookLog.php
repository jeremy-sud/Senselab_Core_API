<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
/** @use HasFactory<\Database\Factories\WebhookLogFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookLogFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'webhook_logs';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EXITOSO = 'exitoso';
    const ESTADO_FALLIDO = 'fallido';

    protected $fillable = [
        'webhook_id',
        'empresa_id',
        'evento',
        'estado',
        'codigo_respuesta',
        'latencia_ms',
        'payload_size',
        'payload',
        'respuesta',
        'error',
        'intento',
        'proximo_reintento_en',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'codigo_respuesta' => 'integer',
        'latencia_ms' => 'integer',
        'payload_size' => 'integer',
        'intento' => 'integer',
        'proximo_reintento_en' => 'datetime',
        'creado_en' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class, 'webhook_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    public function esExitoso(): bool
    {
        return $this->estado === self::ESTADO_EXITOSO;
    }

    public function esFallido(): bool
    {
        return $this->estado === self::ESTADO_FALLIDO;
    }
}
