<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Tests de rollback de migraciones.
 * 
 * NO usa RefreshDatabase porque necesita control directo sobre
 * migrate/rollback sin transacciones envolventes.
 */
class MigrationRollbackTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar migraciones limpiamente antes de cada test
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /**
     * Verifica que todas las migraciones ejecutan up() correctamente.
     */
    public function test_all_migrations_run_successfully(): void
    {
        $migrations = \DB::table('migrations')->count();

        $this->assertGreaterThanOrEqual(98, $migrations, 
            "Se esperaban al menos 98 migraciones ejecutadas, se encontraron {$migrations}");
    }

    /**
     * Verifica que todas las migraciones pueden hacer rollback completo
     * y luego re-migrar sin errores.
     */
    public function test_full_rollback_and_remigrate(): void
    {
        // Paso 1: Rollback completo
        $exitCode = Artisan::call('migrate:rollback', [
            '--step' => 200,
        ]);

        $this->assertEquals(0, $exitCode, 
            'migrate:rollback falló. Output: ' . Artisan::output());

        // Paso 2: Verificar que las tablas principales fueron eliminadas
        $this->assertFalse(Schema::hasTable('empresas'), 
            'La tabla empresas debería haberse eliminado tras rollback completo');
        $this->assertFalse(Schema::hasTable('usuarios'), 
            'La tabla usuarios debería haberse eliminado tras rollback completo');

        // Paso 3: Re-migrar todo
        $exitCode = Artisan::call('migrate');

        $this->assertEquals(0, $exitCode, 
            'migrate falló tras rollback. Output: ' . Artisan::output());

        // Paso 4: Verificar tablas críticas
        $criticalTables = [
            'empresas',
            'sucursales', 
            'usuarios',
            'roles',
            'permisos',
            'productos',
            'clientes',
            'ventas',
            'comprobantes_electronicos_fe',
            'cuentas_contables',
            'asientos_contables',
            'empleados',
            'ordenes_compra',
            'almacenes',
            'cajas',
        ];

        foreach ($criticalTables as $table) {
            $this->assertTrue(Schema::hasTable($table), 
                "La tabla '{$table}' no existe tras re-migrar");
        }
    }

    /**
     * Verifica que cada migración individual puede hacer rollback y re-up.
     * Ejecuta rollback de una en una, empezando por la última.
     */
    public function test_individual_migration_rollback_and_up(): void
    {
        $totalMigrations = \DB::table('migrations')->count();
        $failures = [];

        for ($i = 0; $i < $totalMigrations; $i++) {
            $lastMigration = \DB::table('migrations')
                ->orderBy('batch', 'desc')
                ->orderBy('id', 'desc')
                ->value('migration');

            // Rollback 1 paso
            $rollbackCode = Artisan::call('migrate:rollback', ['--step' => 1]);
            if ($rollbackCode !== 0) {
                $failures[] = 'Rollback falló en migración #' . ($totalMigrations - $i) . ": {$lastMigration}. " . Artisan::output();
                break;
            }

            // Re-migrar
            $migrateCode = Artisan::call('migrate');
            if ($migrateCode !== 0) {
                $failures[] = "Re-migrate falló tras rollback de: {$lastMigration}. " . Artisan::output();
                break;
            }

            // Hacer rollback de nuevo para continuar con la siguiente
            Artisan::call('migrate:rollback', ['--step' => 1]);
        }

        if (!empty($failures)) {
            Artisan::call('migrate');
        }

        $this->assertEmpty($failures, 
            "Migraciones con problemas de rollback:\n" . implode("\n", $failures));
    }

    /**
     * Verifica que migrate:fresh funciona correctamente.
     */
    public function test_migrate_fresh_works(): void
    {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        $this->assertEquals(0, $exitCode, 
            'migrate:fresh falló. Output: ' . Artisan::output());

        $this->assertTrue(Schema::hasTable('empresas'));
        $this->assertTrue(Schema::hasTable('usuarios'));
        $this->assertTrue(Schema::hasTable('productos'));
    }
}
