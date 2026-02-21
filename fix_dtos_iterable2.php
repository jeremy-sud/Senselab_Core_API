<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/DTOs');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        // Fix constructor array parameters
        if (preg_match('/(public|private|protected) function __construct\((.*?)\)\s*\{/s', $content, $matches)) {
            $visibility = $matches[1];
            $params = $matches[2];
            if (preg_match_all('/array\s+\$([a-zA-Z0-9_]+)/', $params, $paramMatches)) {
                // Check if it already has a docblock
                if (!preg_match('/\/\*\*\s*\n(?:\s*\*\s*@param array<mixed> \$[a-zA-Z0-9_]+\s*\n)+\s*\*\/\s*\n\s*(public|private|protected) function __construct/', $content)) {
                    $phpdoc = "/**\n";
                    foreach ($paramMatches[1] as $paramName) {
                        $phpdoc .= "     * @param array<mixed> \$$paramName\n";
                    }
                    $phpdoc .= "     */\n    $visibility function __construct";
                    
                    $content = preg_replace('/' . $visibility . ' function __construct/', $phpdoc, $content, 1);
                }
            }
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "Fixed iterable in: " . $file->getPathname() . "\n";
        }
    }
}
