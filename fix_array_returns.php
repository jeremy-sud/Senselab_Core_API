<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find methods that return array
    $content = preg_replace_callback(
        '/(?:(\/\*\*.*?\*\/)\s*)?(public|protected|private)\s+(static\s+)?function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*:\s*array\s*\{/s',
        function ($matches) {
            $phpdoc = $matches[1];
            $visibility = $matches[2];
            $static = $matches[3];
            $name = $matches[4];
            
            if (empty($phpdoc)) {
                $phpdoc = "/**\n     * @return array<string, mixed>\n     */";
            } else if (strpos($phpdoc, '@return') === false) {
                $phpdoc = str_replace('*/', "* @return array<string, mixed>\n     */", $phpdoc);
            }
            
            return "$phpdoc\n    $visibility {$static}function $name" . substr($matches[0], strpos($matches[0], '('));
        },
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
