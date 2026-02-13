create_php_return_types_fixer.php
<?php

/**
 * Script para agregar autom_aticamente return types a métodos de Laravel controllers
 * basado en el patrón RESTful estándar
 */

$controllersPath = '/home/dawnweaber/Workspace/Ursol-CAST-API/app/Http/Controllers/API';
$commonReturnTypes = [
    'index' => 'Illuminate\Http\Resources\Json\AnonymousResourceCollection',
    'show' => 'Illuminate\Http\Response',  
    'store' => 'Illuminate\Http\JsonResponse',
    'update' => 'Illuminate\Http\Response',
    'destroy' => 'Illuminate\Http\Response',
];

$controllers = array_filter(
    scandir($controllersPath),
    fn($file) => str_ends_with($file, '.php') && $file !== 'ApiConstants.php'
);

$stats = [
    'processed' => 0,
    'modified' => 0,
    'errors' => []
];

foreach ($controllers as $controller) {
    $filePath = "$controllersPath/$controller";
    $content = file_get_contents($filePath);
    $original = $content;
    
    // Verificar métodos sin return type
    foreach (['index', 'show', 'store', 'update', 'destroy'] as $method) {
        // Buscar método sin return type
        $pattern = "public function {$method}\(([^)]*)\) \{";
        $replacement = "public function {$method}($1): Response {";
        
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        $stats['modified']++;
    }
    
    $stats['processed']++;
}

echo "=== RETURN TYPES FIXER ===\n";
echo "Controladores procesados: {$stats['processed']}\n";
echo "Controladores modificados: {$stats['modified']}\n";
echo "\nERRORES:\n";
foreach ($stats['errors'] as $error) {
    echo "  - $error\n";
}
