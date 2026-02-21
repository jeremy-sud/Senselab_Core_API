<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\PermisoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `permisos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Permiso extends Model
{
    /** @use HasFactory<\Database\Factories\PermisoFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'permisos';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'modulo',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'nombre' => 'required|string|unique:permisos,nombre',
        'slug' => 'required|string|unique:permisos,slug',
    ];

    /* --------------------- Relaciones --------------------- */
    
    /**
     * Relación muchos a muchos con Rol.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos', 'permiso_id', 'rol_id')
                    ->wherePivot('activo', true)
                    ->withTimestamps();
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorModulo(mixed $q, mixed $modulo): Builder{
        return $q->where('modulo', $modulo);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Permiso $permiso) {
            if (isset($permiso->nombre)) {
                $permiso->nombre = trim($permiso->nombre);
            }
            // NO normalizar el slug - debe mantener el formato exacto incluyendo guiones bajos
            // Los guiones bajos son importantes para distinguir módulos con múltiples palabras
            // Ejemplo: 'ver-tipo_comprobante_fe' != 'ver-tipo-comprobante-fe'
            if (isset($permiso->slug)) {
                $permiso->slug = trim($permiso->slug);
            }
            if (isset($permiso->modulo)) {
                $permiso->modulo = Str::ucfirst(Str::lower(trim($permiso->modulo)));
            }
        });
    }
}
