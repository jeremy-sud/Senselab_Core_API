<?php

namespace App\Http\Controllers;

use App\Models\ConsecutivoFe;
use App\Http\Requests\StoreConsecutivoFeRequest;
use App\Http\Requests\UpdateConsecutivoFeRequest;
use App\Http\Resources\ConsecutivoFeResource;
use App\Services\ConsecutivoFeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ObtenerSiguienteConsecutivoRequest;
use App\Http\Requests\ResetearConsecutivoRequest;
use App\Traits\HasEmpresaContext;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Consecutivos FE',
    description: 'Gestión de consecutivos de facturación electrónica por tipo de documento'
)]
class ConsecutivoFeController extends Controller
{
    use HasEmpresaContext;

    public function __construct(
        private readonly ConsecutivoFeService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
        #[OA\Get(
        path: '/api/consecutivo-fe',
        summary: 'Listar consecutivos FE',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $filtros = [
            'empresa_id' => $this->getEmpresaId(),
            ...$request->only(['sucursal_id', 'tipo_documento_dgt', 'estado']),
        ];

        $consecutivos = $this->service->listar($filtros, (int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ConsecutivoFeResource::collection($consecutivos),
            'meta' => [
                'current_page' => $consecutivos->currentPage(),
                'last_page' => $consecutivos->lastPage(),
                'per_page' => $consecutivos->perPage(),
                'total' => $consecutivos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
        #[OA\Post(
        path: '/api/consecutivo-fe',
        summary: 'Crear consecutivo FE',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(StoreConsecutivoFeRequest $request): JsonResponse
    {
        $data = [
            'empresa_id' => $this->getEmpresaId(),
            'sucursal_id' => $request->sucursal_id,
            'tipo_documento_dgt' => $request->tipo_documento_dgt,
            'prefijo' => $request->prefijo,
            'consecutivo_actual' => $request->consecutivo_actual ?? 1,
            'estado' => $request->estado ?? 'Activo',
            'fecha_autorizacion' => $request->fecha_autorizacion,
        ];

        $consecutivo = $this->service->crear($data);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE creado exitosamente',
            'data' => new ConsecutivoFeResource($consecutivo)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
        #[OA\Get(
        path: '/api/consecutivo-fe/{id}',
        summary: 'Obtener consecutivo FE',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ConsecutivoFeResource($consecutivoFe)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
        #[OA\Put(
        path: '/api/consecutivo-fe/{id}',
        summary: 'Actualizar consecutivo FE',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function update(UpdateConsecutivoFeRequest $request, ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivo = $this->service->actualizar($consecutivoFe, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE actualizado exitosamente',
            'data' => new ConsecutivoFeResource($consecutivo)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
        #[OA\Delete(
        path: '/api/consecutivo-fe/{id}',
        summary: 'Eliminar consecutivo FE',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $this->service->eliminar($consecutivoFe);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo FE eliminado exitosamente'
        ]);
    }

    /**
     * Obtiene el siguiente consecutivo disponible de forma thread-safe.
     */
    public function obtenerSiguiente(ObtenerSiguienteConsecutivoRequest $request): JsonResponse
    {
        try {
            $params = $request->only(['tipo_documento_dgt', 'sucursal_id', 'prefijo']);
            $resultado = $this->service->obtenerSiguiente($this->getEmpresaId(), $params);

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el consecutivo',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Resetear el consecutivo a un número específico (solo admin).
     */
    public function resetear(ResetearConsecutivoRequest $request, ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivo = $this->service->resetear($consecutivoFe, $request->nuevo_consecutivo);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo reseteado exitosamente',
            'data' => new ConsecutivoFeResource($consecutivo)
        ]);
    }

    /**
     * Listar consecutivos por tipo de documento.
     */
    public function porTipoDocumento(Request $request, string $tipoDocumentoDgt): JsonResponse
    {
        $consecutivos = $this->service->porTipoDocumento($this->getEmpresaId(), $tipoDocumentoDgt);

        return response()->json([
            'success' => true,
            'data' => ConsecutivoFeResource::collection($consecutivos)
        ]);
    }

    /**
     * Marcar un consecutivo como agotado.
     */
    public function marcarAgotado(ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivo = $this->service->marcarAgotado($consecutivoFe);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo marcado como agotado',
            'data' => new ConsecutivoFeResource($consecutivo)
        ]);
    }

    /**
     * Activar un consecutivo inactivo.
     */
    public function activar(ConsecutivoFe $consecutivoFe): JsonResponse
    {
        if ($consecutivoFe->empresa_id !== $this->getEmpresaId()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $consecutivo = $this->service->activar($consecutivoFe);

        return response()->json([
            'success' => true,
            'message' => 'Consecutivo activado exitosamente',
            'data' => new ConsecutivoFeResource($consecutivo)
        ]);
    }

    /**
     * Resumen de consecutivos por estado.
     */
    public function resumenPorEstado(): JsonResponse
    {
        $resumen = $this->service->resumenPorEstado($this->getEmpresaId());

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
