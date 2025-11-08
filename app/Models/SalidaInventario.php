<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Venta;

class SalidaInventario extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'salidas_inventario';

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
        'fecha_salida',
        'tipo_salida',
        'venta_id',
        'cliente_id',
        'proveedor_id',
        'documento_referencia',
        'estado',
        'monto_total',
        'observaciones'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_salida' => 'datetime',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con el almacén.
     */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /**
     * Relación con el cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con el proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con la venta.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Scope para filtrar por fecha.
     */
    public function scopeFechaBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha_salida', [$start, $end]);
    }

    /**
     * Scope para filtrar por tipo de salida.
     */
    public function scopePorTipoSalida($query, $tipo)
    {
        return $query->where('tipo_salida', $tipo);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para obtener salidas activas (no eliminadas).
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }
}
