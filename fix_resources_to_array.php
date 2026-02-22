<?php

$files = glob('app/Http/Resources/*.php');
$files = array_merge($files, glob('app/Http/Resources/**/*.php'));
$files = array_merge($files, glob('app/Http/Resources/**/**/*.php'));
$files = array_merge($files, glob('app/Http/Resources/**/**/**/*.php'));
$files = array_merge($files, glob('app/Http/Resources/**/**/**/**/*.php'));

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'public function toArray(Request $request): array') !== false && strpos($content, '@return array<string, mixed>') === false) {
        $content = str_replace(
            'public function toArray(Request $request): array',
            "/**\n     * @return array<string, mixed>\n     */\n    public function toArray(Request \$request): array",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
