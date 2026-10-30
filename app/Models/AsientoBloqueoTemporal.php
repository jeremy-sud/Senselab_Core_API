<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsientoBloqueoTemporal extends Model
{
    use HasFactory, BelongsToTenant;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'asientos_bloqueos_temporales';

    protected $fillable = [
        'empresa_id',
        'horario_ruta_id',
        'asiento_numero',
        'bus_asiento_id',
        'parada_origen_id',
        'parada_destino_id',
        'token_bloqueo',
        'pasarela_referencia',
        'metodo_pago',
        'monto_pago',
        'datos_transaccion',
        'session_id',
        'usuario_id',
        'cliente_id',
        'ip_address',
        'estado',
        'bloqueado_en',
        'expira_en',
    ];

    protected $casts = [
        'monto_pago' => 'decimal:2',
        'datos_transaccion' => 'array',
        'bloqueado_en' => 'datetime',
        'expira_en' => 'datetime',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function horarioRuta(): BelongsTo
    {
        return $this->belongsTo(HorarioRuta::class, 'horario_ruta_id');
    }

    public function busAsiento(): BelongsTo
    {
        return $this->belongsTo(BusAsiento::class, 'bus_asiento_id');
    }

    public function paradaOrigen(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_origen_id');
    }

    public function paradaDestino(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'bloqueado')
            ->where('expira_en', '>', now());
    }

    public function isActivo(): bool
    {
        return $this->estado === 'bloqueado' && $this->expira_en && $this->expira_en->isFuture();
    }
}
