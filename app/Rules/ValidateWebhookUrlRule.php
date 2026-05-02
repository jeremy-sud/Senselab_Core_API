<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validación de URL para Webhooks - Prevención de SSRF
 *
 * Esta regla valida que las URLs de webhooks:
 * 1. Sean URLs válidas
 * 2. No apunten a IPs privadas o bloqueadas
 * 3. No usen puertos reservados
 * 4. No accedan a endpoints de metadata (AWS, GCP, etc)
 *
 * @see https://owasp.org/www-community/attacks/Server_Side_Request_Forgery
 */
class ValidateWebhookUrlRule implements ValidationRule
{
    // Rangos privados CIDR según RFC 1918
    private const PRIVATE_RANGES = [
        '10.0.0.0/8',           // Clase A privada
        '172.16.0.0/12',        // Clase B privada
        '192.168.0.0/16',       // Clase C privada
        '127.0.0.0/8',          // Loopback
        '169.254.0.0/16',       // Link-local (APIPA)
        '224.0.0.0/4',          // Multicast
        '240.0.0.0/4',          // Reservado
        '0.0.0.0/8',            // Este host
        '::/128',               // IPv6 loopback
    ];

    private const BLOCKED_IPS = [
        '0.0.0.0',              // Broadcast
        '255.255.255.255',      // Broadcast
        '169.254.169.254',      // AWS metadata service
        '169.254.169.253',      // AWS metadata service v2
        '::1',                  // IPv6 loopback
        '::',                   // IPv6 unspecified
    ];

    private const BLOCKED_HOSTS = [
        'localhost',
        'localhost.localdomain',
        '127.0.0.1',
        '::1',
    ];

    // Puertos bloqueados (reservados y servicios internos)
    private const BLOCKED_PORTS = [
        22,      // SSH
        23,      // Telnet
        25,      // SMTP
        53,      // DNS
        69,      // TFTP
        135,     // RPC Endpoint Mapper
        139,     // NetBIOS
        445,     // SMB
        873,     // rsync
        1433,    // MSSQL
        3306,    // MySQL
        5432,    // PostgreSQL
        5984,    // CouchDB
        6379,    // Redis
        7001,    // Cassandra
        8086,    // InfluxDB
        9200,    // Elasticsearch
        9300,    // Elasticsearch
        11211,   // Memcached
        27017,   // MongoDB
        50070,   // Hadoop NameNode
    ];

    /**
     * Validar URL de webhook
     */
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void
    {
        // Validar que sea una URL válida
        $url = filter_var($value, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        // Extraer componentes
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?? 443;
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!$host) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        // Validar esquema (solo HTTP/HTTPS)
        if (!in_array($scheme, ['http', 'https'])) {
            $fail(__('validation.custom.webhook_url.invalid_scheme'));
            return;
        }

        // Verificar si host es una IP bloqueada directamente
        if ($this->isBlockedHost($host)) {
            $fail(__('validation.custom.webhook_url.blocked_host', [
                'host' => $host,
            ]));
            return;
        }

        // Verificar puerto bloqueado
        if ($this->isBlockedPort($port)) {
            $fail(__('validation.custom.webhook_url.blocked_port', [
                'port' => $port,
            ]));
            return;
        }

        // Resolver hostname a IP
        $ip = $this->resolveHostToIp($host);

        if ($ip === false) {
            $fail(__('validation.custom.webhook_url.unresolvable_host'));
            return;
        }

        // Verificar que no sea IP privada
        if ($this->isPrivateIp($ip)) {
            $fail(__('validation.custom.webhook_url.private_network', [
                'ip' => $ip,
            ]));
            return;
        }

        // Verificar que no sea IP bloqueada
        if (in_array($ip, self::BLOCKED_IPS)) {
            $fail(__('validation.custom.webhook_url.blocked_ip', [
                'ip' => $ip,
            ]));
            return;
        }
    }

    /**
     * Verificar si el host está bloqueado
     */
    private function isBlockedHost(string $host): bool
    {
        return in_array(strtolower($host), array_map('strtolower', self::BLOCKED_HOSTS));
    }

    /**
     * Verificar si el puerto está bloqueado
     */
    private function isBlockedPort(int $port): bool
    {
        // Permitir puertos HTTP y HTTPS
        if (in_array($port, [80, 443])) {
            return false;
        }

        return in_array($port, self::BLOCKED_PORTS);
    }

    /**
     * Resolver hostname a IP
     */
    private function resolveHostToIp(string $host): string|false
    {
        // Usar getaddrinfo para soporte IPv6
        $result = getaddrinfo($host, null, AF_UNSPEC, SOCK_STREAM);

        if ($result === false) {
            return false;
        }

        // Retornar la primera IP
        if (is_array($result) && count($result) > 0) {
            return $result[0]['addr'];
        }

        return false;
    }

    /**
     * Verificar si IP está en rango privado
     */
    private function isPrivateIp(string $ip): bool
    {
        foreach (self::PRIVATE_RANGES as $range) {
            if ($this->isIpInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si IP está en rango CIDR
     *
     * @param string $ip IP a verificar (IPv4 o IPv6)
     * @param string $range Rango CIDR (ej: 10.0.0.0/8)
     */
    private function isIpInRange(string $ip, string $range): bool
    {
        if (str_contains($ip, ':')) {
            // IPv6
            return $this->isIpv6InRange($ip, $range);
        }

        // IPv4
        [$subnet, $bits] = explode('/', $range);
        $bits = (int) $bits;

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);

        if ($ip === false || $subnet === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);
        $subnet &= (int) $mask;

        return ($ip & (int) $mask) === $subnet;
    }

    /**
     * Verificar si IPv6 está en rango CIDR
     */
    private function isIpv6InRange(string $ip, string $range): bool
    {
        try {
            [$subnet, $bits] = explode('/', $range);
            $bits = (int) $bits;

            $ipPacked = inet_pton($ip);
            $subnetPacked = inet_pton($subnet);

            if ($ipPacked === false || $subnetPacked === false) {
                return false;
            }

            $mask = '';
            for ($i = 0; $i < ceil($bits / 8); $i++) {
                $mask .= chr(255);
            }

            $mask .= str_repeat(chr(0), 16 - strlen($mask));

            return (($ipPacked & $mask) === ($subnetPacked & $mask));
        } catch (\Throwable $e) {
            return false;
        }
    }
}
