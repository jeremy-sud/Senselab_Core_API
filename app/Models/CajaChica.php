<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaChica extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'caja_chica';

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
        'monto_inicial',
        'saldo_actual',
        'responsable_id',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'observaciones',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Estados permitidos para el fondo de caja chica.
     */
    const ESTADO_ABIERTA = 'Abierta';
    const ESTADO_CERRADA = 'Cerrada';
    const ESTADO_LIQUIDADA = 'Liquidada';

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con el empleado responsable.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'responsable_id');
    }

    /**
     * Relación con los movimientos de caja chica.
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoCajaChica::class, 'caja_chica_id');
    }

    /**
     * Scope para obtener fondos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar fondos abiertos.
     */
    public function scopeAbiertas($query)
    {
        return $query->where('estado', self::ESTADO_ABIERTA);
    }

    /**
     * Scope para filtrar por responsable.
     */
    public function scopePorResponsable($query, $responsableId)
    {
        return $query->where('responsable_id', $responsableId);
    }

    /**
     * Determina si el fondo está abierto.
     */
    public function estaAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTA;
    }

    /**
     * Determina si el fondo está cerrado.
     */
    public function estaCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }

    /**
     * Determina si el fondo está liquidado.
     */
    public function estaLiquidada(): bool
    {
        return $this->estado === self::ESTADO_LIQUIDADA;
    }
}