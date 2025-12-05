<?php
// Redirigir a Laravel para manejar la ruta /docs
$_SERVER['REQUEST_URI'] = '/docs' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
require __DIR__ . '/../index.php';
