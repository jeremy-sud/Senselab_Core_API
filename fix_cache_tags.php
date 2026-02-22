<?php

$files = glob('app/Http/Controllers/API/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('/(\/\*\* @var array<int, string> \*\/\n\s*)+/', "/** @var array<int, string> */\n    ", $content);
    file_put_contents($file, $content);
}
