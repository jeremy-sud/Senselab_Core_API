<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Modelo para Tokens OAuth 2.0 de Hacienda
 * 
 * Gestiona los tokens de autenticación con el API de Hacienda.
 */
class FeOAuthToken extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'fe_oauth_tokens';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'ambiente',
        'access_token',
        'token_type',
        'expires_in',
        'expires_at',
        'refresh_token',
        'scope',
        'activo',
        'uso_contador',
        'ultimo_uso',
        'metadata',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'expires_in' => 'integer',
        'expires_at' => 'datetime',
        'activo' => 'boolean',
        'uso_contador' => 'integer',
        'ultimo_uso' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Atributos ocultos para serialización.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Scope: Tokens activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Tokens no expirados.
     */
    public function scopeValidos($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope: Filtrar por ambiente.
     */
    public function scopeAmbiente($query, string $ambiente)
    {
        return $query->where('ambiente', $ambiente);
    }

    /**
     * Scope: Obtener el token válido más reciente para un ambiente.
     */
    public function scopeUltimoValido($query, string $ambiente)
    {
        return $query->ambiente($ambiente)
            ->activos()
            ->validos()
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Accessor: Verificar si el token está próximo a expirar (5 minutos).
     */
    public function getProximoExpirarAttribute(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return Carbon::now()->diffInMinutes($this->expires_at, false) <= 5
            && Carbon::now()->diffInMinutes($this->expires_at, false) > 0;
    }

    /**
     * Accessor: Verificar si el token está expirado.
     */
    public function getExpiradoAttribute(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * Accessor: Verificar si el token es válido (activo y no expirado).
     */
    public function getValidoAttribute(): bool
    {
        return $this->activo && !$this->expirado;
    }

    /**
     * Accessor: Segundos restantes hasta expiración.
     */
    public function getSegundosRestantesAttribute(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        $diff = Carbon::now()->diffInSeconds($this->expires_at, false);
        return max(0, $diff);
    }

    /**
     * Incrementar el contador de uso.
     */
    public function incrementarUso(): void
    {
        $this->increment('uso_contador');
        $this->update(['ultimo_uso' => Carbon::now()]);
    }
}
