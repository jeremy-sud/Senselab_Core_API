<?php
$methods = [
    'setValorAttribute',
    'actualizarSaldo',
    'incrementarIntentos',
    'marcarComoProcesado',
    'marcarComoError',
    'conciliar',
    'marcarComoLeida',
    'marcarComoNoLeida',
    'marcarComoPagada',
    'marcarComoDeclarada'
];

$files = glob('app/Models/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            $content = preg_replace(
                "/(function\s+$method\s*\([^)]*\))\s*:\s*mixed/",
                "$1: void",
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
