<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/DTOs/Transformers');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Fix transform() return type
        $content = preg_replace(
            '/public function transform\([^)]+\): array\s*\{/',
            "/**\n     * @return array<string, mixed>\n     */\n    $0",
            $content
        );
        
        // Fix collection() parameter and return type
        $content = preg_replace(
            '/public static function collection\(iterable \$([a-zA-Z0-9_]+)\): array\s*\{/',
            "/**\n     * @param iterable<int, mixed> \$$1\n     * @return array<int, array<string, mixed>>\n     */\n    $0",
            $content
        );
        
        file_put_contents($file->getPathname(), $content);
        echo "Fixed transformers in: " . $file->getPathname() . "\n";
    }
}
