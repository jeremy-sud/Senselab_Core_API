<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `productos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Producto extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'productos';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'unidad_medida_id',
        'marca_id',
        'proveedor_id',
        'tipo_impuesto_id',
        'cabys_id',
        'codigo',
        'codigo_barras',
        'nombre',
        'descripcion',
        'precio_venta',
        'precio_compra',
        'stock_minimo',
        'stock_maximo',
        'tipo_producto',
        'vende',
        'compra',
        'controla_inventario',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'vende' => 'boolean',
        'compra' => 'boolean',
        'controla_inventario' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string',
        'precio_venta' => 'required|numeric|min:0',
        'precio_compra' => 'required|numeric|min:0',
        'tipo_producto' => 'required|string',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function tipoImpuesto()
    {
        return $this->belongsTo(TipoImpuesto::class, 'tipo_impuesto_id');
    }

    public function cabys()
    {
        return $this->belongsTo(Cabys::class, 'cabys_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos($q)
    {
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa($q, $empresaId)
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopePorCategoria($q, $categoriaId)
    {
        return $q->where('categoria_id', $categoriaId);
    }

    public function scopePorTipo($q, $tipo)
    {
        return $q->where('tipo_producto', $tipo);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Producto $p) {
            if (isset($p->nombre)) {
                $p->nombre = trim($p->nombre);
            }
            if (isset($p->codigo)) {
                $p->codigo = Str::upper(trim($p->codigo));
            }
            if (isset($p->codigo_barras)) {
                $p->codigo_barras = trim($p->codigo_barras);
            }
        });
    }
}
