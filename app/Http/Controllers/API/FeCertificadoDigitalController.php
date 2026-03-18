<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeCertificadoDigitalResource;
use App\Http\Requests\StoreFeCertificadoDigitalRequest;
use App\Http\Requests\UpdateFeCertificadoDigitalRequest;
use App\Models\FeCertificadoDigital;
use App\Services\FeCertificadoDigitalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Certificados Digitales FE',
    description: 'Gestión de certificados digitales para firma de comprobantes electrónicos'
)]
class FeCertificadoDigitalController extends Controller
{
    public function __construct(
        private readonly FeCertificadoDigitalService $service
    ) {}
    #[OA\Get(
        path: '/api/fe-certificados-digitales',
        summary: 'Listar certificados digitales',
        description: 'Obtener listado de certificados digitales de la empresa con filtros por estado, ambiente y vigencia',
        security: [['sanctum' => []]],
        tags: ['Certificados Digitales FE'],
        parameters: [
            new OA\Parameter(
                name: 'activo',
                in: 'query',
                description: 'Filtrar por estado activo',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'ambiente',
                in: 'query',
                description: 'Ambiente (produccion/stg)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['produccion', 'stg'])
            ),
            new OA\Parameter(
                name: 'solo_vigentes',
                in: 'query',
                description: 'Solo certificados vigentes',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'dias_vencimiento',
                in: 'query',
                description: 'Certificados que vencen en X días',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de certificados',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nombre', type: 'string', example: 'Certificado Producción 2024'),
                                    new OA\Property(property: 'ambiente', type: 'string', example: 'produccion'),
                                    new OA\Property(property: 'activo', type: 'boolean', example: true),
                                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date'),
                                    new OA\Property(property: 'pin', type: 'string', example: '****', description: 'PIN encriptado/oculto'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = $request->user();

        $filtros = [
            'empresa_id' => $user->empresa_id,
            ...$request->only(['activo', 'ambiente', 'solo_vigentes', 'dias_vencimiento']),
        ];

        $certificados = $this->service->listarTodos($filtros);

        return response()->json([
            'data' => FeCertificadoDigitalResource::collection($certificados),
        ]);
    }

    #[OA\Post(
        path: '/api/fe-certificados-digitales',
        summary: 'Crear certificado digital',
        description: 'Subir y registrar certificado digital .p12 para firma de comprobantes electrónicos',
        security: [['sanctum' => []]],
        tags: ['Certificados Digitales FE'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nombre', 'pin', 'ambiente', 'fecha_vencimiento', 'archivo_certificado'],
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', example: 'Certificado Producción 2024'),
                        new OA\Property(property: 'pin', type: 'string', example: '1234', description: 'PIN del certificado (se encripta)'),
                        new OA\Property(property: 'ambiente', type: 'string', enum: ['produccion', 'stg'], example: 'produccion'),
                        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'activo', type: 'boolean', example: true, description: 'Marcar como activo (desactiva otros)'),
                        new OA\Property(property: 'archivo_certificado', type: 'string', format: 'binary', description: 'Archivo .p12 del certificado'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Certificado creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Certificado digital creado exitosamente'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ]
    )]
    public function store(StoreFeCertificadoDigitalRequest $request): JsonResponse
    {
        $this->authorize('create', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = $request->user();

        $data = $request->validated();

        // Manejar subida del archivo .p12
        if ($request->hasFile('archivo_certificado')) {
            $file = $request->file('archivo_certificado');
            $nombreUnico = Str::uuid() . '.p12';

            $ruta = $file->storeAs(
                'certificados_fe/' . $user->empresa_id,
                $nombreUnico,
                'private'
            );

            $data['ruta_archivo'] = $ruta;
        }

        $data['empresa_id'] = $user->empresa_id;

        $certificado = $this->service->crear($data);

        return response()->json([
            'message' => 'Certificado digital creado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('view', $certificado);

        return response()->json([
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeCertificadoDigitalRequest $request, FeCertificadoDigital $feCertificadoDigital): JsonResponse
    {
        $this->authorize('update', $feCertificadoDigital);

        $certificado = $this->service->actualizar($feCertificadoDigital, $request->validated());

        return response()->json([
            'message' => 'Certificado digital actualizado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('delete', $certificado);

        $this->service->eliminar($certificado);

        return response()->json([
            'message' => 'Certificado digital eliminado exitosamente',
        ]);
    }

    #[OA\Post(
        path: '/api/fe-certificados-digitales/{id}/activar',
        summary: 'Activar certificado digital',
        description: 'Activar certificado y desactivar otros del mismo ambiente. No se puede activar un certificado vencido.',
        security: [['sanctum' => []]],
        tags: ['Certificados Digitales FE'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del certificado',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificado activado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Certificado activado exitosamente'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Certificado vencido'),
        ]
    )]
    public function activar(FeCertificadoDigital $certificado, Request $request): JsonResponse
    {
        $this->authorize('activar', $certificado);

        /** @var \App\Models\Usuario $user */
        $user = $request->user();

        try {
            $certificado = $this->service->activar($certificado, $user->empresa_id);
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Certificado activado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Deactivate a certificate.
     */
    public function desactivar(FeCertificadoDigital $certificado): JsonResponse
    {
        $this->authorize('update', $certificado);

        $this->service->desactivar($certificado);

        return response()->json([
            'message' => 'Certificado desactivado exitosamente',
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    #[OA\Get(
        path: '/api/fe-certificados-digitales/activo',
        summary: 'Obtener certificado activo',
        description: 'Obtener el certificado activo para un ambiente específico (produccion/stg)',
        security: [['sanctum' => []]],
        tags: ['Certificados Digitales FE'],
        parameters: [
            new OA\Parameter(
                name: 'ambiente',
                in: 'query',
                description: 'Ambiente del certificado',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['produccion', 'stg'], default: 'produccion')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Certificado activo encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'No hay certificado activo para el ambiente'),
        ]
    )]
    public function activo(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = $request->user();

        $ambiente = $request->get('ambiente', 'produccion');

        $certificado = $this->service->obtenerActivo($user->empresa_id, $ambiente);

        if (!$certificado) {
            return response()->json([
                'message' => 'No hay certificado activo para el ambiente ' . $ambiente,
            ], 404);
        }

        return response()->json([
            'data' => new FeCertificadoDigitalResource($certificado),
        ]);
    }

    /**
     * Get certificates expiring soon.
     */
    public function proximosVencer(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeCertificadoDigital::class);

        /** @var \App\Models\Usuario $user */
        $user = $request->user();

        $dias = (int) $request->get('dias', 30);

        $certificados = $this->service->proximosVencer($user->empresa_id, $dias);

        return response()->json([
            'data' => FeCertificadoDigitalResource::collection($certificados),
        ]);
    }
}
