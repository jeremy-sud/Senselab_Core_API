<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
/** @use HasFactory<\Database\Factories\ComprobanteRecibidoElectronicoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteRecibidoElectronico extends Model
{
    /** @use HasFactory<\Database\Factories\ComprobanteRecibidoElectronicoFactory> */
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
        'consecutivo',
        'fecha_emision',
        'tipo_documento',
        'numero_cedula_emisor',
        'nombre_emisor',
        'monto_total',
        'monto_impuesto',
        'moneda',
        'xml_original',
        'estado_validacion',
        'mensaje_hacienda',
        'detalle_mensaje',
        'contabilizado',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha_emision' => 'date',
        'monto_total' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'contabilizado' => 'boolean',
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
     * Relación con la entrada de inventario asociada (opcional).
     */
    public function entradaInventario(): BelongsTo
    {
        return $this->belongsTo(EntradaInventario::class, 'entrada_inventario_id');
    }

    /**
     * Relación con el usuario que realizó la confirmación (opcional).
     */
    public function usuarioConfirmacion(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_confirmacion_id');
    }

    /**
     * Scope para obtener comprobantes activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por estado de validación.
     */
    public function scopePorEstadoValidacion(Builder $query, mixed $estado): Builder{
        return $query->where('estado_validacion', $estado);
    }

    /**
     * Scope para filtrar por contabilizado.
     */
    public function scopeContabilizados(Builder $query, mixed $contabilizado = true): Builder{
        return $query->where('contabilizado', $contabilizado);
    }

    /**
     * Scope para filtrar por mensaje de hacienda.
     */
    public function scopePorMensajeHacienda(Builder $query, mixed $mensaje): Builder{
        return $query->where('mensaje_hacienda', $mensaje);
    }

    /**
     * Scope para filtrar por tipo de documento.
     */
    public function scopePorTipoDocumento(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo_documento', $tipo);
    }

    /**
     * Scope para filtrar por rango de fechas de emisión.
     */
    public function scopePorFechaEmision(Builder $query, mixed $start, mixed $end): Builder{
        return $query->whereBetween('fecha_emision', [$start, $end]);
    }

    /**
     * Scope para buscar por clave numérica.
     */
    public function scopePorClaveNumerica(Builder $query, mixed $clave): Builder{
        return $query->where('clave_numerica', 'LIKE', "%{$clave}%");
    }

    /**
     * Determina si el comprobante está contabilizado.
     */
    public function estaContabilizado(): bool
    {
        return (bool) $this->contabilizado;
    }

    /**
     * Determina si fue aceptado por Hacienda.
     */
    public function estaAceptado(): bool
    {
        return $this->mensaje_hacienda === 'Aceptado';
    }

    /**
     * Determina si fue rechazado por Hacienda.
     */
    public function estaRechazado(): bool
    {
        return $this->mensaje_hacienda === 'Rechazado';
    }

    /**
     * Obtiene la descripción del tipo de documento.
     */
    public function getTipoDocumentoDescripcionAttribute(): string
    {
        return match($this->tipo_documento) {
            self::TIPO_FACTURA_ELECTRONICA => 'Factura Electrónica',
            self::TIPO_NOTA_DEBITO => 'Nota de Débito',
            self::TIPO_NOTA_CREDITO => 'Nota de Crédito',
            self::TIPO_TIQUETE_ELECTRONICO => 'Tiquete Electrónico',
            self::TIPO_FACTURA_COMPRA => 'Factura de Compra',
            self::TIPO_FACTURA_EXPORTACION => 'Factura de Exportación',
            default => 'Desconocido'
        };
    }
}
