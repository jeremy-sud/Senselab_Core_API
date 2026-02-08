<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use File as FileHelper;

/**
 * Comando: InstallSecurityFeatures
 *
 * Instala automáticamente los traits de seguridad en los modelos.
 * Ejecutar después de completar FASE 1.
 *
 * Uso:
 *   php artisan security:install
 *
 * @package App\Console\Commands
 */
class InstallSecurityFeatures extends Command
{
    protected $signature = 'security:install 
                            {--models= : Modelos específicos a actualizar (separados por coma)}
                            {--force : Ignorar confirmación y ejecutar inmediatamente}';

    protected $description = 'Instala traits de seguridad (HasAuditableEvents, HasEncryptedAttributes) en modelos';

    /**
     * Modelos críticos recomendados para instalación automática
     */
    protected array $criticalModels = [
        'App\Models\Usuario',
        'App\Models\Empresa',
        'App\Models\Rol',
        'App\Models\Permiso',
        'App\Models\Comprobante',
        'App\Models\Pago',
        'App\Models\Factura',
        'App\Models\HaciendaComprobante',
        'App\Models\Proveedor',
        'App\Models\Cliente',
    ];

    /**
     * Traits a instalar
     */
    protected array $traits = [
        'HasAuditableEvents' => 'App\Traits\HasAuditableEvents',
        'HasEncryptedAttributes' => 'App\Traits\HasEncryptedAttributes',
    ];

    public function handle()
    {
        $this->info('🔐 Instalador de Seguridad - FASE 1');
        $this->line('');

        // Determinar qué modelos actualizar
        $modelsToUpdate = $this->getModelsToUpdate();

        if (empty($modelsToUpdate)) {
            $this->error('No hay modelos para actualizar');
            return self::FAILURE;
        }

        $this->info("Modelos a actualizar: " . count($modelsToUpdate));
        foreach ($modelsToUpdate as $model) {
            $this->line("  ✓ {$model}");
        }
        $this->line('');

        if (! $this->option('force')) {
            if (! $this->confirm('¿Continuar con la instalación?', true)) {
                $this->info('Instalación cancelada');
                return self::SUCCESS;
            }
        }

        $this->line('');
        $installed = 0;
        $skipped = 0;

        foreach ($modelsToUpdate as $model) {
            if ($this->installSecurityTraits($model)) {
                $installed++;
                $this->line("  ✓ {$model} - actualizado");
            } else {
                $skipped++;
                $this->line("  ✗ {$model} - omitido (ya instalado)");
            }
        }

        $this->line('');
        $this->info("Instalación completada: {$installed} modelos actualizados, {$skipped} omitidos");

        // Mostrar siguientes pasos
        $this->showNextSteps();

        return self::SUCCESS;
    }

    /**
     * Obtener modelos a actualizar
     */
    protected function getModelsToUpdate(): array
    {
        if ($this->option('models')) {
            return array_map('trim', explode(',', $this->option('models')));
        }

        $this->info('Seleccione qué modelos actualizar:');
        $this->line('  1. Modelos críticos (RECOMENDADO)');
        $this->line('  2. Todos los modelos en app/Models');
        $this->line('  3. Específicos (ingrese nombres)');

        $choice = $this->choice('Opción', ['1', '2', '3'], '1');

        return match($choice) {
            '1' => $this->criticalModels,
            '2' => $this->getAllModels(),
            '3' => $this->getCustomModels(),
        };
    }

    /**
     * Obtener todos los modelos de app/Models
     */
    protected function getAllModels(): array
    {
        $modelsPath = app_path('Models');
        $models = [];

        if (! is_dir($modelsPath)) {
            return [];
        }

        $files = File::files($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $className = 'App\Models\\' . $file->getFilenameWithoutExtension();
                if (class_exists($className)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }

    /**
     * Obtener modelos específicos ingresados por usuario
     */
    protected function getCustomModels(): array
    {
        $input = $this->ask('Ingrese nombres de modelos (separados por coma)');
        
        return array_map(
            fn($name) => 'App\Models\\' . trim($name),
            explode(',', $input)
        );
    }

    /**
     * Instalar traits de seguridad en un modelo
     */
    protected function installSecurityTraits(string $model): bool
    {
        $path = $this->getModelFilePath($model);

        if (! file_exists($path)) {
            $this->warn("  Archivo no encontrado: {$path}");
            return false;
        }

        $content = file_get_contents($path);

        // Verificar si ya tiene los traits
        $alreadyHasTraits = true;
        foreach ($this->traits as $trait) {
            if (strpos($content, $trait) !== false) {
                $alreadyHasTraits = false;
                break;
            }
        }

        if (! $alreadyHasTraits && strpos($content, 'HasAuditableEvents') !== false) {
            return false; // Ya tiene al menos uno
        }

        // Buscar dónde insertar los use statements
        $useStatementInserted = false;
        foreach ($this->traits as $traitName => $traitPath) {
            if (strpos($content, "use {$traitPath}") === false) {
                // Insertar import
                $content = preg_replace(
                    '/^(namespace\s+[^;]+;)/m',
                    "$1\n\nuse {$traitPath};",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    $useStatementInserted = true;
                }
            }
        }

        // Agregar traits a la clase
        $traitUses = "\n        use HasAuditableEvents;\n        use HasEncryptedAttributes;";

        if (strpos($content, $traitUses) === false) {
            // Buscar la apertura de clase y agregar traits después
            $content = preg_replace(
                '/^(class\s+\w+\s+extends\s+Model\s*\n\s*\{)/m',
                "$1{$traitUses}",
                $content,
                1,
                $count
            );

            if ($count > 0 && file_put_contents($path, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener ruta del archivo modelo
     */
    protected function getModelFilePath(string $model): string
    {
        $className = class_basename($model);
        return app_path("Models/{$className}.php");
    }

    /**
     * Mostrar pasos siguientes
     */
    protected function showNextSteps(): void
    {
        $this->line('');
        $this->info('📋 Próximos pasos recomendados:');
        $this->line('');

        $steps = [
            '1. Ejecutar migración de auditoría:' => '   php artisan migrate',
            '2. Verificar configuración de encriptación:' => '   config/encryption.php',
            '3. Ajustar .env según necesidad:' => [
                '   DATA_ENCRYPTION_ENABLED=true',
                '   AUDIT_ENABLED=true',
                '   AUDIT_RETENTION_DAYS=365',
            ],
            '4. Verificar traits fueron instalados:' => '   grep -r "HasAuditableEvents" app/Models',
            '5. Ejecutar tests:' => '   php artisan test',
        ];

        foreach ($steps as $step => $command) {
            $this->line("  {$step}");
            
            if (is_array($command)) {
                foreach ($command as $cmd) {
                    $this->line("    <fg=cyan>{$cmd}</>");
                }
            } else {
                $this->line("    <fg=cyan>{$command}</>");
            }
            $this->line('');
        }

        $this->info('✅ ¡Instalación completada!');
    }
}
