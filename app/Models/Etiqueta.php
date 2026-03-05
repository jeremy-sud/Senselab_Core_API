<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class Etiqueta extends Model
{
    use BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'etiquetas';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'color_hex',
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

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string|max:100',
        'color_hex' => ['nullable','regex:/^#[0-9A-Fa-f]{6}$/'],
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    // Relaciones
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function entidades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EntidadEtiqueta::class, 'etiqueta_id');
    }

    // Scopes
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorNombre(Builder $query, mixed $nombre): Builder{
        return $query->where('nombre', 'like', "%{$nombre}%");
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            $model->nombre = $model->nombre ? trim(preg_replace('/\s+/', ' ', $model->nombre)) : $model->nombre;

            if (!empty($model->color_hex) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $model->color_hex)) {
                throw new \Exception('El color debe estar en formato HEX (#RRGGBB).');
            }
        });
    }
}
