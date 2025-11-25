<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUrlShortenerRequest;
use App\Http\Requests\UpdateUrlShortenerRequest;
use App\Http\Resources\UrlShortenerResource;
use App\Models\UrlShortener;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para gestión de URLs acortadas
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UrlShortenerController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['url-shortener', 'urls'];
    protected int $cacheTTL = 1800; // 30 minutos - URLs dinámicas por clicks

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', UrlShortener::class);
        
        try {
            $cacheKey = $this->getCacheKey('index', [
                'empresa_id' => $request->input('empresa_id'),
                'usuario_id' => $request->input('usuario_id'),
                'activo' => $request->input('activo'),
                'no_expirados' => $request->input('no_expirados'),
                'per_page' => $request->input('per_page', 15)
            ]);
            
            $urls = $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
                $query = UrlShortener::with(['empresa', 'usuario'])
                    ->where('eliminado', false);
                
                // Filtrar por empresa
                if ($request->has('empresa_id')) {
                    $query->where('empresa_id', $request->empresa_id);
                }
                
                // Filtrar por usuario
                if ($request->has('usuario_id')) {
                    $query->where('usuario_id', $request->usuario_id);
                }
                
                // Filtrar por activos
                if ($request->has('activo')) {
                    $query->where('activo', $request->boolean('activo'));
                }
                
                // Filtrar no expirados
                if ($request->boolean('no_expirados')) {
                    $query->noExpirados();
                }
                
                $perPage = $request->input('per_page', 15);
                return $query->orderBy('id', 'desc')->paginate($perPage);
            });
            
            return UrlShortenerResource::collection($urls);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener URLs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUrlShortenerRequest $request)
    {
        $this->authorize('create', UrlShortener::class);
        
        try {
            $validated = $request->validated();
            
            // Generar slug si no se proporciona
            if (empty($validated['slug'])) {
                $validated['slug'] = $this->generateUniqueSlug();
            }
            
            // Generar URL corta
            $validated['url_corta'] = url('/s/' . $validated['slug']);
            
            $urlShortener = UrlShortener::create($validated);
            
            $this->flushCache(['url-shortener', 'urls']);
            
            return response()->json([
                'success' => true,
                'message' => 'URL acortada creada exitosamente',
                'data' => new UrlShortenerResource($urlShortener)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear URL acortada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $url = UrlShortener::with(['empresa', 'usuario'])->findOrFail($id);
            
            $this->authorize('view', $url);
            
            return response()->json([
                'success' => true,
                'data' => new UrlShortenerResource($url)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUrlShortenerRequest $request, $id)
    {
        try {
            $url = UrlShortener::findOrFail($id);
            
            $this->authorize('update', $url);
            
            $validated = $request->validated();
            
            // Si se actualiza el slug, actualizar también la URL corta
            if (isset($validated['slug'])) {
                $validated['url_corta'] = url('/s/' . $validated['slug']);
            }
            
            $url->update($validated);
            
            $this->flushCache(['url-shortener', 'urls']);
            
            return response()->json([
                'success' => true,
                'message' => 'URL actualizada exitosamente',
                'data' => new UrlShortenerResource($url->fresh())
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $url = UrlShortener::findOrFail($id);
            
            $this->authorize('delete', $url);
            $url->update(['eliminado' => true]);
            
            $this->flushCache(['url-shortener', 'urls']);
            
            return response()->json([
                'success' => true,
                'message' => 'URL eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redirigir usando el slug
     */
    public function redirect(string $slug)
    {
        try {
            $url = UrlShortener::where('slug', $slug)
                ->activos()
                ->noEliminados()
                ->noExpirados()
                ->firstOrFail();
            
            // Incrementar contador de clicks
            $url->incrementClicks();
            
            return redirect($url->url_original);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL no encontrada o expirada'
            ], 404);
        }
    }

    /**
     * Generar slug único
     */
    private function generateUniqueSlug(int $length = 6): string
    {
        do {
            $slug = Str::random($length);
        } while (UrlShortener::where('slug', $slug)->exists());
        
        return $slug;
    }

    /**
     * Obtener estadísticas de una URL
     */
    public function stats(string $id)
    {
        try {
            $url = UrlShortener::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $url->id,
                    'slug' => $url->slug,
                    'url_original' => $url->url_original,
                    'url_corta' => $url->url_corta,
                    'clicks' => $url->clicks,
                    'is_expired' => $url->isExpired(),
                    'is_available' => $url->isAvailable(),
                    'creado_en' => $url->creado_en,
                    'expira_en' => $url->expira_en,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
