<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Fix $fillable, $hidden, $appends
    $content = preg_replace(
        '/\@var\s+array\s*\n(\s*\*\/\s*\n\s*(?:protected|public|private)\s+\$(?:fillable|hidden|appends)\s*=)/',
        '@var list<string>' . "\n$1",
        $content
    );
    
    // Fix $casts
    $content = preg_replace(
        '/\@var\s+array\s*\n(\s*\*\/\s*\n\s*(?:protected|public|private)\s+\$casts\s*=)/',
        '@var array<string, string>' . "\n$1",
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
