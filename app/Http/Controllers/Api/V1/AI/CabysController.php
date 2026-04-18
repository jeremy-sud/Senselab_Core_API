<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\CabysClassifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * Controller para clasificación automática de códigos CABYS mediante IA
 *
 * @OA\Tag(
 *     name="AI - Clasificación CABYS",
 *     description="Endpoints para clasificación automática de códigos CABYS (Catálogo de Bienes y Servicios de Costa Rica) mediante IA"
 * )
 */
class CabysController extends Controller
{
    public function __construct(
        private CabysClassifierService $cabysService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/ai/cabys/classify",
     *     summary="Clasificar producto en CABYS",
     *     description="Clasifica un producto según la descripción y sugiere códigos CABYS usando IA",
     *     operationId="classifyProduct",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description"},
     *             @OA\Property(property="description", type="string", minLength=3, maxLength=500, example="Arroz blanco grano largo"),
     *             @OA\Property(property="category_hint", type="string", maxLength=100, example="Alimentos"),
     *             @OA\Property(property="max_suggestions", type="integer", minimum=1, maximum=10, example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto clasificado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="codigo", type="string", example="1010101000100"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="confianza", type="number", format="float", example=0.95)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/ai/cabys/batch",
     *     summary="Clasificar múltiples productos",
     *     description="Clasifica un lote de hasta 50 productos en códigos CABYS",
     *     operationId="batchClassifyCabys",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"products"},
     *             @OA\Property(property="products", type="array", minItems=1, maxItems=50,
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="description", type="string", minLength=3, maxLength=500),
     *                     @OA\Property(property="id", type="string", maxLength=50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Productos clasificados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/ai/cabys/search",
     *     summary="Buscar códigos CABYS",
     *     description="Busca códigos CABYS por descripción textual",
     *     operationId="searchCabys",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Término de búsqueda",
     *         @OA\Schema(type="string", minLength=2, maxLength=200)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Número máximo de resultados",
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultados de búsqueda CABYS",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/ai/cabys/validate/{code}",
     *     summary="Validar código CABYS",
     *     description="Verifica si un código CABYS es válido y retorna su información",
     *     operationId="validateCabysCode",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Código CABYS a validar (8-13 dígitos)",
     *         @OA\Schema(type="string", example="1010101000100")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Código validado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="valid", type="boolean"),
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Formato de código CABYS inválido"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/ai/cabys/suggest/{productoId}",
     *     summary="Sugerencias CABYS para producto existente",
     *     description="Obtiene sugerencias de códigos CABYS para un producto registrado en el sistema",
     *     operationId="suggestCabysForProduct",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="productoId",
     *         in="path",
     *         required=true,
     *         description="ID del producto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sugerencias generadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_code", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/ai/cabys/categories",
     *     summary="Categorías principales CABYS",
     *     description="Obtiene las categorías principales del catálogo CABYS",
     *     operationId="getCabysCategories",
     *     tags={"AI - Clasificación CABYS"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Categorías obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
