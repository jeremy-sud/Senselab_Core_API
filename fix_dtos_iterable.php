<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/DTOs');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        // Fix constructor array parameters
        if (preg_match('/public function __construct\((.*?)\)\s*\{/s', $content, $matches)) {
            $params = $matches[1];
            if (preg_match_all('/array\s+\$([a-zA-Z0-9_]+)/', $params, $paramMatches)) {
                $phpdoc = "/**\n";
                foreach ($paramMatches[1] as $paramName) {
                    $phpdoc .= "     * @param array<mixed> \$$paramName\n";
                }
                $phpdoc .= "     */\n    public function __construct";
                
                $content = preg_replace('/public function __construct/', $phpdoc, $content, 1);
            }
        }
        
        // Fix methods returning array (except toArray, rules, messages, withValidator which are already fixed)
        $content = preg_replace_callback(
            '/public function ([a-zA-Z0-9_]+)\([^)]*\):\s*array\s*\{/',
            function ($matches) {
                $methodName = $matches[1];
                if (in_array($methodName, ['toArray', 'rules', 'messages', 'withValidator', 'toModelData'])) {
                    return $matches[0];
                }
                return "/**\n     * @return array<mixed>\n     */\n    " . $matches[0];
            },
            $content
        );
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "Fixed iterable in: " . $file->getPathname() . "\n";
        }
    }
}
