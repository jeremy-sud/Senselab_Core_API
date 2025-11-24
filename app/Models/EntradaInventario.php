<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntradaInventario extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'entradas_inventario';

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
        'almacen_id',
        'fecha_entrada',
        'tipo_entrada',
        'orden_compra_id',
        'proveedor_id',
        'documento_referencia',
        'estado',
        'monto_total',
        'observaciones',
        'descripcion',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha_entrada' => 'datetime',
        'monto_total' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Relaciones
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleEntradaInventario::class, 'entrada_inventario_id');
    }

    /**
     * Scopes
     */
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePendientes(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeFechaBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha_entrada', [$start, $end]);
    }

    /**
     * Boot: calcular monto_total a partir de detalles antes de guardar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Establecer fecha por defecto si no viene
            if (empty($model->fecha_entrada)) {
                $model->fecha_entrada = now();
            }

            // Normalizar estado
            $model->estado = $model->estado ?? 'Pendiente';

            // Calcular monto total si la relación detalles está cargada
            if ($model->relationLoaded('detalles')) {
                $total = $model->detalles->sum(function ($d) {
                    return (float) ($d->total_linea ?? 0);
                });
                $model->monto_total = round($total, 2);
            }

            if (($model->monto_total ?? 0) < 0) {
                throw new \Exception('El monto total no puede ser negativo.');
            }
        });

        static::deleting(function ($model) {
            if (! $model->isForceDeleting()) {
                $model->detalles()->update(['eliminado' => 1, 'activo' => 0]);
            }
        });
    }
}
