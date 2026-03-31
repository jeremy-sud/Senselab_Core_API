<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\WebhookFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasActiveScope;

    protected $table = 'webhooks';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /** Eventos disponibles para suscripción */
    const EVENTOS_DISPONIBLES = [
        'venta.creada',
        'factura.emitida',
        'pago.recibido',
        'inventario.bajo',
        'cliente.creado',
    ];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'url',
        'eventos',
        'secret',
        'descripcion',
        'timeout_segundos',
        'max_reintentos',
        'activo',
        'eliminado',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'eventos' => 'array',
        'timeout_segundos' => 'integer',
        'max_reintentos' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'webhook_id');
    }

    /**
     * Verifica si el webhook está suscrito a un evento específico.
     *
     * @param string $evento
     * @return bool
     */
    public function escuchaEvento(string $evento): bool
    {
        return in_array($evento, $this->eventos ?? [], true);
    }
}
