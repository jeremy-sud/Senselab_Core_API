<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para monitorear endpoints obsoletos o programados para apagado (Sunset).
 * 
 * Inyecta las cabeceras estándar RFC 8594 (Deprecation y Sunset) en las respuestas
 * cuando los clientes consumen APIs obsoletas, permitiendo al frontend alertar en consola.
 */
class SunsetMonitorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ejemplo: Si el cliente consume la API v4 obsoleta, inyectamos advertencias
        if ($request->is('api/v4/*')) {
            $response->headers->set('Deprecation', '2026-01-01');
            $response->headers->set('Sunset', '2026-12-31');
        }

        return $response;
    }
}
