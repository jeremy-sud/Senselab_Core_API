<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\CategoriaProductoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    /** @use HasFactory<\Database\Factories\CategoriaProductoFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'categorias_productos';

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
        'categoria_padre_id',
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
     * Relación con la categoría padre.
     */
    public function categoriaPadre(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_padre_id');
    }

    /**
     * Relación con las categorías hijas.
     */
    public function categoriasHijas(): HasMany
    {
        return $this->hasMany(CategoriaProducto::class, 'categoria_padre_id');
    }

    /**
    /**
     * Relación con los productos.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(\App\Models\Producto::class, 'categoria_id');
    }
    /**
     * Scope para obtener categorías activas.
     */
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
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
        static::saving(function ($categoria) {
            $categoria->nombre = trim($categoria->nombre);
            
            // Verificar si existe otra categoría con el mismo nombre
            $exists = static::where('id', '!=', $categoria->id)
                          ->where('nombre', 'LIKE', $categoria->nombre)
                          ->exists();
            
            if ($exists) {
                throw new \Exception('Ya existe una categoría con este nombre.');
            }
        });
    }

    /**
     * Obtiene el nombre formateado de la categoría.
     */
    public function getNombreFormateadoAttribute(): string
    {
        return ucwords(strtolower($this->nombre));
    }

    /**
     * Obtiene el número de productos activos en esta categoría.
     */
    public function getNumeroProductosAttribute(): int
    {
        return $this->productos()
                   ->where('activo', true)
                   ->where('eliminado', false)
                   ->count();
    }
}
