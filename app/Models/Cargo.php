<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
/** @use HasFactory<\Database\Factories\CargoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Cargo extends Model
{
    /** @use HasFactory<\Database\Factories\CargoFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'cargos';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Obtiene los empleados que tienen este cargo.
     */
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }

    /**
     * Scope para obtener cargos activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por nombre.
     */
    public function scopePorNombre(Builder $query, mixed $nombre): Builder{
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Asegurar que el nombre sea único (case-insensitive)
        static::saving(function ($cargo) {
            $cargo->nombre = trim($cargo->nombre);
            
            // Verificar si existe otro cargo con el mismo nombre
            $exists = static::where('id', '!=', $cargo->id)
                          ->where('nombre', 'LIKE', $cargo->nombre)
                          ->exists();
            
            if ($exists) {
                throw new \Exception('Ya existe un cargo con este nombre.');
            }
        });
    }

    /**
     * Obtiene el nombre formateado del cargo.
     */
    public function getNombreFormateadoAttribute(): string
    {
        return ucwords(strtolower($this->nombre));
    }
}