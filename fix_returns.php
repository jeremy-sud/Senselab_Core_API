<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find public/protected/private function name(...) without : type
    // We need to match the closing parenthesis and the opening brace
    $content = preg_replace_callback(
        '/(public|protected|private)\s+function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*\{/',
        function ($matches) {
            $visibility = $matches[1];
            $name = $matches[2];
            $params = $matches[3];
            
            // Skip magic methods
            if (in_array($name, ['__construct', '__destruct', '__clone'])) {
                return $matches[0];
            }
            
            return "$visibility function $name($params): mixed\n    {";
        },
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
