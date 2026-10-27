<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Process\Pool;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Throwable;

class TenantMigrateAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-all
                            {--parallel : Execute migrations concurrently across tenants}
                            {--threads=4 : Number of concurrent threads for parallel execution}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed database after migration}
                            {--tenant= : Migrate a specific tenant ID only}
                            {--force : Force execution without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations across all tenants sequentially or concurrently in parallel.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->isProduction() && !$this->option('force')) {
            $this->error('Running migrations in production requires the --force flag.');
            return self::FAILURE;
        }

        $specificTenantId = $this->option('tenant');

        // Modo ejecución para un tenant individual (usado internamente por el pool o directamente por el operador)
        if ($specificTenantId) {
            return $this->migrateSingleTenant((int)$specificTenantId);
        }

        $tenants = Empresa::where('activo', true)->get();

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found to migrate.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Starting migrations for %d tenant(s)...', $tenants->count()));

        $isParallel = (bool)$this->option('parallel');
        $threads = max(1, (int)$this->option('threads'));

        if ($isParallel) {
            return $this->migrateInParallel($tenants, $threads);
        }

        return $this->migrateSequentially($tenants);
    }

    /**
     * Migra un único tenant
     */
    protected function migrateSingleTenant(int $tenantId): int
    {
        $tenant = Empresa::find($tenantId);
        if (!$tenant) {
            $this->error(sprintf('Tenant with ID %d not found.', $tenantId));
            return self::FAILURE;
        }

        try {
            /** @var MigrateTenantAction $action */
            $action = app(config('multitenancy.actions.migrate_tenant', MigrateTenantAction::class));

            if ($this->option('fresh')) {
                $action->fresh(true);
            }

            if ($this->option('seed')) {
                $action->seed(true);
            }

            $action->output($this->output)->execute($tenant);
            $this->info(sprintf('Tenant [%s (ID: %d)] migrated successfully.', $tenant->nombre, $tenant->id));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(sprintf('Migration failed for tenant [%s (ID: %d)]: %s', $tenant->nombre, $tenant->id, $e->getMessage()));
            return self::FAILURE;
        }
    }

    /**
     * Migra inquilinos secuencialmente
     */
    protected function migrateSequentially($tenants): int
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($tenants as $tenant) {
            $tenantStart = microtime(true);
            $this->line(sprintf('Migrating tenant [%s] (ID: %d)...', $tenant->nombre, $tenant->id));

            try {
                /** @var MigrateTenantAction $action */
                $action = app(config('multitenancy.actions.migrate_tenant', MigrateTenantAction::class));

                if ($this->option('fresh')) {
                    $action->fresh(true);
                }

                if ($this->option('seed')) {
                    $action->seed(true);
                }

                $action->output($this->output)->execute($tenant);

                $duration = round(microtime(true) - $tenantStart, 2);
                $results[] = [
                    'id' => $tenant->id,
                    'nombre' => $tenant->nombre,
                    'status' => 'OK',
                    'duration' => $duration . 's',
                ];
            } catch (Throwable $e) {
                $duration = round(microtime(true) - $tenantStart, 2);
                $results[] = [
                    'id' => $tenant->id,
                    'nombre' => $tenant->nombre,
                    'status' => 'ERROR: ' . $e->getMessage(),
                    'duration' => $duration . 's',
                ];
            }
        }

        $totalDuration = round(microtime(true) - $startTime, 2);
        $this->table(['ID', 'Tenant', 'Status', 'Duration'], $results);
        $this->info(sprintf('Completed migrations for %d tenants in %s seconds.', count($results), $totalDuration));

        return self::SUCCESS;
    }

    /**
     * Migra inquilinos concurrentemente usando pool de procesos
     */
    protected function migrateInParallel($tenants, int $threads): int
    {
        $this->info(sprintf('Executing concurrent migrations with %d worker threads...', $threads));
        $startTime = microtime(true);

        $baseCmd = [PHP_BINARY, base_path('artisan'), 'tenant:migrate-all', '--force'];
        if ($this->option('fresh')) {
            $baseCmd[] = '--fresh';
        }
        if ($this->option('seed')) {
            $baseCmd[] = '--seed';
        }

        $tenantChunks = $tenants->chunk($threads);
        $allResults = [];

        foreach ($tenantChunks as $chunk) {
            $pool = Process::pool(function (Pool $pool) use ($chunk, $baseCmd) {
                foreach ($chunk as $tenant) {
                    $cmd = array_merge($baseCmd, ["--tenant={$tenant->id}"]);
                    $pool->as("tenant_{$tenant->id}")->command($cmd);
                }
            });

            $responses = $pool->start()->wait();

            foreach ($chunk as $tenant) {
                $key = "tenant_{$tenant->id}";
                $response = $responses[$key] ?? null;
                $success = $response && $response->successful();

                $allResults[] = [
                    'id' => $tenant->id,
                    'nombre' => $tenant->nombre,
                    'status' => $success ? 'OK' : 'ERROR',
                    'output' => $response ? trim($response->output() ?: $response->errorOutput()) : 'No output',
                ];
            }
        }

        $totalDuration = round(microtime(true) - $startTime, 2);
        $this->table(['ID', 'Tenant', 'Status', 'Output Summary'], array_map(function ($item) {
            return [
                $item['id'],
                $item['nombre'],
                $item['status'],
                mb_strimwidth($item['output'], 0, 60, '...'),
            ];
        }, $allResults));

        $this->info(sprintf('Completed parallel migrations for %d tenants in %s seconds.', count($allResults), $totalDuration));
        return self::SUCCESS;
    }
}
