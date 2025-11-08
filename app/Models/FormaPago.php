<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    protected $table = 'formas_pago';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'requiere_referencia',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'requiere_referencia' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    protected $hidden = [
        'eliminado',
    ];

    public static $rules = [
        'nombre' => 'required|string|max:255|unique:formas_pago,nombre',
        'descripcion' => 'nullable|string',
        'tipo' => 'nullable|string|max:50',
        'requiere_referencia' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Relaciones de ejemplo: pagos que usaron esta forma (si existe tabla pagos)
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'forma_pago_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->nombre = $model->nombre ? trim(preg_replace('/\s+/', ' ', $model->nombre)) : $model->nombre;

            // Asegurar que el nombre sea único (mensajes claros antes de la excepción de BD)
            $exists = self::where('nombre', $model->nombre)
                ->where('id', '<>', $model->id ?? 0)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe una forma de pago con ese nombre.');
            }
        });
    }
}
