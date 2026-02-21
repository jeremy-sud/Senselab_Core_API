<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $content = preg_replace_callback(
        '/(function\s+[a-zA-Z0-9_]+\s*\()([^)]*)(\))/',
        function ($matches) {
            $params = explode(',', $matches[2]);
            $newParams = [];
            foreach ($params as $param) {
                $param = trim($param);
                if (empty($param)) continue;
                
                // If it's just a variable name without a type (e.g., $id or $name)
                // Or if it has a default value but no type (e.g., $id = null)
                if (preg_match('/^\$[a-zA-Z0-9_]+(\s*=.*)?$/', $param)) {
                    $newParams[] = 'mixed ' . $param;
                } else {
                    $newParams[] = $param;
                }
            }
            return $matches[1] . implode(', ', $newParams) . $matches[3];
        },
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
