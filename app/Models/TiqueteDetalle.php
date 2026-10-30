<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiqueteDetalle extends Model
{
    use BelongsToTenant;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'tiquetes_detalle';

    /**
     * Clave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atributos que se pueden asignar de manera masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'detalle_venta_id',
        'horario_ruta_id',
        'parada_origen_id',
        'parada_destino_id',
        'asiento_numero',
        'codigo_boleto',
        'pasarela_referencia',
        'nombre_pasajero',
        'identificacion_pasajero',
        'precio_final_tiquete',
        'tipo_tarifa',
        'estado',
        'estado_pago',
        'bloqueo_temporal_id',
        'cambiado_a_tiquete_id',
        'motivo_cambio',
        'diferencia_tarifa_cambio',
        'activo',
        'eliminado',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'precio_final_tiquete' => 'decimal:2',
        'diferencia_tarifa_cambio' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Indica si el modelo tiene marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Relación con el modelo Empresa (Multi-Tenancy).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con el modelo DetalleVenta.
     */
    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'detalle_venta_id');
    }

    /**
     * Relación con el modelo HorarioRuta.
     */
    public function horarioRuta(): BelongsTo
    {
        return $this->belongsTo(HorarioRuta::class, 'horario_ruta_id');
    }

    /**
     * Relación con la parada de origen (embarque).
     */
    public function paradaOrigen(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_origen_id');
    }

    /**
     * Relación con la parada de destino (desembarque).
     */
    public function paradaDestino(): BelongsTo
    {
        return $this->belongsTo(RutaParada::class, 'parada_destino_id');
    }

    /**
     * Relación con el bloqueo temporal que reservó este boleto.
     */
    public function bloqueoTemporal(): BelongsTo
    {
        return $this->belongsTo(AsientoBloqueoTemporal::class, 'bloqueo_temporal_id');
    }

    /**
     * Relación con el nuevo tiquete emitido en caso de cambio de viaje.
     */
    public function tiqueteReemplazo(): BelongsTo
    {
        return $this->belongsTo(TiqueteDetalle::class, 'cambiado_a_tiquete_id');
    }

    /**
     * Scope para filtrar solo los registros activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo los registros no eliminados.
     */
    public function scopeNoEliminados(Builder $query): Builder
    {
        return $query->where('eliminado', false);
    }
}
