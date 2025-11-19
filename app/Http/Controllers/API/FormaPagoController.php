<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormaPagoRequest;
use App\Http\Requests\UpdateFormaPagoRequest;
use App\Http\Resources\FormaPagoResource;
use App\Models\FormaPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de formas de pago
 * 
 * Gestiona métodos de pago (Efectivo, Tarjeta, Transferencia, SINPE, etc.)
 * Nota: Tabla global sin empresa_id según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class FormaPagoController extends Controller
{
    /**
     * Listar todas las formas de pago
     * 
     * GET /api/formas-pago
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FormaPago::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $formasPago = $query->get();

        return FormaPagoResource::collection($formasPago);
    }

    /**
     * Crear una nueva forma de pago
     * 
     * POST /api/formas-pago
     */
    public function store(StoreFormaPagoRequest $request): JsonResponse
    {
        $formaPago = FormaPago::create($request->validated());

        return (new FormaPagoResource($formaPago))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una forma de pago específica
     * 
     * GET /api/formas-pago/{id}
     */
    public function show(int $id): FormaPagoResource
    {
        $formaPago = FormaPago::findOrFail($id);

        return new FormaPagoResource($formaPago);
    }

    /**
     * Actualizar una forma de pago existente
     * 
     * PUT/PATCH /api/formas-pago/{id}
     */
    public function update(UpdateFormaPagoRequest $request, int $id): FormaPagoResource
    {
        $formaPago = FormaPago::findOrFail($id);
        $formaPago->update($request->validated());

        return new FormaPagoResource($formaPago);
    }

    /**
     * Eliminar una forma de pago (soft delete)
     * 
     * DELETE /api/formas-pago/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $formaPago = FormaPago::findOrFail($id);

        $formaPago->eliminado = 1;
        $formaPago->activo = 0;
        $formaPago->save();

        return response()->json([
            'message' => 'Forma de pago eliminada exitosamente',
            'data' => new FormaPagoResource($formaPago)
        ], 200);
    }
}
