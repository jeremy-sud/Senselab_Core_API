<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConfiguracionRequest;
use App\Http\Requests\UpdateConfiguracionRequest;
use App\Http\Requests\ActualizarMultiplesConfiguracionesRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Configuracion;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador para Configuraciones del Sistema
 *
 * Gestiona configuraciones clave-valor por empresa (moneda, idioma, tasas, etc.).
 *
 * @package App\Http\Controllers\API
 * @author Senselab - Jeremy Arias Solano
 */
class ConfiguracionController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /** @var array<string> */
    /** @var array<int, string> */
    protected array $cacheTags = ['configuraciones', 'settings'];
    protected int $cacheTTL = 7200; // 2 horas (cambia poco)
    /**
     * Listar configuraciones de la empresa
     */
    #[OA\Get(
        path: '/api/configuraciones',
        summary: 'Listar configuraciones',
        description: 'Obtiene todas las configuraciones de la empresa autenticada',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Configuracion'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Configuracion::class);
        $empresaId = $this->getEmpresaId();

        $configuraciones = $this->cacheQueryIfEnabled(
            $this->getCacheKey('index', ['empresa_id' => $empresaId]),
            function() use ($empresaId) {
                return Configuracion::where('empresa_id', $empresaId)
                    ->orderBy('clave', 'asc')
                    ->get();
            }
        );

        return ConfiguracionResource::collection($configuraciones)
            ->additional(['success' => true]);
    }

    /**
     * Crear nueva configuración
     */
    #[OA\Post(
        path: '/api/configuraciones',
        summary: 'Crear configuración',
        description: 'Crea nueva configuración clave-valor',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['clave', 'valor', 'tipo_dato'],
                properties: [
                    new OA\Property(property: 'clave', type: 'string', example: 'moneda_default'),
                    new OA\Property(property: 'valor', type: 'string', example: 'CRC'),
                    new OA\Property(property: 'tipo_dato', type: 'string', enum: ['string', 'integer', 'float', 'boolean', 'json', 'array'], example: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Configuración creada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Configuracion')]))
        ]
    )]
    public function store(StoreConfiguracionRequest $request): ConfiguracionResource
    {
        $this->authorize('create', Configuracion::class);
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::create([
            'empresa_id' => $empresaId,
            'clave' => $request->clave,
            'valor' => $request->valor,
            'tipo_dato' => $request->tipo_dato,
            'descripcion' => $request->descripcion
        ]);

        $this->flushCache();

        return (new ConfiguracionResource($configuracion))
            ->additional([
                'success' => true,
                'message' => 'Configuración creada exitosamente'
            ]);
    }

    /**
     * Mostrar configuración específica
     */
    #[OA\Get(
        path: '/api/configuraciones/{id}',
        summary: 'Obtener configuración',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(Request $request, int $id): ConfiguracionResource
    {
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('view', $configuracion);

        return (new ConfiguracionResource($configuracion))
            ->additional(['success' => true]);
    }

    /**
     * Actualizar configuración
     */
    #[OA\Put(
        path: '/api/configuraciones/{id}',
        summary: 'Actualizar configuración',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateConfiguracionRequest $request, int $id): ConfiguracionResource
    {
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('update', $configuracion);

        $configuracion->update($request->only([
            'clave',
            'valor',
            'tipo_dato',
            'descripcion'
        ]));

        $this->flushCache();

        return (new ConfiguracionResource($configuracion))
            ->additional([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente'
            ]);
    }

    /**
     * Eliminar configuración
     */
    #[OA\Delete(
        path: '/api/configuraciones/{id}',
        summary: 'Eliminar configuración',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Eliminada')]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('delete', $configuracion);

        $configuracion->delete();

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Configuración eliminada exitosamente'
        ]);
    }

    /**
     * Obtener configuración por clave
     */
    #[OA\Get(
        path: '/api/configuraciones/clave/{clave}',
        summary: 'Buscar por clave',
        description: 'Obtiene configuración por su clave única',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        parameters: [new OA\Parameter(name: 'clave', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'moneda_default'))],
        responses: [
            new OA\Response(response: 200, description: 'Encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function porClave(Request $request, string $clave): ConfiguracionResource|JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->first();

        if (!$configuracion) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        return (new ConfiguracionResource($configuracion))
            ->additional(['success' => true]);
    }

    /**
     * Obtener valor de configuración por clave
     */
    #[OA\Get(
        path: '/api/configuraciones/valor/{clave}',
        summary: 'Obtener solo valor',
        description: 'Retorna el valor convertido según tipo_dato',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        parameters: [new OA\Parameter(name: 'clave', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'moneda_default'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Valor obtenido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'clave', type: 'string', example: 'moneda_default'),
                        new OA\Property(property: 'valor', example: 'CRC'),
                        new OA\Property(property: 'tipo_dato', type: 'string', example: 'string')
                    ]
                )
            )
        ]
    )]
    public function obtenerValor(Request $request, string $clave): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $configuracion = Configuracion::where('empresa_id', $empresaId)
            ->where('clave', $clave)
            ->first();

        if (!$configuracion) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada',
                'valor' => null
            ], 404);
        }

        // Convertir valor según tipo_dato
        $valor = $configuracion->valor;
        switch ($configuracion->tipo_dato) {
            case 'numero':
                $valor = is_numeric($valor) ? (float) $valor : $valor;
                break;
            case 'booleano':
                $valor = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'json':
                $valor = json_decode($valor, true);
                break;
        }

        return response()->json([
            'success' => true,
            'clave' => $configuracion->clave,
            'valor' => $valor,
            'tipo_dato' => $configuracion->tipo_dato
        ]);
    }

    /**
     * Actualizar múltiples configuraciones
     */
    #[OA\Post(
        path: '/api/configuraciones/actualizar-multiples',
        summary: 'Actualizar en lote',
        description: 'Actualiza múltiples configuraciones en una sola petición',
        security: [['sanctum' => []]],
        tags: ['Configuraciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['configuraciones'],
                properties: [
                    new OA\Property(
                        property: 'configuraciones',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'clave', type: 'string'),
                                new OA\Property(property: 'valor', type: 'string')
                            ],
                            type: 'object'
                        ),
                        example: [
                            ['clave' => 'moneda_default', 'valor' => 'USD'],
                            ['clave' => 'idioma', 'valor' => 'es']
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Configuraciones actualizadas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'total_actualizadas', type: 'integer', example: 2)
                    ]
                )
            )
        ]
    )]
    public function actualizarMultiples(ActualizarMultiplesConfiguracionesRequest $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();
        $actualizadas = 0;

        foreach ($request->configuraciones as $config) {
            Configuracion::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'clave' => $config['clave'],
                ],
                [
                    'valor' => $config['valor'],
                    'tipo_dato' => 'string'
                ]
            );
            $actualizadas++;
        }

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => "Se actualizaron e inicializaron {$actualizadas} configuraciones exitosamente",
            'total_actualizadas' => $actualizadas
        ]);
    }
}
