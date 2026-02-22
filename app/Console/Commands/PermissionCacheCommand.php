<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PermissionService;
use App\Models\Usuario;

/**
 * Comando para gestionar el cache de permisos del sistema.
 */
class PermissionCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:cache 
                            {action : La acción a realizar (clear|warmup|stats)}
                            {--user= : ID del usuario específico}
                            {--limit=100 : Límite de usuarios para warmup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar cache de permisos (clear, warmup, stats)';

    /**
     * The permission service instance.
     *
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * Create a new command instance.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        if (!in_array($action, ['clear', 'warmup', 'stats'])) {
            $this->error("Acción inválida: {$action}. Use: clear, warmup o stats");
            return self::FAILURE;
        }

        return match($action) {
            'clear' => $this->clearCache(),
            'warmup' => $this->warmupCache(),
            default => $this->showStats(),
        };
    }

    /**
     * Limpiar cache de permisos.
     *
     * @return int
     */
    protected function clearCache(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            // Limpiar cache de un usuario específico
            $usuario = Usuario::find($userId);
            
            if (!$usuario) {
                $this->error("Usuario con ID {$userId} no encontrado.");
                return 1;
            }
            
            $this->permissionService->clearUserPermissionCache($usuario);
            $this->info("✓ Cache de permisos limpiado para usuario: {$usuario->nombre} (ID: {$userId})");
            
        } else {
            // Limpiar todo el cache de permisos
            $this->info('Limpiando cache de permisos...');
            $count = $this->permissionService->clearAllPermissionCache();
            $this->info("✓ Cache de permisos limpiado. Entradas eliminadas: {$count}");
        }

        return 0;
    }

    /**
     * Precalentar cache de permisos.
     *
     * @return int
     */
    protected function warmupCache(): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Precalentando cache de permisos (límite: {$limit} usuarios)...");
        
        $bar = $this->output->createProgressBar($limit);
        $bar->start();
        
        $count = $this->permissionService->warmupPermissionCache($limit);
        
        $bar->finish();
        $this->newLine();
        
        $this->info("✓ Cache precalentado para {$count} usuarios.");
        
        return 0;
    }

    /**
     * Mostrar estadísticas del cache.
     *
     * @return int
     */
    protected function showStats(): int
    {
        $stats = $this->permissionService->getCacheStats();
        
        $this->info('=== Estadísticas de Cache de Permisos ===');
        $this->newLine();
        
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Driver de Cache', $stats['driver']],
                ['Permisos Activos', $stats['total_permissions']],
                ['Roles Activos', $stats['total_roles']],
                ['Usuarios Activos', $stats['total_users']],
                ['Entradas en Cache', $stats['cache_entries']],
            ]
        );
        
        return 0;
    }
}
