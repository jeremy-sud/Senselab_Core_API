<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $content = preg_replace(
        '/(protected\s+static\s+function\s+boot(?:ed)?\s*\([^)]*\))\s*:\s*mixed/',
        "$1: void",
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
