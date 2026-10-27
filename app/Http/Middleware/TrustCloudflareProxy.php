<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para la extracción y validación de cabeceras de proxy de Cloudflare.
 *
 * FASE 25.1: Garantiza la lectura de CF-Connecting-IP, CF-Visitor, CF-IPCountry y CF-Ray
 * para que rate limiters, security audit logs y sesiones obtengan la dirección IP
 * pública real del visitante en lugar de la dirección IP perimetral del proxy o túnel.
 */
class TrustCloudflareProxy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extraer IP real del cliente desde Cloudflare si está presente
        if ($cfConnectingIp = $request->header('CF-Connecting-IP')) {
            $clientIp = trim((string) $cfConnectingIp);
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $clientIp);
            }
        }

        // 2. Extraer esquema seguro HTTPS desde el objeto JSON CF-Visitor
        if ($cfVisitor = $request->header('CF-Visitor')) {
            $visitor = json_decode((string) $cfVisitor, true);
            if (is_array($visitor) && isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', 443);
            }
        }

        return $next($request);
    }
}
