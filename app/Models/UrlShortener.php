<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UrlShortener extends Model
{
    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'url_shorter_db';

    /**
     * Nombre de la columna para created_at.
     *
     * @var string
     */
    const CREATED_AT = 'creado_en';

    /**
     * Nombre de la columna para updated_at.
     *
     * @var string
     */
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'url_original',
        'url_corta',
        'slug',
        'clicks',
        'descripcion',
        'expira_en',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'empresa_id' => 'integer',
        'usuario_id' => 'integer',
        'clicks' => 'integer',
        'expira_en' => 'datetime',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Relación con Empresa.
     *
     * @return BelongsTo
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con Usuario.
     *
     * @return BelongsTo
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para filtrar solo URLs activas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar URLs no eliminadas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNoEliminados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('eliminado', false);
    }

    /**
     * Scope para filtrar URLs no expiradas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNoExpirados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expira_en')
              ->orWhere('expira_en', '>', now());
        });
    }

    /**
     * Incrementar contador de clicks.
     *
     * @return void
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Verificar si la URL está expirada.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expira_en) {
            return false;
        }

        return $this->expira_en->isPast();
    }

    /**
     * Verificar si la URL está disponible (activa, no eliminada, no expirada).
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->activo && !$this->eliminado && !$this->isExpired();
    }
}
