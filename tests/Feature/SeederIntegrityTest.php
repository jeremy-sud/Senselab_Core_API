<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Tests de integridad de seeders: verifica que MasterDataSeeder
 * y DemoDataSeeder corren sin errores y producen datos correctos.
 *
 * NO usa RefreshDatabase porque necesita control directo sobre
 * migrate/seed sin transacciones envolventes.
 */
class SeederIntegrityTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /**
     * MasterDataSeeder carga todos los catálogos del sistema.
     */
    public function test_master_data_seeder_runs_without_errors(): void
    {
        $exitCode = Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode, 'MasterDataSeeder falló: ' . Artisan::output());
    }

    /**
     * MasterDataSeeder produce las cantidades esperadas de registros.
     */
    public function test_master_data_seeder_creates_expected_records(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $expectations = [
            'regimenes_tributarios' => 2,
            'formas_pago' => 6,
            'tipos_cuentas' => 8,
            'unidades_medida' => 11,
            'cargos' => 13,
            'permisos' => 68,
            'roles' => 8,
            'tipos_comprobantes_fe' => 9,
            'tipos_impuesto' => 6,
            'tasas_impuesto' => 5,
            'deducciones_legales' => 6,
            'codigos_actividad_economica' => 35,
            'zonas_geograficas' => 7,
            'tipos_clientes' => 6,
        ];

        foreach ($expectations as $table => $expectedCount) {
            $actual = DB::table($table)->count();
            $this->assertEquals($expectedCount, $actual,
                "Tabla '{$table}': se esperaban {$expectedCount} registros, se encontraron {$actual}");
        }
    }

    /**
     * MasterDataSeeder es idempotente: ejecutar dos veces no duplica datos.
     */
    public function test_master_data_seeder_is_idempotent(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $countsBefore = [];
        $tables = [
            'regimenes_tributarios', 'formas_pago', 'tipos_cuentas',
            'unidades_medida', 'cargos', 'permisos', 'roles',
            'tipos_comprobantes_fe', 'tipos_impuesto', 'tasas_impuesto',
            'deducciones_legales', 'codigos_actividad_economica',
            'zonas_geograficas', 'tipos_clientes',
        ];

        foreach ($tables as $table) {
            $countsBefore[$table] = DB::table($table)->count();
        }

        // Ejecutar por segunda vez
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        foreach ($tables as $table) {
            $countAfter = DB::table($table)->count();
            $this->assertEquals($countsBefore[$table], $countAfter,
                "Tabla '{$table}' tiene datos duplicados tras re-ejecutar seeder ({$countsBefore[$table]} → {$countAfter})");
        }
    }

    /**
     * DemoDataSeeder corre sobre datos maestros y crea empresa + usuarios demo.
     */
    public function test_demo_data_seeder_runs_after_master_data(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $exitCode = Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\DemoDataSeeder',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode, 'DemoDataSeeder falló: ' . Artisan::output());

        // Verificar empresa demo
        $empresa = DB::table('empresas')->where('nombre', 'like', '%Ursol%')->first();
        $this->assertNotNull($empresa, 'Empresa demo Ursol no fue creada');

        // Verificar usuarios demo
        $usuarios = DB::table('usuarios')->whereIn('email', [
            'eduardo@ursol.com',
            'jeremy@ursol.com',
            'admin@ursol.com',
        ])->count();

        $this->assertGreaterThanOrEqual(2, $usuarios,
            "Se esperaban al menos 2 usuarios demo, se encontraron {$usuarios}");
    }

    /**
     * DatabaseSeeder (MasterData + DemoData) corre completo sin errores.
     */
    public function test_database_seeder_runs_completely(): void
    {
        $exitCode = Artisan::call('db:seed', ['--force' => true]);

        $this->assertEquals(0, $exitCode, 'DatabaseSeeder falló: ' . Artisan::output());

        // Verificar que tanto datos maestros como demo existen
        $this->assertGreaterThan(0, DB::table('roles')->count(), 'No se crearon roles');
        $this->assertGreaterThan(0, DB::table('permisos')->count(), 'No se crearon permisos');
        $this->assertGreaterThan(0, DB::table('empresas')->count(), 'No se creó empresa demo');
        $this->assertGreaterThan(0, DB::table('usuarios')->count(), 'No se crearon usuarios demo');
    }

    /**
     * Tasas de IVA de Costa Rica tienen los valores correctos según Ley 9635.
     */
    public function test_iva_rates_are_correct(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $tasas = DB::table('tasas_impuesto')
            ->pluck('tasa_porcentaje')
            ->map(fn ($v) => (float) $v)
            ->sort()
            ->values()
            ->toArray();

        $this->assertEquals([0.0, 1.0, 2.0, 4.0, 13.0], $tasas,
            'Las tasas de IVA no coinciden con Ley 9635: 0%, 1%, 2%, 4%, 13%');
    }

    /**
     * Los 68 permisos cubren los 17 módulos con CRUD.
     */
    public function test_permissions_cover_all_modules(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataSeeder',
            '--force' => true,
        ]);

        $expectedModules = [
            'empresas', 'sucursales', 'usuarios', 'roles', 'productos',
            'clientes', 'proveedores', 'inventario', 'ventas', 'compras',
            'contabilidad', 'nomina', 'cuentas_cobrar', 'cuentas_pagar',
            'facturacion', 'reportes', 'configuracion',
        ];

        $permisos = DB::table('permisos')->pluck('slug')->toArray();

        foreach ($expectedModules as $modulo) {
            // slugs usan formato: ver-{modulo}, crear-{modulo}, editar-{modulo}, eliminar-{modulo}
            $moduloPermisos = array_filter($permisos, fn ($p) => str_ends_with($p, "-{$modulo}"));
            $this->assertGreaterThanOrEqual(4, count($moduloPermisos),
                "Módulo '{$modulo}' debería tener al menos 4 permisos CRUD, tiene " . count($moduloPermisos));
        }
    }
}
