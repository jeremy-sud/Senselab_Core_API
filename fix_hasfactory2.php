<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $className = basename($file, '.php');
    
    // Check if it uses HasFactory and doesn't already have the PHPDoc
    if (strpos($content, 'HasFactory') !== false && strpos($content, '@use HasFactory') === false) {
        // Find the line with `use ... HasFactory ...;` inside the class
        $content = preg_replace(
            '/(\s+)(use\s+[^;]*HasFactory[^;]*;)/',
            "$1/** @use HasFactory<\\Database\\Factories\\{$className}Factory> */$1$2",
            $content
        );
        file_put_contents($file, $content);
    }
}
echo "Done.\n";
