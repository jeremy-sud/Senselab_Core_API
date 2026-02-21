<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
/** @use HasFactory<\Database\Factories\SesionUsuarioFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $token_hash
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $ultimo_acceso
 * @property bool $activo
 * @property \Carbon\Carbon|null $creado_en
 * @property-read Usuario|null $usuario
 * @property-read string $navegador
 * @property-read string $sistema_operativo
 */
class SesionUsuario extends Model
{
    /** @use HasFactory<\Database\Factories\SesionUsuarioFactory> */
    use HasFactory;

    protected $table = 'sesiones_usuarios';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null; // Solo tiene timestamp de creación

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'ultimo_acceso',
        'activo',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        'creado_en' => 'datetime',
    ];

    /**
     * Relación con el usuario
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para sesiones activas
     *
     * @param Builder<SesionUsuario> $query
     * @return Builder<SesionUsuario>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para sesiones inactivas
     *
     * @param Builder<SesionUsuario> $query
     * @return Builder<SesionUsuario>
     */
    public function scopeInactivas(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    /**
     * Scope para sesiones de un usuario específico
     *
     * @param Builder<SesionUsuario> $query
     * @param int $usuarioId
     * @return Builder<SesionUsuario>
     */
    public function scopePorUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para sesiones expiradas (más de 24 horas sin actividad)
     *
     * @param Builder<SesionUsuario> $query
     * @param int $horas
     * @return Builder<SesionUsuario>
     */
    public function scopeExpiradas(Builder $query, int $horas = 24): Builder
    {
        return $query->where('ultimo_acceso', '<', now()->subHours($horas))
            ->where('activo', true);
    }

    /**
     * Scope para sesiones recientes (última hora)
     *
     * @param Builder<SesionUsuario> $query
     * @return Builder<SesionUsuario>
     */
    public function scopeRecientes(Builder $query): Builder
    {
        return $query->where('ultimo_acceso', '>=', now()->subHour());
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarAcceso(): bool
    {
        return $this->update(['ultimo_acceso' => now()]);
    }

    /**
     * Desactivar sesión
     */
    public function desactivar(): bool
    {
        return $this->update(['activo' => false]);
    }

    /**
     * Activar sesión
     */
    public function activar(): bool
    {
        return $this->update([
            'activo' => true,
            'ultimo_acceso' => now(),
        ]);
    }

    /**
     * Verificar si la sesión está expirada
     */
    public function estaExpirada(int $horas = 24): bool
    {
        return $this->ultimo_acceso < now()->subHours($horas);
    }

    /**
     * Crear nueva sesión
     */
    public static function crearSesion(int $usuarioId, string $token): self
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'token_hash' => hash('sha256', $token),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'ultimo_acceso' => now(),
            'activo' => true,
        ]);
    }

    /**
     * Cerrar todas las sesiones de un usuario
     */
    public static function cerrarSesionesUsuario(int $usuarioId): int
    {
        return self::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->update(['activo' => false]);
    }

    /**
     * Limpiar sesiones expiradas
     */
    public static function limpiarExpiradas(int $horas = 24): int
    {
        return self::expiradas($horas)->update(['activo' => false]);
    }

    /**
     * Obtener sesión por token
     */
    public static function porToken(string $token): ?self
    {
        return self::where('token_hash', hash('sha256', $token))
            ->where('activo', true)
            ->first();
    }

    /**
     * Obtener información del navegador parseada
     */
    public function getNavegadorAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconocido';
        }

        // Detección simple de navegador
        if (str_contains($this->user_agent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($this->user_agent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($this->user_agent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($this->user_agent, 'Edge')) {
            return 'Edge';
        } elseif (str_contains($this->user_agent, 'Opera')) {
            return 'Opera';
        }

        return 'Otro';
    }

    /**
     * Obtener sistema operativo
     */
    public function getSistemaOperativoAttribute(): string
    {
        if (!$this->user_agent) {
            return 'Desconocido';
        }

        if (str_contains($this->user_agent, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($this->user_agent, 'Mac')) {
            return 'macOS';
        } elseif (str_contains($this->user_agent, 'Linux')) {
            return 'Linux';
        } elseif (str_contains($this->user_agent, 'Android')) {
            return 'Android';
        } elseif (str_contains($this->user_agent, 'iOS')) {
            return 'iOS';
        }

        return 'Otro';
    }
}
