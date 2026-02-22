<?php

$files = glob('app/Http/Controllers/API/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'protected function generateCacheKey(string $method, array $params = []): string') !== false) {
        $content = str_replace(
            'protected function generateCacheKey(string $method, array $params = []): string',
            "/**\n     * @param array<string, mixed> \$params\n     */\n    protected function generateCacheKey(string \$method, array \$params = []): string",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
