<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\{
    Cabys,
    ZonaGeografica,
    TipoImpuesto,
    TasaImpuesto,
    TipoComprobanteFe,
    RegimenTributario,
    CodigoActividadEconomica,
    UnidadMedida,
    TipoCliente,
    TipoCuenta,
    FormaPago
};

/**
 * Comando para precargar catálogos en cache (cache warming).
 *
 * Optimiza el rendimiento precargando datos que rara vez cambian,
 * reduciendo consultas a base de datos en las primeras solicitudes.
 *
 * @package App\Console\Commands
 * @author Sistemas Ursol S.A.
 */
class CacheWarmupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup
                            {--catalogs : Solo precarga catálogos}
                            {--stats : Muestra estadísticas de cache}
                            {--clear : Limpia cache antes de precargar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Precarga catálogos y datos de referencia en cache (cache warming)';

    /**
     * Configuración de catálogos a precargar.
     *
     * @var array<string, array{model: class-string, tag: string, ttl: int, description: string}>
     */
    protected array $catalogs = [];

    /**
     * TTL por defecto: 24 horas para catálogos estáticos.
     */
    protected const DEFAULT_TTL = 86400;

    /**
     * TTL largo: 7 días para datos que casi nunca cambian.
     */
    protected const LONG_TTL = 604800;

    /**
     * Constructor - inicializa la configuración de catálogos.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->catalogs = [
            'cabys' => [
                'model' => Cabys::class,
                'tag' => 'cabys',
                'ttl' => self::LONG_TTL,
                'description' => 'Catálogo de bienes y servicios (CABYS)',
            ],
            'zonas_geograficas' => [
                'model' => ZonaGeografica::class,
                'tag' => 'zonas',
                'ttl' => self::LONG_TTL,
                'description' => 'Zonas geográficas (provincias, cantones, distritos)',
            ],
            'tipos_impuesto' => [
                'model' => TipoImpuesto::class,
                'tag' => 'impuestos',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Tipos de impuesto',
            ],
            'tasas_impuesto' => [
                'model' => TasaImpuesto::class,
                'tag' => 'impuestos',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Tasas de impuesto',
            ],
            'tipos_comprobante_fe' => [
                'model' => TipoComprobanteFe::class,
                'tag' => 'factura_electronica',
                'ttl' => self::LONG_TTL,
                'description' => 'Tipos de comprobante electrónico',
            ],
            'regimenes_tributarios' => [
                'model' => RegimenTributario::class,
                'tag' => 'tributario',
                'ttl' => self::LONG_TTL,
                'description' => 'Regímenes tributarios',
            ],
            'codigos_actividad' => [
                'model' => CodigoActividadEconomica::class,
                'tag' => 'actividades',
                'ttl' => self::LONG_TTL,
                'description' => 'Códigos de actividad económica',
            ],
            'unidades_medida' => [
                'model' => UnidadMedida::class,
                'tag' => 'productos',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Unidades de medida',
            ],
            'tipos_cliente' => [
                'model' => TipoCliente::class,
                'tag' => 'clientes',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Tipos de cliente',
            ],
            'tipos_cuenta' => [
                'model' => TipoCuenta::class,
                'tag' => 'contabilidad',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Tipos de cuenta contable',
            ],
            'formas_pago' => [
                'model' => FormaPago::class,
                'tag' => 'pagos',
                'ttl' => self::DEFAULT_TTL,
                'description' => 'Formas de pago',
            ],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($this->option('clear')) {
            $this->clearCatalogCache();
        }

        return $this->warmupCatalogs();
    }

    /**
     * Precarga todos los catálogos en cache.
     *
     * @return int
     */
    protected function warmupCatalogs(): int
    {
        $this->info('🔥 Iniciando cache warming de catálogos...');
        $this->newLine();

        $totalRecords = 0;
        $startTime = microtime(true);

        foreach ($this->catalogs as $key => $config) {
            $count = $this->warmupCatalog($key, $config);
            $totalRecords += $count;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("✅ Cache warming completado:");
        $this->line("   • Total de registros: {$totalRecords}");
        $this->line("   • Tiempo: {$elapsed}s");
        $this->line("   • Catálogos: " . count($this->catalogs));

        return Command::SUCCESS;
    }

    /**
     * Precarga un catálogo específico en cache.
     *
     * @param string $key Clave del catálogo
     * @param array{model: class-string, tag: string, ttl: int, description: string} $config Configuración
     * @return int Número de registros cacheados
     */
    protected function warmupCatalog(string $key, array $config): int
    {
        $modelClass = $config['model'];
        $tag = $config['tag'];
        $ttl = $config['ttl'];
        $description = $config['description'];

        try {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = new $modelClass();
            
            // Obtener todos los registros activos
            $query = $model->newQuery();
            
            // Si el modelo tiene scope activo, usarlo
            if (method_exists($model, 'scopeActivo')) {
                $query->where('activo', true);
            }

            // Obtener registros
            $records = $query->get();
            $count = $records->count();

            // Cachear la colección completa
            $cacheKey = "catalog:{$key}:all";
            Cache::tags([$tag, 'catalogos'])->put($cacheKey, $records, $ttl);

            // Cachear registros individuales por ID para acceso rápido
            foreach ($records as $record) {
                $recordKey = "catalog:{$key}:{$record->getKey()}";
                Cache::tags([$tag, 'catalogos'])->put($recordKey, $record, $ttl);
            }

            // Si el modelo tiene campo 'codigo', indexar por código también
            if ($records->first() && isset($records->first()->codigo)) {
                foreach ($records as $record) {
                    $codeKey = "catalog:{$key}:codigo:{$record->codigo}";
                    Cache::tags([$tag, 'catalogos'])->put($codeKey, $record, $ttl);
                }
            }

            $this->line("   ✓ {$description}: {$count} registros");

            return $count;
        } catch (\Throwable $e) {
            $this->error("   ✗ {$description}: Error - {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Limpia el cache de catálogos.
     *
     * @return void
     */
    protected function clearCatalogCache(): void
    {
        $this->info('🧹 Limpiando cache de catálogos...');
        
        try {
            Cache::tags(['catalogos'])->flush();
            $this->line('   ✓ Cache de catálogos limpiado');
        } catch (\Throwable $e) {
            $this->error("   ✗ Error limpiando cache: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Muestra estadísticas del cache de catálogos.
     *
     * @return int
     */
    protected function showStats(): int
    {
        $this->info('📊 Estadísticas de cache de catálogos:');
        $this->newLine();

        $headers = ['Catálogo', 'TTL', 'En Cache', 'Registros'];
        $rows = [];

        foreach ($this->catalogs as $key => $config) {
            $cacheKey = "catalog:{$key}:all";
            $cached = Cache::tags([$config['tag'], 'catalogos'])->has($cacheKey);
            $count = 0;

            if ($cached) {
                $data = Cache::tags([$config['tag'], 'catalogos'])->get($cacheKey);
                $count = is_countable($data) ? count($data) : 0;
            }

            $ttlHours = round($config['ttl'] / 3600);
            
            $rows[] = [
                $config['description'],
                "{$ttlHours}h",
                $cached ? '✓' : '✗',
                $count,
            ];
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
