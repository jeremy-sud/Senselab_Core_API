<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Seguridad HTTP Headers
 *
 * Implementa headers de seguridad recomendados por OWASP
 * para proteger contra XSS, clickjacking, MIME sniffing, etc.
 *
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir clickjacking - No permitir iframe desde otros dominios
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevenir MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Habilitar protección XSS del navegador (legacy, pero útil)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Política de referrer - Solo enviar origen en cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // No permitir que la API sea embebida en otros sitios
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Deshabilitar caché para respuestas sensibles (APIs)
        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // HSTS - Forzar HTTPS (solo en producción)
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy básica para API
        // Ajustar según necesidades del frontend
        if ($request->is('api/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'none'"
            );
        }

        // Permissions Policy - Deshabilitar APIs sensibles no necesarias
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        return $response;
    }
}

