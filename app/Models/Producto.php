/**
 * Modelo para la tabla `productos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Producto extends Model
{
    use HasFactory, BelongsToTenant;

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
        'proveedor_id_predeterminado',
        'nombre',
        'descripcion',
        'codigo_barras',
        'sku',
        'precio_venta',
        'precio_compra',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'peso',
        'volumen',
        'impuesto_id',
        'cabys_id',
        'tipo_producto',
        'codigo_tipo_item_dgt',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'stock' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'peso' => 'decimal:2',
        'volumen' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
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

    public function proveedorPredeterminado()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id_predeterminado');
    }

    public function impuesto()
    {
        return $this->belongsTo(TipoImpuesto::class, 'impuesto_id');
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
            if (isset($p->sku)) {
                $p->sku = Str::upper(trim($p->sku));
            }
            if (isset($p->codigo_barras)) {
                $p->codigo_barras = trim($p->codigo_barras);
            }
            if (isset($p->tipo_producto)) {
                $p->tipo_producto = Str::ucfirst(Str::lower(trim($p->tipo_producto)));
            }
        });
    }
}
            }
