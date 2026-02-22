<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixModelTimestamps extends Command
{
    protected $signature = 'models:fix-timestamps';
    protected $description = 'Agrega constantes de timestamps personalizados a todos los modelos';

    public function handle(): int
    {
        $modelsPath = app_path('Models');
        $files = File::allFiles($modelsPath);
        $updated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            
            // Verificar si ya tiene las constantes
            if (str_contains($content, "const CREATED_AT = 'creado_en'")) {
                $skipped++;
                continue;
            }

            // Verificar si tiene timestamps = false
            if (str_contains($content, 'public $timestamps = false')) {
                // Reemplazar por timestamps = true y agregar constantes
                $pattern = '/public \$timestamps = false;/';
                $replacement = "public \$timestamps = true;\n\n    /**\n     * Nombres personalizados de las marcas de tiempo.\n     */\n    const CREATED_AT = 'creado_en';\n    const UPDATED_AT = 'actualizado_en';";
                
                $newContent = preg_replace($pattern, $replacement, $content);
                
                if ($newContent !== $content) {
                    File::put($file->getPathname(), $newContent);
                    $this->info("✓ Actualizado: {$file->getFilename()}");
                    $updated++;
                }
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Modelos actualizados: {$updated}");
        $this->info("Modelos omitidos: {$skipped}");
        $this->info(str_repeat('=', 50));

        return 0;
    }
}
