#!/usr/bin/env php
<?php

/**
 * Script para documentar los 6 controllers restantes (relaciones, inventario, etc)
 */

$controllersToDocument = [
    [
        'file' => 'app/Http/Controllers/API/UrlShortenerController.php',
        'tag' => 'URL Shortener',
        'description' => 'Servicio de acortamiento de URLs para compartir enlaces',
        'path' => '/api/url-shortener',
        'methods' => [
            'index' => ['summary' => 'Listar URLs acortadas', 'method' => 'Get'],
            'store' => ['summary' => 'Crear URL corta', 'method' => 'Post'],
            'show' => ['summary' => 'Obtener URL corta', 'method' => 'Get'],
            'destroy' => ['summary' => 'Eliminar URL corta', 'method' => 'Delete'],
        ],
    ],
    [
        'file' => 'app/Http/Controllers/ConsecutivoFEController.php',
        'tag' => 'Consecutivos FE',
        'description' => 'Gestión de consecutivos de facturación electrónica por tipo de documento',
        'path' => '/api/consecutivo-fe',
        'methods' => [
            'index' => ['summary' => 'Listar consecutivos FE', 'method' => 'Get'],
            'store' => ['summary' => 'Crear consecutivo FE', 'method' => 'Post'],
            'show' => ['summary' => 'Obtener consecutivo FE', 'method' => 'Get'],
            'update' => ['summary' => 'Actualizar consecutivo FE', 'method' => 'Put'],
            'destroy' => ['summary' => 'Eliminar consecutivo FE', 'method' => 'Delete'],
        ],
    ],
    [
        'file' => 'app/Http/Controllers/EntidadEtiquetaController.php',
        'tag' => 'Entidad-Etiqueta',
        'description' => 'Gestión de relaciones muchos-a-muchos entre entidades y etiquetas',
        'path' => '/api/entidad-etiqueta',
        'methods' => [
            'index' => ['summary' => 'Listar relaciones entidad-etiqueta', 'method' => 'Get'],
            'store' => ['summary' => 'Crear relación entidad-etiqueta', 'method' => 'Post'],
            'destroy' => ['summary' => 'Eliminar relación entidad-etiqueta', 'method' => 'Delete'],
        ],
    ],
    [
        'file' => 'app/Http/Controllers/InventarioProductoController.php',
        'tag' => 'Inventario de Productos',
        'description' => 'Gestión de inventario y existencias de productos por sucursal',
        'path' => '/api/inventario-producto',
        'methods' => [
            'index' => ['summary' => 'Listar inventario de productos', 'method' => 'Get'],
            'store' => ['summary' => 'Crear registro de inventario', 'method' => 'Post'],
            'show' => ['summary' => 'Obtener inventario de producto', 'method' => 'Get'],
            'update' => ['summary' => 'Actualizar inventario', 'method' => 'Put'],
        ],
    ],
    [
        'file' => 'app/Http/Controllers/RolPermisoController.php',
        'tag' => 'Rol-Permiso',
        'description' => 'Gestión de permisos asignados a roles (control de acceso)',
        'path' => '/api/rol-permiso',
        'methods' => [
            'index' => ['summary' => 'Listar permisos de roles', 'method' => 'Get'],
            'store' => ['summary' => 'Asignar permiso a rol', 'method' => 'Post'],
            'destroy' => ['summary' => 'Quitar permiso de rol', 'method' => 'Delete'],
        ],
    ],
    [
        'file' => 'app/Http/Controllers/RolUsuarioController.php',
        'tag' => 'Rol-Usuario',
        'description' => 'Gestión de roles asignados a usuarios',
        'path' => '/api/rol-usuario',
        'methods' => [
            'index' => ['summary' => 'Listar roles de usuarios', 'method' => 'Get'],
            'store' => ['summary' => 'Asignar rol a usuario', 'method' => 'Post'],
            'destroy' => ['summary' => 'Quitar rol de usuario', 'method' => 'Delete'],
        ],
    ],
];

foreach ($controllersToDocument as $ctrl) {
    $file = $ctrl['file'];
    
    if (!file_exists($file)) {
        echo "⚠️  Archivo no existe: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Verificar si ya tiene documentación OpenAPI
    if (strpos($content, '#[OA\\') !== false) {
        echo "✅ Ya documentado: $file\n";
        continue;
    }
    
    // Agregar use OpenApi\Attributes as OA
    if (strpos($content, 'use OpenApi\Attributes as OA;') === false) {
        $content = preg_replace(
            '/(use [^;]+;\n)(?=\n(?:class|#\[))/s',
            "$1use OpenApi\\Attributes as OA;\n",
            $content,
            1
        );
    }
    
    // Agregar tag antes de la clase
    $tagDoc = "\n#[OA\\Tag(\n    name: '{$ctrl['tag']}',\n    description: '{$ctrl['description']}'\n)]\n";
    $content = preg_replace(
        '/(class \w+Controller extends Controller)/',
        $tagDoc . '$1',
        $content,
        1
    );
    
    // Documentar cada método
    foreach ($ctrl['methods'] as $methodName => $methodInfo) {
        $httpMethod = $methodInfo['method'];
        $summary = $methodInfo['summary'];
        
        $params = '';
        $path = $ctrl['path'];
        
        if (in_array($methodName, ['show', 'update', 'destroy'])) {
            $path .= '/{id}';
            $params = "        parameters: [\n" .
                     "            new OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'integer')),\n" .
                     "        ],\n";
        }
        
        $responses = "            new OA\\Response(response: 200, description: 'Operación exitosa'),\n";
        
        if ($methodName === 'store') {
            $responses = "            new OA\\Response(response: 201, description: 'Recurso creado'),\n" .
                        "            new OA\\Response(response: 422, description: 'Error de validación'),\n";
        } elseif (in_array($methodName, ['show', 'update', 'destroy'])) {
            $responses .= "            new OA\\Response(response: 404, description: 'No encontrado'),\n";
        }
        
        $methodDoc = "    #[OA\\{$httpMethod}(\n" .
            "        path: '{$path}',\n" .
            "        summary: '{$summary}',\n" .
            "        security: [['sanctum' => []]],\n" .
            "        tags: ['{$ctrl['tag']}'],\n" .
            $params .
            "        responses: [\n" .
            $responses .
            "        ]\n" .
            "    )]\n";
        
        $content = preg_replace(
            "/(\s+)(public function {$methodName}\([^)]*\))/",
            "$1$methodDoc$1$2",
            $content,
            1
        );
    }
    
    file_put_contents($file, $content);
    echo "✅ Documentado: $file\n";
}

echo "\n🎉 Documentación de controllers restantes completada!\n";
