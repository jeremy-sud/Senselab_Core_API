<?php

namespace App\Multitenancy\TenantFinder;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class HeaderSubdomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Empresa
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null) {
            return null;
        }

        return $this->locateTenant($identifier);
    }

    protected function resolveIdentifier(Request $request): ?string
    {
        foreach ($this->headerKeys() as $header) {
            $value = $request->header($header);

            if ($value !== null && $value !== '') {
                return trim((string) $value);
            }
        }

        return $this->resolveFromSubdomain($request);
    }

    protected function headerKeys(): array
    {
        return config('multitenancy.identification.header_keys', [
            'X-Empresa-Id',
            'X-Tenant-Id',
            'X-Tenant',
        ]);
    }

    protected function resolveFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();

        if (! $host) {
            return null;
        }

        $baseDomain = config('multitenancy.identification.base_domain');
        $subdomain = null;

        if ($baseDomain && str_ends_with($host, $baseDomain)) {
            $subdomain = trim(substr($host, 0, -strlen($baseDomain)), '.');
        } elseif (str_contains($host, '.')) {
            $parts = explode('.', $host);
            $subdomain = array_shift($parts);
        }

        if (! $subdomain) {
            return null;
        }

        $subdomain = strtolower($subdomain);

        if (in_array($subdomain, config('multitenancy.identification.ignore_subdomains', ['www', 'api']), true)) {
            return null;
        }

        return $subdomain;
    }

    protected function locateTenant(string $identifier): ?Empresa
    {
        if (is_numeric($identifier)) {
            return Empresa::query()->find((int) $identifier);
        }

        return Empresa::query()
            ->where('subdominio', $identifier)
            ->first();
    }
}
