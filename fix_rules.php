<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find public static $rules or public $rules without PHPDoc
    if (preg_match('/(public|protected|private)\s+(static\s+)?\$rules\s*=/', $content)) {
        // If it doesn't have @var array above it
        if (!preg_match('/@var\s+array.*?\n\s*(public|protected|private)\s+(static\s+)?\$rules\s*=/s', $content)) {
            $content = preg_replace(
                '/(\s*)(public|protected|private)\s+(static\s+)?\$rules\s*=/',
                "$1/**\n$1 * @var array<string, mixed>\n$1 */$1$2 $3\$rules =",
                $content
            );
            file_put_contents($file, $content);
        }
    }
}
echo "Done.\n";
