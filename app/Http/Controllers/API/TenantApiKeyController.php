<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantApiKeyController extends Controller
{
    /**
     * List all active API keys for the authenticated tenant.
     */
    public function index(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $keys = ApiKey::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->get()
            ->map(function ($key) {
                return [
                    'id' => (string) $key->id,
                    'name' => $key->name,
                    'prefix' => $key->prefix,
                    'token' => $key->prefix . '••••••••••••••••', // Mask real token hash representation
                    'environment' => $key->environment,
                    'created_at' => $key->created_at ? $key->created_at->toISOString() : now()->toISOString(),
                ];
            });

        return response()->json($keys);
    }

    /**
     * Generate a new secure API key.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'environment' => 'required|in:live,sandbox',
        ]);

        $env = $request->input('environment', 'sandbox');
        $prefix = $env === 'live' ? 'sl_live_' : 'sl_sandbox_';
        
        // Generate raw unique cryptographically secure token
        $rawToken = bin2hex(random_bytes(16));
        $fullToken = $prefix . $rawToken;

        $key = ApiKey::create([
            'empresa_id' => $request->user()->empresa_id,
            'name' => trim($request->input('name')),
            'prefix' => $prefix,
            'token_hash' => hash('sha256', $fullToken),
            'environment' => $env,
            'activo' => true,
        ]);

        return response()->json([
            'id' => (string) $key->id,
            'name' => $key->name,
            'prefix' => $prefix,
            'token' => $fullToken, // Expose full plain text token once
            'environment' => $env,
            'created_at' => $key->created_at ? $key->created_at->toISOString() : now()->toISOString(),
            'message' => 'Guarde esta llave de forma segura. No se volverá a mostrar.',
        ], 201);
    }

    /**
     * Revoke (deactivate) an API key.
     */
    public function revoke(Request $request, string $id)
    {
        $empresaId = $request->user()->empresa_id;

        $key = ApiKey::where('empresa_id', $empresaId)->find($id);

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Llave de API no encontrada o no pertenece a su cuenta.',
            ], 404);
        }

        $key->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => "Llave de API revocada con éxito.",
        ]);
    }
}
