<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $className = basename($file, '.php');
    
    if (strpos($content, 'use HasFactory;') !== false) {
        $content = preg_replace(
            '/use\s+HasFactory;/',
            "/** @use HasFactory<\\Database\\Factories\\{$className}Factory> */\n    use HasFactory;",
            $content
        );
        file_put_contents($file, $content);
    }
}
echo "Done.\n";
