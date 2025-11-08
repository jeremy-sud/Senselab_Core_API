<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    use HasFactory, BelongsToTenant;

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
        'empresa_id',
        'nombre',
        'descripcion'
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
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con los productos.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    /**
     * Scope para obtener categorías activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por nombre.
     */
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Asegurar que el nombre sea único por empresa (case-insensitive)
        static::saving(function ($categoria) {
            $categoria->nombre = trim($categoria->nombre);
            
            // Verificar si existe otra categoría con el mismo nombre en la misma empresa
            $exists = static::where('id', '!=', $categoria->id)
                          ->where('empresa_id', $categoria->empresa_id)
                          ->where('nombre', 'LIKE', $categoria->nombre)
                          ->exists();
            
            if ($exists) {
                throw new \Exception('Ya existe una categoría con este nombre en la empresa.');
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
