<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class FormaPago extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    protected $table = 'formas_pago';

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'codigo_dgt',
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
        'codigo_dgt' => 'required|string|max:10|unique:formas_pago,codigo_dgt',
        'nombre' => 'required|string|max:255|unique:formas_pago,nombre',
        'descripcion' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo_dgt', $codigo);
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
