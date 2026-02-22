<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CleanCacheJob;

/**
 * Comando para ejecutar limpieza de cache mediante Queue Job
 * Sprint 8.4 - Queue Jobs
 */
class CleanCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clean-scheduled {type=all} {--sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia cache usando Queue Job (async por defecto). Tipos: all, tags, expired, sessions, logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $sync = $this->option('sync');

        $this->info("Iniciando limpieza de cache (tipo: {$type})...");

        if ($sync) {
            // Ejecución síncrona inmediata
            $job = new CleanCacheJob($type);
            $job->handle();
            $this->info('✓ Limpieza completada (modo síncrono)');
        } else {
            // Ejecución asíncrona mediante queue
            CleanCacheJob::dispatch($type);
            $this->info('✓ Job encolado para ejecución asíncrona');
            $this->info('  Usa: php artisan queue:work para procesar');
        }

        return Command::SUCCESS;
    }
}
