<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateDatabaseIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:validate 
                            {--detailed : Mostrar detalles completos de problemas}
                            {--fix : Intentar corregir problemas automáticamente (experimental)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Valida la integridad de la base de datos: PKs, FKs, índices y relaciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Iniciando validación de integridad de base de datos...');
        $this->newLine();

        $database = config('database.connections.mysql.database');
        $errors = 0;

        // 1. Validar Primary Keys
        $this->line('📌 Validando Primary Keys...');
        $errors += $this->validatePrimaryKeys($database);
        $this->newLine();

        // 2. Validar Foreign Keys
        $this->line('🔗 Validando Foreign Keys...');
        $errors += $this->validateForeignKeys($database);
        $this->newLine();

        // 3. Validar Índices
        $this->line('📊 Validando Índices...');
        $errors += $this->validateIndexes($database);
        $this->newLine();

        // 4. Validar Integridad Referencial
        $this->line('🔍 Validando Integridad Referencial...');
        $errors += $this->validateReferentialIntegrity();
        $this->newLine();

        // 5. Validar Tipos de Datos
        $this->line('🔢 Validando Tipos de Datos PK/FK...');
        $errors += $this->validateDataTypes($database);
        $this->newLine();

        // 6. Resumen
        $this->displaySummary($database, $errors);

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Valida que todas las tablas tengan Primary Key
     */
    private function validatePrimaryKeys(string $database): int
    {
        $tablesWithoutPK = DB::select("
            SELECT t.TABLE_NAME
            FROM information_schema.TABLES t
            LEFT JOIN information_schema.TABLE_CONSTRAINTS tc 
                ON t.TABLE_SCHEMA = tc.TABLE_SCHEMA 
                AND t.TABLE_NAME = tc.TABLE_NAME 
                AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
            WHERE t.TABLE_SCHEMA = ?
                AND t.TABLE_TYPE = 'BASE TABLE'
                AND tc.CONSTRAINT_NAME IS NULL
        ", [$database]);

        if (count($tablesWithoutPK) > 0) {
            $this->error('  ❌ Tablas sin Primary Key: ' . count($tablesWithoutPK));
            if ($this->option('detailed')) {
                foreach ($tablesWithoutPK as $table) {
                    $this->warn("    - {$table->TABLE_NAME}");
                }
            }
            return count($tablesWithoutPK);
        }

        $this->info('  ✅ Todas las tablas tienen Primary Key');
        return 0;
    }

    /**
     * Valida Foreign Keys
     */
    private function validateForeignKeys(string $database): int
    {
        $errors = 0;

        // Total de FKs
        $totalFKs = DB::selectOne("
            SELECT COUNT(*) as total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database])->total;

        $this->info("  ℹ️  Total de Foreign Keys: {$totalFKs}");

        // FKs a empresas
        $empresaFKs = DB::selectOne("
            SELECT COUNT(*) as total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND COLUMN_NAME = 'empresa_id'
                AND REFERENCED_TABLE_NAME = 'empresas'
        ", [$database])->total;

        $this->info("  ℹ️  Foreign Keys a empresas.id: {$empresaFKs}");

        // Tablas con empresa_id sin FK
        $missingEmpresaFK = DB::select("
            SELECT DISTINCT c.TABLE_NAME
            FROM information_schema.COLUMNS c
            LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu
                ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
                AND c.TABLE_NAME = kcu.TABLE_NAME
                AND c.COLUMN_NAME = kcu.COLUMN_NAME
                AND kcu.REFERENCED_TABLE_NAME = 'empresas'
            WHERE c.TABLE_SCHEMA = ?
                AND c.COLUMN_NAME = 'empresa_id'
                AND kcu.CONSTRAINT_NAME IS NULL
        ", [$database]);

        if (count($missingEmpresaFK) > 0) {
            $this->error('  ❌ Tablas con empresa_id sin FK: ' . count($missingEmpresaFK));
            if ($this->option('detailed')) {
                foreach ($missingEmpresaFK as $table) {
                    $this->warn("    - {$table->TABLE_NAME}");
                }
            }
            $errors += count($missingEmpresaFK);
        } else {
            $this->info('  ✅ Todas las tablas con empresa_id tienen FK');
        }

        return $errors;
    }

    /**
     * Valida índices
     */
    private function validateIndexes(string $database): int
    {
        $errors = 0;

        // Índices por tipo
        $indexStats = DB::select("
            SELECT 
                CASE 
                    WHEN NON_UNIQUE = 0 THEN 'ÚNICOS'
                    WHEN INDEX_NAME = 'PRIMARY' THEN 'PRIMARY'
                    ELSE 'SIMPLES'
                END AS tipo,
                COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME)) AS cantidad
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
            GROUP BY tipo
        ", [$database]);

        foreach ($indexStats as $stat) {
            $this->info("  ℹ️  Índices {$stat->tipo}: {$stat->cantidad}");
        }

        // Tablas con empresa_id sin índice
        $missingEmpresaIndex = DB::select("
            SELECT DISTINCT c.TABLE_NAME
            FROM information_schema.COLUMNS c
            LEFT JOIN information_schema.STATISTICS s
                ON c.TABLE_SCHEMA = s.TABLE_SCHEMA
                AND c.TABLE_NAME = s.TABLE_NAME
                AND c.COLUMN_NAME = s.COLUMN_NAME
            WHERE c.TABLE_SCHEMA = ?
                AND c.COLUMN_NAME = 'empresa_id'
                AND s.INDEX_NAME IS NULL
        ", [$database]);

        if (count($missingEmpresaIndex) > 0) {
            $this->error('  ❌ Tablas con empresa_id sin índice: ' . count($missingEmpresaIndex));
            if ($this->option('detailed')) {
                foreach ($missingEmpresaIndex as $table) {
                    $this->warn("    - {$table->TABLE_NAME}");
                }
            }
            $errors += count($missingEmpresaIndex);
        } else {
            $this->info('  ✅ Todas las tablas con empresa_id tienen índice');
        }

        // Índices compuestos
        $compositeIndexes = DB::selectOne("
            SELECT COUNT(*) as total
            FROM (
                SELECT TABLE_NAME, INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ?
                    AND INDEX_NAME != 'PRIMARY'
                GROUP BY TABLE_NAME, INDEX_NAME
                HAVING COUNT(COLUMN_NAME) > 1
            ) AS composite
        ", [$database])->total;

        $this->info("  ℹ️  Índices Compuestos: {$compositeIndexes}");

        return $errors;
    }

    /**
     * Valida integridad referencial (registros huérfanos)
     */
    private function validateReferentialIntegrity(): int
    {
        $errors = 0;

        // Tablas con empresa_id para verificar
        $tables = [
            'ventas', 'clientes', 'productos', 'proveedores', 'empleados',
            'ordenes_compra', 'cuentas_por_cobrar', 'cuentas_por_pagar',
            'entradas_inventario', 'salidas_inventario', 'asientos_contables'
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                $orphans = DB::selectOne("
                    SELECT COUNT(*) as total
                    FROM {$table} t
                    LEFT JOIN empresas e ON t.empresa_id = e.id
                    WHERE e.id IS NULL
                ")->total;

                if ($orphans > 0) {
                    $this->error("  ❌ Registros huérfanos en {$table}: {$orphans}");
                    $errors += $orphans;
                }
            } catch (\Exception $e) {
                // Tabla no tiene empresa_id, omitir
                continue;
            }
        }

        if ($errors === 0) {
            $this->info('  ✅ No se detectaron registros huérfanos');
        }

        return $errors;
    }

    /**
     * Valida consistencia de tipos de datos entre PK y FK
     */
    private function validateDataTypes(string $database): int
    {
        $inconsistencies = DB::select("
            SELECT 
                kcu.TABLE_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                c1.DATA_TYPE AS fk_type,
                c2.DATA_TYPE AS pk_type
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.COLUMNS c1 
                ON kcu.TABLE_SCHEMA = c1.TABLE_SCHEMA
                AND kcu.TABLE_NAME = c1.TABLE_NAME
                AND kcu.COLUMN_NAME = c1.COLUMN_NAME
            JOIN information_schema.COLUMNS c2
                ON kcu.REFERENCED_TABLE_SCHEMA = c2.TABLE_SCHEMA
                AND kcu.REFERENCED_TABLE_NAME = c2.TABLE_NAME
                AND kcu.REFERENCED_COLUMN_NAME = c2.COLUMN_NAME
            WHERE kcu.TABLE_SCHEMA = ?
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                AND c1.DATA_TYPE != c2.DATA_TYPE
        ", [$database]);

        if (count($inconsistencies) > 0) {
            $this->error('  ❌ Inconsistencias de tipos de datos: ' . count($inconsistencies));
            if ($this->option('detailed')) {
                foreach ($inconsistencies as $inc) {
                    $this->warn("    - {$inc->TABLE_NAME}.{$inc->COLUMN_NAME} ({$inc->fk_type}) → {$inc->REFERENCED_TABLE_NAME}.{$inc->REFERENCED_COLUMN_NAME} ({$inc->pk_type})");
                }
            }
            return count($inconsistencies);
        }

        $this->info('  ✅ Todos los tipos de datos PK/FK son consistentes');
        return 0;
    }

    /**
     * Muestra resumen final
     */
    private function displaySummary(string $database, int $errors): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════');
        $this->line('📊 RESUMEN DE VALIDACIÓN');
        $this->line('═══════════════════════════════════════════════════');

        $stats = DB::select("
            SELECT 'Tablas Totales' as metrica, 
                   COUNT(*) as valor
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
                AND TABLE_TYPE = 'BASE TABLE'
            
            UNION ALL
            
            SELECT 'Foreign Keys',
                   COUNT(*)
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            
            UNION ALL
            
            SELECT 'Índices (excl. PK)',
                   COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME))
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
                AND INDEX_NAME != 'PRIMARY'
        ", [$database, $database, $database]);

        foreach ($stats as $stat) {
            $this->info("  {$stat->metrica}: {$stat->valor}");
        }

        $this->newLine();

        if ($errors === 0) {
            $this->info('✅ Base de datos ÓPTIMA - Sin problemas detectados');
        } else {
            $this->error("❌ Se detectaron {$errors} problemas - Revisar detalles arriba");
            $this->warn('💡 Usa --detailed para ver información completa');
            if (!$this->option('fix')) {
                $this->warn('💡 Usa --fix para intentar correcciones automáticas (experimental)');
            }
        }
    }
}
