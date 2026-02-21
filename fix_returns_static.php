<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $content = preg_replace_callback(
        '/(public|protected|private)\s+(static\s+)?function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)\s*\{/',
        function ($matches) {
            $visibility = $matches[1];
            $static = $matches[2];
            $name = $matches[3];
            $params = $matches[4];
            
            if (in_array($name, ['__construct', '__destruct', '__clone'])) {
                return $matches[0];
            }
            
            return "$visibility {$static}function $name($params): mixed\n    {";
        },
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
