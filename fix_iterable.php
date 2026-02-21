<?php
$methods = [
    'getReadableChanges',
    'getChangedFields',
    'toApiResponse',
    'execute',
    'executeHardDelete',
    'executeSoftDelete',
    'executeArchive',
    'executeAnonymize',
    'getConfigurationJson',
    'getDataSummaryReport'
];

$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            // Check if it already has a PHPDoc
            if (!preg_match("/\/\*\*.*?\*\/\s*(public|protected|private)\s+(static\s+)?function\s+$method/s", $content)) {
                $content = preg_replace(
                    "/(\s*)(public|protected|private)\s+(static\s+)?function\s+$method/",
                    "$1/**\n$1 * @return array<string, mixed>\n$1 */$1$2 $3function $method",
                    $content
                );
                $changed = true;
            }
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
    }
}
echo "Done.\n";
