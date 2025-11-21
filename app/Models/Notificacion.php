<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null; // Solo tiene timestamp de creación

    protected $fillable = [
        'usuario_id',
        'empresa_id',
        'tipo',
        'titulo',
        'mensaje',
        'datos',
        'leida',
        'leida_en',
        'url',
        'prioridad',
    ];

    protected $casts = [
        'datos' => 'array',
        'leida' => 'boolean',
        'leida_en' => 'datetime',
        'prioridad' => 'integer',
        'creado_en' => 'datetime',
    ];

    // Constantes para tipos de notificación
    public const TIPO_INFO = 'info';
    public const TIPO_WARNING = 'warning';
    public const TIPO_ERROR = 'error';
    public const TIPO_SUCCESS = 'success';

    // Constantes para prioridad
    public const PRIORIDAD_NORMAL = 0;
    public const PRIORIDAD_ALTA = 1;
    public const PRIORIDAD_URGENTE = 2;

    /**
     * Relación con el usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con la empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope para filtrar por prioridad
     */
    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para notificaciones recientes (últimas 24 horas)
     */
    public function scopeRecientes($query)
    {
        return $query->where('creado_en', '>=', now()->subDay());
    }

    /**
     * Scope para notificaciones de alta prioridad
     */
    public function scopeAltaPrioridad($query)
    {
        return $query->where('prioridad', '>=', self::PRIORIDAD_ALTA);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida()
    {
        $this->update([
            'leida' => true,
            'leida_en' => now(),
        ]);
    }

    /**
     * Marcar notificación como no leída
     */
    public function marcarComoNoLeida()
    {
        $this->update([
            'leida' => false,
            'leida_en' => null,
        ]);
    }

    /**
     * Crear notificación para un usuario
     */
    public static function crear($usuarioId, $tipo, $titulo, $mensaje, $datos = null, $url = null, $prioridad = self::PRIORIDAD_NORMAL)
    {
        $usuario = Usuario::find($usuarioId);

        return self::create([
            'usuario_id' => $usuarioId,
            'empresa_id' => $usuario->empresa_id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'datos' => $datos,
            'url' => $url,
            'prioridad' => $prioridad,
            'leida' => false,
        ]);
    }

    /**
     * Crear notificación de información
     */
    public static function info($usuarioId, $titulo, $mensaje, $datos = null, $url = null)
    {
        return self::crear($usuarioId, self::TIPO_INFO, $titulo, $mensaje, $datos, $url);
    }

    /**
     * Crear notificación de advertencia
     */
    public static function warning($usuarioId, $titulo, $mensaje, $datos = null, $url = null)
    {
        return self::crear($usuarioId, self::TIPO_WARNING, $titulo, $mensaje, $datos, $url, self::PRIORIDAD_ALTA);
    }

    /**
     * Crear notificación de error
     */
    public static function error($usuarioId, $titulo, $mensaje, $datos = null, $url = null)
    {
        return self::crear($usuarioId, self::TIPO_ERROR, $titulo, $mensaje, $datos, $url, self::PRIORIDAD_URGENTE);
    }

    /**
     * Crear notificación de éxito
     */
    public static function success($usuarioId, $titulo, $mensaje, $datos = null, $url = null)
    {
        return self::crear($usuarioId, self::TIPO_SUCCESS, $titulo, $mensaje, $datos, $url);
    }
}
