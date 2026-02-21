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
        // Only replace if it doesn't have a PHPDoc right above it
        if (preg_match("/\n\s+(public|protected|private)\s+(static\s+)?function\s+$method\s*\(/", $content)) {
            $content = preg_replace(
                "/\n(\s+)(public|protected|private)\s+(static\s+)?function\s+$method\s*\(/",
                "\n$1/**\n$1 * @return array<string, mixed>\n$1 */\n$1$2 $3function $method(",
                $content
            );
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
    }
}
echo "Done.\n";
