<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioRuta extends Model
{
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $table = 'horarios_ruta';

    protected $fillable = [
        'ruta_id',
        'bus_id',
        'fecha_salida',
        'hora_salida',
        'fecha_llegada_estimada',
        'hora_llegada_estimada',
        'asientos_disponibles',
        'estado',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_llegada_estimada' => 'date',
        'hora_salida' => 'string',
        'hora_llegada_estimada' => 'string',
        'asientos_disponibles' => 'integer',
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
        'ruta_id' => 'required|exists:rutas,id',
        'bus_id' => 'nullable|exists:buses,id',
        'fecha_salida' => 'required|date',
        'hora_salida' => 'required',
        'estado' => 'required|string|max:50',
        'asientos_disponibles' => 'nullable|integer|min:0',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Relación con la ruta.
     */
    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    /**
     * Relación con el bus asignado.
     */
    public function bus(): BelongsTo
    {
        return $this->belongsTo(BusUnidad::class, 'bus_id');
    }

    /**
     * Ventas/tiquetes asociados a este horario (si existe tabla ventas/tiquetes).
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'horario_ruta_id');
    }

    /**
     * Relación con los detalles de tiquetes vendidos para este horario.
     * Alias para ventas o relación directa con TiqueteDetalle si existe.
     */
    public function tiquetesDetalle(): HasMany
    {
        // Asumiendo que TiqueteDetalle es el modelo correcto para los tiquetes individuales
        // Si no existe, usar Venta si representa lo mismo.
        // Basado en el controlador, parece ser una relación HasMany.
        return $this->hasMany(TiqueteDetalle::class, 'horario_ruta_id');
    }

    // Scopes
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopeProgramados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'Programado')->orWhere('estado', 'Programado');
    }

    public function scopeEnViaje(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'En Viaje');
    }

    public function scopeCancelados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', 'Cancelado');
    }

    /**
     * Calcula asientos disponibles basados en capacidad del bus y tiquetes vendidos.
     * Si la relación `bus` o `ventas` está cargada, realiza el cálculo en memoria.
     * Devuelve el valor almacenado si no se puede calcular.
     *
     * @return int|null
     */
    public function calcularAsientosDisponibles(): mixed
    {
        // Si ya existe valor almacenado, lo usamos como fallback
        $almacenado = $this->asientos_disponibles;

        // Si no hay bus o ventas cargadas, devolver el almacenado
        if (! $this->relationLoaded('bus') || ! $this->relationLoaded('ventas')) {
            return $almacenado;
        }

        $capacidad = (int) ($this->bus->capacidad ?? 0);
        $vendidos = (int) $this->ventas->sum(function ($v) {
            return (int) ($v->cantidad ?? 1);
        });

        $disponibles = max(0, $capacidad - $vendidos);
        return $disponibles;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Normalizar estado por defecto
            $model->estado = $model->estado ?? 'Programado';

            // Fecha y hora deben existir
            if (empty($model->fecha_salida) || empty($model->hora_salida)) {
                throw new \Exception('Fecha y hora de salida son requeridas.');
            }

            // No permitir asientos negativos
            if (! is_null($model->asientos_disponibles) && $model->asientos_disponibles < 0) {
                throw new \Exception('Asientos disponibles no puede ser negativo.');
            }
        });
    }
}
