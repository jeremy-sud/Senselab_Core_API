<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
/** @use HasFactory<\Database\Factories\LogAccesoSistemaFactory> */
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
    /** @use HasFactory<\Database\Factories\LogAccesoSistemaFactory> */
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

    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeLoginExitoso(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo_evento', 'login_exitoso');
    }

    public function scopeLoginFallido(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo_evento', 'login_fallido');
    }

    public function scopeLogout(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo_evento', 'logout');
    }

    public function scopePorUsuario(Builder $query, mixed $usuarioId): Builder{
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorIP(Builder $query, mixed $ip): Builder{
        return $query->where('ip_address', $ip);
    }

    public function scopeUltimos(Builder $query, mixed $dias = 30): Builder{
        return $query->where('creado_en', '>=', now()->subDays($dias));
    }

    /* --------------------- Métodos --------------------- */

    public function fueExitoso(): mixed
    {
        return $this->tipo_evento === 'login_exitoso';
    }

    public function fueFallido(): mixed
    {
        return $this->tipo_evento === 'login_fallido';
    }

    public function getDuracionFormateada(): mixed
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
    public static function registrarLoginExitoso(mixed $usuario, mixed $request): mixed
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
    public static function registrarLoginFallido(mixed $email, mixed $razon, mixed $request): mixed
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
