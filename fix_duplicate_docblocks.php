<?php

$files = glob('app/Http/Controllers/API/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('/(\/\*\* @var array<int, string> \*\/\n\s*)+/', "/** @var array<int, string> */\n    ", $content);
    $content = preg_replace('/\/\*\*\n\s+\* Tags para invalidación de cache\n\s+\* @var array<string>\n\s+\*\/\n\s+\/\*\* @var array<int, string> \*\//', "/**\n     * Tags para invalidación de cache\n     * @var array<int, string>\n     */", $content);
    file_put_contents($file, $content);
}
