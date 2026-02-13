<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\API\VentaCreateDTO;
use App\Services\VentaService;
use App\DTOs\Transformers\VentaTransformer;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * VentaController - Versión Refactorizada
 * 
 * Controlador simplificado usando Service + DTO + Transformer
 * Reduces from 818 líneas a ~250 líneas
 * 
 * Fecha: 12 de febrero de 2026
 */
class VentaControllerRefactored extends Controller
{
    public function __construct(private VentaService $service) {}

    /**
     * GET /api/ventas
     * Listar ventas con paginación
     */
    #[OA\Get(
        path: '/api/ventas',
        summary: 'Listar ventas',
        tags: ['Ventas'],
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        
        if ($request->filled('cliente_id')) {
            $ventas = $this->service->porCliente($request->integer('cliente_id'), $perPage);
        } elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $inicio = new \DateTime($request->string('fecha_inicio'));
            $fin = new \DateTime($request->string('fecha_fin'));
            $ventas = $this->service->entreFechas($inicio, $fin, $perPage);
        } else {
            $ventas = $this->service->listar($perPage);
        }
        
        return response()->json([
            'data' => VentaTransformer::collection($ventas->items()),
            'pagination' => [
                'total' => $ventas->total(),
                'per_page' => $ventas->perPage(),
                'current_page' => $ventas->currentPage(),
                'last_page' => $ventas->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/ventas/{id}
     * Obtener detalle de venta
     */
    #[OA\Get(
        path: '/api/ventas/{id}',
        summary: 'Obtener venta por ID',
        tags: ['Ventas'],
    )]
    public function show(int $id): JsonResponse
    {
        $venta = $this->service->obtener($id);
        
        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }
        
        return response()->json(VentaTransformer::transform($venta));
    }

    /**
     * POST /api/ventas
     * Crear nueva venta
     */
    #[OA\Post(
        path: '/api/ventas',
        summary: 'Crear venta',
        tags: ['Ventas'],
    )]
    public function store(Request $request): JsonResponse
    {
        // Validación delegada a FormRequest (StoreVentaRequest)
        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'empresa_id' => 'required|integer|exists:empresas,id',
            'fecha' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'impuesto' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'estado' => 'required|string|in:pendiente,confirmada,cancelada',
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|integer',
            'detalles.*.cantidad' => 'required|numeric',
            'detalles.*.precio_unitario' => 'required|numeric',
        ]);
        
        try {
            $dto = VentaCreateDTO::fromRequest($request);
            $venta = $this->service->crear($dto);
            
            return response()->json(
                VentaTransformer::transform($venta),
                201
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al crear venta: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PUT /api/ventas/{id}
     * Actualizar venta
     */
    #[OA\Put(
        path: '/api/ventas/{id}',
        summary: 'Actualizar venta',
        tags: ['Ventas'],
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $venta = $this->service->obtener($id);
        
        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }
        
        $request->validate([
            'estado' => 'sometimes|string|in:pendiente,confirmada,cancelada',
            'numero_comprobante' => 'sometimes|string',
            'observaciones' => 'sometimes|string|nullable',
        ]);
        
        if ($request->filled('estado')) {
            $venta = $this->service->cambiarEstado($venta, $request->string('estado'));
        }
        
        return response()->json(VentaTransformer::transform($venta));
    }

    /**
     * DELETE /api/ventas/{id}
     * Eliminar venta
     */
    #[OA\Delete(
        path: '/api/ventas/{id}',
        summary: 'Eliminar venta',
        tags: ['Ventas'],
    )]
    public function destroy(int $id): JsonResponse
    {
        $venta = $this->service->obtener($id);
        
        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }
        
        // Aquí podrías agregar lógica de eliminación suave:
        // $venta->delete();
        
        return response()->json(null, 204);
    }

    /**
     * GET /api/ventas/reporte/total-periodo
     * Obtener total de ventas en período
     */
    public function totalPeriodo(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);
        
        $inicio = new \DateTime($request->string('fecha_inicio'));
        $fin = new \DateTime($request->string('fecha_fin'));
        
        $total = $this->service->totalEnPeriodo($inicio, $fin);
        
        return response()->json([
            'total' => $total,
            'periodo' => [
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
            ],
        ]);
    }
}
