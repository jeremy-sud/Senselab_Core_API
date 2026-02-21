<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Support\Str;

class EntidadEtiqueta extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    protected $table = 'entidad_etiquetas';

    protected $fillable = [
        'etiqueta_id',
        'entidad_tipo',
        'entidad_id',
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
        'etiqueta_id' => 'required|exists:etiquetas,id',
        'entidad_tipo' => 'required|string|max:50',
        'entidad_id' => 'required|integer|min:1',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Mapear nombres en la columna `entidad_tipo` a clases de modelo.
     * Modifica este arreglo si usas otros nombres de tabla.
     * Clave: valor almacenado en `entidad_tipo` => Clase del modelo
     *
     * @var array<string, string>
     */
    protected static $entidadMap = [
        'clientes' => Cliente::class,
        'productos' => Producto::class,
        'ventas' => Venta::class,
        'empleados' => Empleado::class,
        'proveedores' => Proveedor::class,
        // Añade más mapeos según necesites
    ];

    /**
     * Relación con la etiqueta.
     */
    public function etiqueta(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Etiqueta::class, 'etiqueta_id');
    }

    /**
     * Relación polimórfica (resuelta por mapa o convención).
     * Si no podemos resolver la clase, devuelve null.
     */
    public function entidad(): mixed
    {
        $tipo = $this->entidad_tipo;

        if (isset(self::$entidadMap[$tipo])) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $class */
            $class = self::$entidadMap[$tipo];
            return $this->belongsTo($class, 'entidad_id');
        }

        // Intentar convertir el nombre de tabla a clase en App\Models (singular, Studly)
        $posible = '\\App\\Models\\' . Str::studly(Str::singular($tipo));
        if (class_exists($posible) && is_subclass_of($posible, \Illuminate\Database\Eloquent\Model::class)) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $posible */
            return $this->belongsTo($posible, 'entidad_id');
        }

        return null;
    }

    // Scopes útiles
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEtiqueta(Builder $query, mixed $etiquetaId): Builder{
        return $query->where('etiqueta_id', $etiquetaId);
    }

    public function scopePorEntidad(Builder $query, mixed $tipo, mixed $id): Builder{
        return $query->where('entidad_tipo', $tipo)->where('entidad_id', $id);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Asegurar campos básicos
            if (empty($model->etiqueta_id) || empty($model->entidad_tipo) || empty($model->entidad_id)) {
                throw new \Exception('Etiqueta y entidad (tipo/id) son requeridos.');
            }

            // Normalizar entidad_tipo
            $model->entidad_tipo = trim(strtolower($model->entidad_tipo));

            // Evitar duplicados (la BD tiene una clave única, esto da un mensaje limpio)
            $exists = self::where('etiqueta_id', $model->etiqueta_id)
                ->where('entidad_tipo', $model->entidad_tipo)
                ->where('entidad_id', $model->entidad_id)
                ->where('id', '<>', $model->id ?? 0)
                ->exists();

            if ($exists) {
                throw new \Exception('La etiqueta ya está asignada a esta entidad.');
            }
        });
    }
}
