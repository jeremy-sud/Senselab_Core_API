<?php
$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find public function scopeName(Builder $query, $param1, $param2)
    // and change to public function scopeName(Builder $query, mixed $param1, mixed $param2)
    $content = preg_replace_callback(
        '/public\s+function\s+scope([a-zA-Z0-9_]+)\s*\(([^)]*)\)/',
        function ($matches) {
            $params = explode(',', $matches[2]);
            $newParams = [];
            foreach ($params as $param) {
                $param = trim($param);
                if (empty($param)) continue;
                
                // If it's just a variable name without a type (e.g., $id or $name)
                if (preg_match('/^\$[a-zA-Z0-9_]+$/', $param)) {
                    $newParams[] = 'mixed ' . $param;
                } else {
                    $newParams[] = $param;
                }
            }
            return 'public function scope' . $matches[1] . '(' . implode(', ', $newParams) . ')';
        },
        $content
    );
    
    file_put_contents($file, $content);
}
echo "Done.\n";
