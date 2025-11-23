<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `logs_acceso_sistema`.
 * Registra auditoría de accesos al sistema (login, logout, intentos fallidos).
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class LogAccesoSistema extends Model
{
    use HasFactory;

    protected $table = 'logs_acceso_sistema';
    public $timestamps = false; // Solo usa creado_en

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'email',
        'tipo_evento',
        'ip_address',
        'user_agent',
        'metodo_autenticacion',
        'razon_fallo',
        'sesion_id',
        'duracion_sesion',
        'pais',
        'ciudad',
        'creado_en',
    ];

    protected $casts = [
        'duracion_sesion' => 'integer',
        'creado_en' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeLoginExitoso($query)
    {
        return $query->where('tipo_evento', 'login_exitoso');
    }

    public function scopeLoginFallido($query)
    {
        return $query->where('tipo_evento', 'login_fallido');
    }

    public function scopeLogout($query)
    {
        return $query->where('tipo_evento', 'logout');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorIP($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeUltimos($query, $dias = 30)
    {
        return $query->where('creado_en', '>=', now()->subDays($dias));
    }

    /* --------------------- Métodos --------------------- */

    public function fueExitoso()
    {
        return $this->tipo_evento === 'login_exitoso';
    }

    public function fueFallido()
    {
        return $this->tipo_evento === 'login_fallido';
    }

    public function getDuracionFormateada()
    {
        if (!$this->duracion_sesion) {
            return null;
        }

        $horas = floor($this->duracion_sesion / 3600);
        $minutos = floor(($this->duracion_sesion % 3600) / 60);
        $segundos = $this->duracion_sesion % 60;

        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }

    /**
     * Registrar un login exitoso
     */
    public static function registrarLoginExitoso($usuario, $request)
    {
        return self::create([
            'usuario_id' => $usuario->id,
            'email' => $usuario->email,
            'tipo_evento' => 'login_exitoso',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metodo_autenticacion' => 'password',
            'sesion_id' => session()->getId(),
        ]);
    }

    /**
     * Registrar un login fallido
     */
    public static function registrarLoginFallido($email, $razon, $request)
    {
        return self::create([
            'email' => $email,
            'tipo_evento' => 'login_fallido',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'razon_fallo' => $razon,
        ]);
    }
}
