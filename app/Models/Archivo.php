<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'archivos';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'entidad_tipo',
        'entidad_id',
        'nombre_original',
        'nombre_almacenado',
        'ruta',
        'tipo_mime',
        'extension',
        'tamano_bytes',
        'categoria',
        'hash_sha256',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Relación polimórfica con la entidad (producto, cliente, venta, etc.)
     */
    public function entidad(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entidad_tipo', 'entidad_id');
    }

    /**
     * Relación con la empresa propietaria
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con el usuario que subió el archivo
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Scope para archivos activos
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para archivos no eliminados
     */
    public function scopeNoEliminados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('eliminado', false);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para filtrar por tipo de entidad
     */
    public function scopeEntidadTipo($query, $tipo)
    {
        return $query->where('entidad_tipo', $tipo);
    }

    /**
     * Obtener la URL completa del archivo
     */
    public function getUrlAttribute()
    {
        return storage_path('app/' . $this->ruta);
    }

    /**
     * Obtener el tamaño formateado
     */
    public function getTamanoFormateadoAttribute()
    {
        $bytes = $this->tamano_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
