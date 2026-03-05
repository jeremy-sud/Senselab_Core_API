<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use App\Traits\BelongsToTenant;

class MovimientoPresupuesto extends Model
{
    use UsesTenantConnection, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'movimiento_presupuestos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'detalle_presupuesto_id',
        'monto',
        'fecha',
        'descripcion',
        'tipo',
        'referencia_tipo',
        'referencia_id',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Get the detalle de presupuesto associated with the movimiento.
     */
    public function detallePresupuesto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DetallePresupuesto::class);
    }

    /**
     * Get the parent referencia model (factura, compra, etc).
     */
    public function referencia(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
