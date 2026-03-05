<?php

namespace App\Services;

use App\Models\OrdenCompra;
use App\Models\DetalleOrdenCompra;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * OrdenCompraService - Servicio de Gestión de Órdenes de Compra
 *
 * Encapsula la lógica de negocio para órdenes de compra:
 * - CRUD operations con transacciones
 * - Generación automática de número de orden
 * - Procesamiento de detalles y cálculo de totales
 * - Validaciones de estado para eliminación
 *
 * Refactorización FASE 8 - Service Layer Pattern
 */
class OrdenCompraService
{
    /**
     * Listar órdenes de compra con filtros opcionales
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = OrdenCompra::with(['empresa', 'proveedor', 'usuario']);

        if (!empty($filtros['empresa_id'])) {
            $query->porEmpresa($filtros['empresa_id']);
        }

        if (!empty($filtros['proveedor_id'])) {
            $query->porProveedor($filtros['proveedor_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['pendientes'])) {
            $query->pendientes();
        }

        if (!empty($filtros['activas'])) {
            $query->activas();
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Crear una nueva orden de compra con detalles
     *
     * Genera número de orden automáticamente y calcula totales.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $detalles
     * @return OrdenCompra
     */
    public function crear(array $data, array $detalles): OrdenCompra
    {
        return DB::transaction(function () use ($data, $detalles) {
            $data['numero_orden'] = $this->generarNumeroOrden($data['empresa_id']);

            $orden = OrdenCompra::create($data);

            $montoSubtotal = 0;
            $montoImpuestos = 0;

            foreach ($detalles as $detalle) {
                $cantidad = $detalle['cantidad'];
                $precioUnitario = $detalle['precio_unitario'];
                $descuento = $detalle['descuento'] ?? 0;

                $subtotal = ($cantidad * $precioUnitario) - $descuento;
                $montoSubtotal += $subtotal;

                DetalleOrdenCompra::create([
                    'orden_compra_id' => $orden->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                    'descripcion' => $detalle['descripcion'] ?? null,
                ]);
            }

            $orden->update([
                'subtotal' => $montoSubtotal,
                'impuesto_total' => $montoImpuestos,
                'total_orden' => $montoSubtotal + $montoImpuestos,
            ]);

            return $orden->load(['proveedor', 'detalles.producto']);
        });
    }

    /**
     * Obtener orden de compra por ID con relaciones completas
     *
     * @param int $id
     * @return OrdenCompra
     */
    public function obtener(int $id): OrdenCompra
    {
        $orden = OrdenCompra::with([
            'empresa',
            'proveedor',
            'usuario',
            'detalles.producto',
            'pagos',
            'entradasInventario',
        ])->findOrFail($id);

        $orden->saldo_pendiente = $orden->calcularSaldoPendiente();

        return $orden;
    }

    /**
     * Actualizar una orden de compra existente
     *
     * @param OrdenCompra $orden
     * @param array<string, mixed> $data
     * @return OrdenCompra
     */
    public function actualizar(OrdenCompra $orden, array $data): OrdenCompra
    {
        $orden->update($data);

        return $orden->load(['proveedor', 'detalles.producto', 'empresa']);
    }

    /**
     * Eliminar una orden de compra (soft delete)
     *
     * Solo permite eliminar órdenes en estado 'borrador'.
     *
     * @param OrdenCompra $orden
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(OrdenCompra $orden): bool
    {
        if ($orden->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'orden' => 'Solo se pueden eliminar órdenes en estado borrador',
            ]);
        }

        return DB::transaction(function () use ($orden) {
            $orden->detalles()->delete();

            $orden->update([
                'activo' => false,
                'eliminado' => true,
            ]);

            return true;
        });
    }

    /**
     * Generar número de orden único por empresa
     *
     * @param int $empresaId
     * @return string
     */
    private function generarNumeroOrden(int $empresaId): string
    {
        $ultimaOrden = OrdenCompra::where('empresa_id', $empresaId)
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimaOrden ? (int) substr($ultimaOrden->numero_orden, -6) + 1 : 1;

        return 'OC-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }
}
