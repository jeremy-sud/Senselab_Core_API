<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $table = 'marcas';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    protected $hidden = [
        'eliminado',
    ];

    public static $rules = [
        'nombre' => 'required|string|max:100|unique:marcas,nombre',
        'descripcion' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    // Relaciones
    public function productos()
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'like', "%{$nombre}%");
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->nombre = $model->nombre ? trim(preg_replace('/\s+/', ' ', $model->nombre)) : $model->nombre;

            // Validar unicidad antes de que la BD arroje excepción
            $exists = self::where('nombre', $model->nombre)
                ->where('id', '<>', $model->id ?? 0)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe una marca con ese nombre.');
            }
        });
    }
}
