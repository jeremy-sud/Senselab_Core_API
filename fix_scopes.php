<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    if (strpos($content, 'public function scope') !== false) {
        // Add use Illuminate\Database\Eloquent\Builder; if not present
        if (strpos($content, 'use Illuminate\Database\Eloquent\Builder;') === false) {
            $content = preg_replace(
                '/(namespace App\\\\Models;\n\n)/',
                "$1use Illuminate\\Database\\Eloquent\\Builder;\n",
                $content
            );
        }
        
        // Fix parameter type: public function scopeName($query, ...) -> public function scopeName(Builder $query, ...)
        $content = preg_replace(
            '/public\s+function\s+scope([a-zA-Z0-9_]+)\s*\(\s*\$query/',
            'public function scope$1(Builder $query',
            $content
        );
        
        // Fix return type: public function scopeName(Builder $query, ...) -> public function scopeName(Builder $query, ...): Builder
        // We need to match until the closing parenthesis, and if there's no colon after it, add : Builder
        $content = preg_replace(
            '/public\s+function\s+scope([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*(?!:)/',
            'public function scope$1($2): Builder',
            $content
        );
        
        file_put_contents($file, $content);
    }
}
echo "Done.\n";
