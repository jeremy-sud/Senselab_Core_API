<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\CabysClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para clasificación automática de códigos CABYS mediante IA
 *
 * @group AI - Clasificación CABYS
 */
class CabysController extends Controller
{
    public function __construct(
        private CabysClassifierService $cabysService
    ) {}

    /**
     * Clasificar producto
     */
    public function classifyProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|min:3|max:500',
            'category_hint' => 'nullable|string|max:100',
            'max_suggestions' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $producto = [
                'nombre' => $request->input('description'),
                'descripcion' => $request->input('description'),
                'categoria' => $request->input('category_hint', ''),
            ];

            $result = $this->cabysService->classifyProduct($producto);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al clasificar producto',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Clasificar múltiples productos
     */
    public function batchClassify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array|min:1|max:50',
            'products.*.description' => 'required|string|min:3|max:500',
            'products.*.id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Transformar productos al formato esperado por el servicio
            $productos = array_map(function ($p) {
                return [
                    'id' => $p['id'] ?? null,
                    'nombre' => $p['description'],
                    'descripcion' => $p['description'],
                ];
            }, $request->input('products'));

            $result = $this->cabysService->classifyBatch($productos);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al clasificar productos',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Buscar códigos CABYS
     */
    public function searchByDescription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:200',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->cabysService->searchCabys(
                $request->input('query'),
                $request->input('limit', 10)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar códigos CABYS',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Validar código CABYS
     */
    public function validateCode(string $code): JsonResponse
    {
        if (strlen($code) < 8 || strlen($code) > 13) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de código CABYS inválido',
                'data' => [
                    'valid' => false,
                    'code' => $code,
                ],
            ], 422);
        }

        try {
            $result = $this->cabysService->validateCabys($code);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar código CABYS',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Obtener sugerencias para producto existente
     */
    public function suggestForProduct(Request $request, int $productoId): JsonResponse
    {
        try {
            $empresaId = $request->user()->empresa_id;

            $producto = DB::table('productos')
                ->where('id', $productoId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                ], 404);
            }

            $result = $this->cabysService->classifyProduct([
                'nombre' => $producto->nombre ?? 'Producto',
                'descripcion' => $producto->descripcion ?? '',
                'categoria' => $producto->categoria ?? '',
                'id' => $producto->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => array_merge($result, [
                    'current_code' => $producto->codigo_cabys ?? null,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sugerencias para producto',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Obtener categorías principales CABYS
     */
    public function getCategories(): JsonResponse
    {
        try {
            $result = $this->cabysService->getMainCategories();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
