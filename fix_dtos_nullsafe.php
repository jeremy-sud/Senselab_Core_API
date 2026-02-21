<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/DTOs');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Replace $request->string('field')?->trim()
        $newContent = preg_replace(
            '/\$request->string\(([\'"][^\'"]+[\'"])\)\?->trim\(\)/',
            '$request->filled($1) ? $request->string($1)->trim()->toString() : null',
            $content
        );
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Fixed nullsafe in: " . $file->getPathname() . "\n";
        }
    }
}
