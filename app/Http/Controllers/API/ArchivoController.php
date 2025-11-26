<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;
use App\Http\Resources\ArchivoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class ArchivoController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['archivos', 'documentos'];
    protected $cacheTTL = 1800; // 30 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/archivos',
        summary: 'Listar archivos',
        description: 'Obtiene un listado paginado de archivos adjuntos',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'entidad_tipo',
                description: 'Filtrar por tipo de entidad',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'entidad_id',
                description: 'Filtrar por ID de entidad',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'categoria',
                description: 'Filtrar por categoría',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Archivo::class);

        $cacheKey = $this->generateCacheKey('archivos.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = Archivo::with(['empresa', 'usuario'])
                ->activos()
                ->noEliminados();

            if ($request->filled('entidad_tipo')) {
                $query->entidadTipo($request->entidad_tipo);
            }

            if ($request->filled('entidad_id')) {
                $query->where('entidad_id', $request->entidad_id);
            }

            if ($request->filled('categoria')) {
                $query->categoria($request->categoria);
            }

            $archivos = $query->orderBy('id', 'desc')->paginate($perPage);

            return ArchivoResource::collection($archivos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/archivos',
        summary: 'Subir archivo',
        description: 'Sube un nuevo archivo y lo asocia a una entidad',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['archivo', 'entidad_tipo', 'entidad_id'],
                    properties: [
                        new OA\Property(property: 'archivo', type: 'string', format: 'binary'),
                        new OA\Property(property: 'entidad_tipo', type: 'string', example: 'App\\Models\\Producto'),
                        new OA\Property(property: 'entidad_id', type: 'integer', example: 1),
                        new OA\Property(property: 'categoria', type: 'string', example: 'imagen'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Archivo subido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): ArchivoResource|JsonResponse
    {
        $this->authorize('create', Archivo::class);

        $validated = $request->validate([
            'archivo' => 'required|file|max:10240', // 10MB
            'entidad_tipo' => 'required|string',
            'entidad_id' => 'required|integer',
            'categoria' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('archivo');
            
            // Generar nombre único
            $nombreOriginal = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $nombreAlmacenado = uniqid() . '_' . time() . '.' . $extension;
            
            // Almacenar archivo
            $ruta = $file->storeAs('archivos', $nombreAlmacenado, 'private');
            
            // Calcular hash
            $hashSha256 = hash_file('sha256', $file->getRealPath());
            
            $archivo = Archivo::create([
                'empresa_id' => auth('sanctum')->user()->empresa_id,
                'usuario_id' => auth('sanctum')->id(),
                'entidad_tipo' => $validated['entidad_tipo'],
                'entidad_id' => $validated['entidad_id'],
                'nombre_original' => $nombreOriginal,
                'nombre_almacenado' => $nombreAlmacenado,
                'ruta' => $ruta,
                'tipo_mime' => $file->getMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $file->getSize(),
                'categoria' => $validated['categoria'] ?? 'general',
                'hash_sha256' => $hashSha256,
            ]);

            DB::commit();
            $this->clearCache();

            return (new ArchivoResource($archivo->load(['empresa', 'usuario'])))
                ->additional(['message' => 'Archivo subido exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al subir archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/archivos/{id}',
        summary: 'Obtener archivo específico',
        description: 'Obtiene los metadatos de un archivo',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del archivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id): ArchivoResource
    {
        $archivo = Archivo::with(['empresa', 'usuario'])->findOrFail($id);
        $this->authorize('view', $archivo);

        $cacheKey = $this->generateCacheKey("archivos.show.{$id}");

        return $this->getCached($cacheKey, function () use ($archivo) {
            return new ArchivoResource($archivo);
        });
    }

    /**
     * Download the specified resource.
     */
    #[OA\Get(
        path: '/api/archivos/{id}/descargar',
        summary: 'Descargar archivo',
        description: 'Descarga el archivo físico',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del archivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo descargado',
                content: new OA\MediaType(mediaType: 'application/octet-stream')
            )
        ]
    )]
    public function descargar(string $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $archivo = Archivo::findOrFail($id);
        $this->authorize('view', $archivo);

        if (!Storage::disk('private')->exists($archivo->ruta)) {
            return response()->json([
                'message' => 'Archivo no encontrado en el almacenamiento'
            ], 404);
        }

        $rutaCompleta = Storage::disk('private')->path($archivo->ruta);
        return response()->download($rutaCompleta, $archivo->nombre_original);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/archivos/{id}',
        summary: 'Actualizar metadatos de archivo',
        description: 'Actualiza la categoría u otros metadatos',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del archivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'categoria', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): ArchivoResource|JsonResponse
    {
        $archivo = Archivo::findOrFail($id);
        $this->authorize('update', $archivo);

        $validated = $request->validate([
            'categoria' => 'sometimes|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $archivo->update($validated);

            DB::commit();
            $this->clearCache();

            return (new ArchivoResource($archivo->fresh(['empresa', 'usuario'])))
                ->additional(['message' => 'Archivo actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/archivos/{id}',
        summary: 'Eliminar archivo',
        description: 'Elimina el archivo del sistema',
        security: [['sanctum' => []]],
        tags: ['Archivos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del archivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo eliminado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $archivo = Archivo::findOrFail($id);
        $this->authorize('delete', $archivo);

        DB::beginTransaction();
        try {
            // Eliminar archivo físico
            if (Storage::disk('private')->exists($archivo->ruta)) {
                Storage::disk('private')->delete($archivo->ruta);
            }

            // Soft delete del registro
            $archivo->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
