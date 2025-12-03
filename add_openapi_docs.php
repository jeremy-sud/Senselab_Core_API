#!/usr/bin/env php
<?php

/**
 * Script para agregar documentación OpenAPI masivamente a controllers CRUD simples
 */

$controllersToDocument = [
    [
        'file' => 'app/Http/Controllers/API/TipoComprobanteFeController.php',
        'tag' => 'Tipos de Comprobante FE',
        'description' => 'Gestión de tipos de comprobantes de facturación electrónica (Factura, Nota Crédito, Tiquete, etc)',
        'path' => '/api/tipo-comprobante-fe',
        'singular' => 'tipo de comprobante',
        'plural' => 'tipos de comprobante',
    ],
    [
        'file' => 'app/Http/Controllers/API/MensajeHaciendaController.php',
        'tag' => 'Mensajes Hacienda',
        'description' => 'Gestión de mensajes de respuesta de Hacienda (aceptación/rechazo de comprobantes)',
        'path' => '/api/mensaje-hacienda',
        'singular' => 'mensaje de hacienda',
        'plural' => 'mensajes de hacienda',
    ],
    [
        'file' => 'app/Http/Controllers/API/CodigoActividadEconomicaController.php',
        'tag' => 'Códigos Actividad Económica',
        'description' => 'Catálogo de códigos de actividad económica del Ministerio de Hacienda',
        'path' => '/api/codigo-actividad-economica',
        'singular' => 'código de actividad económica',
        'plural' => 'códigos de actividad económica',
    ],
    [
        'file' => 'app/Http/Controllers/API/TipoClienteController.php',
        'tag' => 'Tipos de Cliente',
        'description' => 'Gestión de tipos/categorías de clientes (minorista, mayorista, etc)',
        'path' => '/api/tipo-cliente',
        'singular' => 'tipo de cliente',
        'plural' => 'tipos de cliente',
    ],
    [
        'file' => 'app/Http/Controllers/API/ZonaGeograficaController.php',
        'tag' => 'Zonas Geográficas',
        'description' => 'Gestión de zonas geográficas de cobertura (provincias, cantones, distritos)',
        'path' => '/api/zona-geografica',
        'singular' => 'zona geográfica',
        'plural' => 'zonas geográficas',
    ],
    [
        'file' => 'app/Http/Controllers/API/CuentaBancariaController.php',
        'tag' => 'Cuentas Bancarias',
        'description' => 'Gestión de cuentas bancarias de la empresa',
        'path' => '/api/cuenta-bancaria',
        'singular' => 'cuenta bancaria',
        'plural' => 'cuentas bancarias',
    ],
    [
        'file' => 'app/Http/Controllers/API/MovimientoBancarioController.php',
        'tag' => 'Movimientos Bancarios',
        'description' => 'Gestión de movimientos bancarios (depósitos, retiros, transferencias)',
        'path' => '/api/movimiento-bancario',
        'singular' => 'movimiento bancario',
        'plural' => 'movimientos bancarios',
    ],
    [
        'file' => 'app/Http/Controllers/API/PlanillaCcssController.php',
        'tag' => 'Planillas CCSS',
        'description' => 'Gestión de planillas de Caja Costarricense de Seguro Social',
        'path' => '/api/planilla-ccss',
        'singular' => 'planilla CCSS',
        'plural' => 'planillas CCSS',
    ],
    [
        'file' => 'app/Http/Controllers/API/DeclaracionTributariaController.php',
        'tag' => 'Declaraciones Tributarias',
        'description' => 'Gestión de declaraciones tributarias (D104, D101, etc)',
        'path' => '/api/declaracion-tributaria',
        'singular' => 'declaración tributaria',
        'plural' => 'declaraciones tributarias',
    ],
    [
        'file' => 'app/Http/Controllers/API/DeduccionLegalController.php',
        'tag' => 'Deducciones Legales',
        'description' => 'Gestión de deducciones legales aplicables a nómina',
        'path' => '/api/deduccion-legal',
        'singular' => 'deducción legal',
        'plural' => 'deducciones legales',
    ],
    [
        'file' => 'app/Http/Controllers/API/RetencionImpuestoController.php',
        'tag' => 'Retenciones de Impuesto',
        'description' => 'Gestión de retenciones de impuesto sobre renta',
        'path' => '/api/retencion-impuesto',
        'singular' => 'retención de impuesto',
        'plural' => 'retenciones de impuesto',
    ],
    [
        'file' => 'app/Http/Controllers/API/LogAccesoSistemaController.php',
        'tag' => 'Logs de Acceso',
        'description' => 'Registro de accesos al sistema (auditoría de login/logout)',
        'path' => '/api/log-acceso-sistema',
        'singular' => 'log de acceso',
        'plural' => 'logs de acceso',
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
    
    // Agregar use OpenApi\Attributes as OA después de los otros uses
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
    
    // Documentar index
    $indexDoc = "    #[OA\\Get(\n" .
        "        path: '{$ctrl['path']}',\n" .
        "        summary: 'Listar {$ctrl['plural']}',\n" .
        "        security: [['sanctum' => []]],\n" .
        "        tags: ['{$ctrl['tag']}'],\n" .
        "        responses: [\n" .
        "            new OA\\Response(response: 200, description: 'Listado de {$ctrl['plural']}'),\n" .
        "        ]\n" .
        "    )]\n";
    $content = preg_replace(
        '/(\s+)(public function index\([^)]*\))/',
        "$1$indexDoc$1$2",
        $content,
        1
    );
    
    // Documentar store
    $storeDoc = "    #[OA\\Post(\n" .
        "        path: '{$ctrl['path']}',\n" .
        "        summary: 'Crear {$ctrl['singular']}',\n" .
        "        security: [['sanctum' => []]],\n" .
        "        tags: ['{$ctrl['tag']}'],\n" .
        "        responses: [\n" .
        "            new OA\\Response(response: 201, description: '{$ctrl['singular']} creado'),\n" .
        "            new OA\\Response(response: 422, description: 'Error de validación'),\n" .
        "        ]\n" .
        "    )]\n";
    $content = preg_replace(
        '/(\s+)(public function store\([^)]*\))/',
        "$1$storeDoc$1$2",
        $content,
        1
    );
    
    // Documentar show
    $showDoc = "    #[OA\\Get(\n" .
        "        path: '{$ctrl['path']}/{id}',\n" .
        "        summary: 'Obtener {$ctrl['singular']}',\n" .
        "        security: [['sanctum' => []]],\n" .
        "        tags: ['{$ctrl['tag']}'],\n" .
        "        parameters: [\n" .
        "            new OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'integer')),\n" .
        "        ],\n" .
        "        responses: [\n" .
        "            new OA\\Response(response: 200, description: '{$ctrl['singular']} encontrado'),\n" .
        "            new OA\\Response(response: 404, description: 'No encontrado'),\n" .
        "        ]\n" .
        "    )]\n";
    $content = preg_replace(
        '/(\s+)(public function show\([^)]*\))/',
        "$1$showDoc$1$2",
        $content,
        1
    );
    
    // Documentar update
    $updateDoc = "    #[OA\\Put(\n" .
        "        path: '{$ctrl['path']}/{id}',\n" .
        "        summary: 'Actualizar {$ctrl['singular']}',\n" .
        "        security: [['sanctum' => []]],\n" .
        "        tags: ['{$ctrl['tag']}'],\n" .
        "        parameters: [\n" .
        "            new OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'integer')),\n" .
        "        ],\n" .
        "        responses: [\n" .
        "            new OA\\Response(response: 200, description: '{$ctrl['singular']} actualizado'),\n" .
        "            new OA\\Response(response: 404, description: 'No encontrado'),\n" .
        "            new OA\\Response(response: 422, description: 'Error de validación'),\n" .
        "        ]\n" .
        "    )]\n";
    $content = preg_replace(
        '/(\s+)(public function update\([^)]*\))/',
        "$1$updateDoc$1$2",
        $content,
        1
    );
    
    // Documentar destroy
    $destroyDoc = "    #[OA\\Delete(\n" .
        "        path: '{$ctrl['path']}/{id}',\n" .
        "        summary: 'Eliminar {$ctrl['singular']}',\n" .
        "        security: [['sanctum' => []]],\n" .
        "        tags: ['{$ctrl['tag']}'],\n" .
        "        parameters: [\n" .
        "            new OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'integer')),\n" .
        "        ],\n" .
        "        responses: [\n" .
        "            new OA\\Response(response: 200, description: '{$ctrl['singular']} eliminado'),\n" .
        "            new OA\\Response(response: 404, description: 'No encontrado'),\n" .
        "        ]\n" .
        "    )]\n";
    $content = preg_replace(
        '/(\s+)(public function destroy\([^)]*\))/',
        "$1$destroyDoc$1$2",
        $content,
        1
    );
    
    file_put_contents($file, $content);
    echo "✅ Documentado: $file\n";
}

echo "\n🎉 Documentación masiva completada!\n";
