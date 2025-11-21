<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionUsuario extends Model
{
    use HasFactory;

    protected $table = 'sesiones_usuarios';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null; // Solo tiene timestamp de creación

    protected $fillable = [
        'usuario_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'ultimo_acceso',
        'activo',
    ];

    protected $casts = [
        'ultimo_acceso' => 'datetime',
        'activo' => 'boolean',
        'creado_en' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para sesiones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para sesiones inactivas
     */
    public function scopeInactivas($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Scope para sesiones de un usuario específico
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para sesiones expiradas (más de 24 horas sin actividad)
     */
    public function scopeExpiradas($query, $horas = 24)
    {
        return $query->where('ultimo_acceso', '<', now()->subHours($horas))
            ->where('activo', true);
    }

    /**
     * Scope para sesiones recientes (última hora)
     */
    public function scopeRecientes($query)
    {
        return $query->where('ultimo_acceso', '>=', now()->subHour());
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarAcceso()
    {
        $this->update(['ultimo_acceso' => now()]);
    }

    /**
     * Desactivar sesión
     */
    public function desactivar()
    {
        $this->update(['activo' => false]);
    }

    /**
     * Activar sesión
     */
    public function activar()
    {
        $this->update([
            'activo' => true,
            'ultimo_acceso' => now(),
        ]);
    }

    /**
     * Verificar si la sesión está expirada
     */
    public function estaExpirada($horas = 24)
    {
        return $this->ultimo_acceso < now()->subHours($horas);
    }

    /**
     * Crear nueva sesión
     */
    public static function crearSesion($usuarioId, $token)
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
    public static function cerrarSesionesUsuario($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->update(['activo' => false]);
    }

    /**
     * Limpiar sesiones expiradas
     */
    public static function limpiarExpiradas($horas = 24)
    {
        return self::expiradas($horas)->update(['activo' => false]);
    }

    /**
     * Obtener sesión por token
     */
    public static function porToken($token)
    {
        return self::where('token_hash', hash('sha256', $token))
            ->where('activo', true)
            ->first();
    }

    /**
     * Obtener información del navegador parseada
     */
    public function getNavegadorAttribute()
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
    public function getSistemaOperativoAttribute()
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
