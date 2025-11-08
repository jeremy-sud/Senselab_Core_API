<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteRecibidoElectronico extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'comprobantes_recibidos_electronicos';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Estados de confirmación del usuario.
     */
    const ESTADO_PENDIENTE = 0;
    const ESTADO_ACEPTADO = 1;
    const ESTADO_RECHAZADO = 2;

    /**
     * Tipos de documentos según DGT.
     */
    const TIPO_FACTURA_ELECTRONICA = '01';
    const TIPO_NOTA_DEBITO = '02';
    const TIPO_NOTA_CREDITO = '03';
    const TIPO_TIQUETE_ELECTRONICO = '04';
    const TIPO_FACTURA_COMPRA = '08';
    const TIPO_FACTURA_EXPORTACION = '09';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'clave_numerica',
        'consecutivo_receptor',
        'tipo_documento_dgt',
        'fecha_emision_comprobante',
        'fecha_recepcion_sistema',
        'moneda',
        'total_impuesto',
        'total_comprobante',
        'xml_contenido',
        'xml_respuesta_hacienda',
        'estado_hacienda',
        'mensaje_hacienda',
        'fecha_respuesta_hacienda',
        'confirmado_usuario',
        'fecha_confirmacion_usuario',
        'usuario_confirmacion_id',
        'entrada_inventario_id'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha_emision_comprobante' => 'datetime',
        'fecha_recepcion_sistema' => 'datetime',
        'total_impuesto' => 'decimal:5',
        'total_comprobante' => 'decimal:5',
        'fecha_respuesta_hacienda' => 'datetime',
        'confirmado_usuario' => 'integer',
        'fecha_confirmacion_usuario' => 'datetime',
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
     * Relación con el proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con el usuario que confirmó.
     */
    public function usuarioConfirmacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_confirmacion_id');
    }

    /**
     * Relación con la entrada de inventario.
     */
    public function entradaInventario(): BelongsTo
    {
        return $this->belongsTo(EntradaInventario::class, 'entrada_inventario_id');
    }

    /**
     * Scope para obtener comprobantes activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por estado de confirmación.
     */
    public function scopePorEstadoConfirmacion($query, $estado)
    {
        return $query->where('confirmado_usuario', $estado);
    }

    /**
     * Scope para filtrar por estado de hacienda.
     */
    public function scopePorEstadoHacienda($query, $estado)
    {
        return $query->where('estado_hacienda', $estado);
    }

    /**
     * Scope para filtrar por tipo de documento.
     */
    public function scopePorTipoDocumento($query, $tipo)
    {
        return $query->where('tipo_documento_dgt', $tipo);
    }

    /**
     * Scope para filtrar por rango de fechas de emisión.
     */
    public function scopePorFechaEmision($query, $start, $end)
    {
        return $query->whereBetween('fecha_emision_comprobante', [$start, $end]);
    }

    /**
     * Scope para buscar por clave numérica.
     */
    public function scopePorClaveNumerica($query, $clave)
    {
        return $query->where('clave_numerica', 'LIKE', "%{$clave}%");
    }

    /**
     * Determina si el comprobante está pendiente de confirmación.
     */
    public function estaPendiente(): bool
    {
        return $this->confirmado_usuario === self::ESTADO_PENDIENTE;
    }

    /**
     * Determina si el comprobante está aceptado.
     */
    public function estaAceptado(): bool
    {
        return $this->confirmado_usuario === self::ESTADO_ACEPTADO;
    }

    /**
     * Determina si el comprobante está rechazado.
     */
    public function estaRechazado(): bool
    {
        return $this->confirmado_usuario === self::ESTADO_RECHAZADO;
    }

    /**
     * Obtiene la descripción del tipo de documento.
     */
    public function getTipoDocumentoDescripcionAttribute(): string
    {
        return match($this->tipo_documento_dgt) {
            self::TIPO_FACTURA_ELECTRONICA => 'Factura Electrónica',
            self::TIPO_NOTA_DEBITO => 'Nota de Débito',
            self::TIPO_NOTA_CREDITO => 'Nota de Crédito',
            self::TIPO_TIQUETE_ELECTRONICO => 'Tiquete Electrónico',
            self::TIPO_FACTURA_COMPRA => 'Factura de Compra',
            self::TIPO_FACTURA_EXPORTACION => 'Factura de Exportación',
            default => 'Desconocido'
        };
    }

    /**
     * Obtiene la descripción del estado de confirmación.
     */
    public function getEstadoConfirmacionDescripcionAttribute(): string
    {
        return match($this->confirmado_usuario) {
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_ACEPTADO => 'Aceptado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            default => 'Desconocido'
        };
    }
}
