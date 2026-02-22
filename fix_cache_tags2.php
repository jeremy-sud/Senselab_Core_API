<?php

$files = glob('app/Http/Controllers/API/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    if (preg_match('/protected \$cacheTags\s*=/', $content)) {
        $content = preg_replace('/protected \$cacheTags\s*=/', "/** @var array<int, string> */\n    protected array \$cacheTags =", $content);
        $changed = true;
    }
    
    if (preg_match('/protected \$cacheTTL\s*=/', $content)) {
        $content = preg_replace('/protected \$cacheTTL\s*=/', "protected int \$cacheTTL =", $content);
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
