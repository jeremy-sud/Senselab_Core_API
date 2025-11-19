<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `permisos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'descripcion',
        'modulo',
        'codigo_unico',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public static $rules = [
        'nombre' => 'required|string|unique:permisos,nombre',
        'codigo_unico' => 'required|string|unique:permisos,codigo_unico',
    ];

    /* --------------------- Scopes --------------------- */
    public function scopeActivos($q)
    {
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorModulo($q, $modulo)
    {
        return $q->where('modulo', $modulo);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Permiso $permiso) {
            if (isset($permiso->nombre)) {
                $permiso->nombre = trim($permiso->nombre);
            }
            if (isset($permiso->codigo_unico)) {
                $permiso->codigo_unico = Str::upper(trim($permiso->codigo_unico));
            }
            if (isset($permiso->modulo)) {
                $permiso->modulo = Str::ucfirst(Str::lower(trim($permiso->modulo)));
            }
        });
    }
}
