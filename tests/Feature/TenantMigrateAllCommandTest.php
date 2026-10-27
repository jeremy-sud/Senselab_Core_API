<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Console\Commands\TenantMigrateAllCommand;

#[CoversClass(TenantMigrateAllCommand::class)]
class TenantMigrateAllCommandTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = $this->createEmpresa([
            'nombre' => 'Tenant Para Migracion',
            'activo' => true,
        ]);
    }

    #[Test]
    public function test_can_run_migrations_sequentially_across_tenants()
    {
        $this->artisan('tenant:migrate-all', ['--force' => true])
            ->expectsOutputToContain('Starting migrations')
            ->expectsOutputToContain('Completed migrations')
            ->assertExitCode(0);
    }

    #[Test]
    public function test_can_run_migrations_in_parallel_mode()
    {
        $this->artisan('tenant:migrate-all', [
            '--parallel' => true,
            '--threads' => 2,
            '--force' => true,
        ])
            ->expectsOutputToContain('Executing concurrent migrations with 2 worker threads')
            ->expectsOutputToContain('Completed parallel migrations')
            ->assertExitCode(0);
    }

    #[Test]
    public function test_can_migrate_specific_tenant()
    {
        $this->artisan('tenant:migrate-all', [
            '--tenant' => $this->empresa->id,
            '--force' => true,
        ])
            ->expectsOutputToContain('migrated successfully')
            ->assertExitCode(0);
    }

    #[Test]
    public function test_fails_for_non_existent_tenant()
    {
        $this->artisan('tenant:migrate-all', [
            '--tenant' => 999999,
            '--force' => true,
        ])
            ->expectsOutputToContain('Tenant with ID 999999 not found')
            ->assertExitCode(1);
    }

    #[Test]
    public function test_requires_force_flag_in_production()
    {
        $originalEnv = app()->environment();
        $this->app['env'] = 'production';

        try {
            $this->artisan('tenant:migrate-all')
                ->expectsOutputToContain('Running migrations in production requires the --force flag')
                ->assertExitCode(1);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }
}
