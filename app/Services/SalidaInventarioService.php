<?php

namespace App\Services;

use App\DTOs\API\SalidaInventarioCreateDTO;
use App\Exceptions\InventarioException;
use App\Models\SalidaInventario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar Salidas de Inventario
 *
 * Encapsula la lógica de negocio para salidas de inventario
 * Fecha de creación: 12 de febrero de 2026
 * Refactorizado: 13 de febrero de 2026
 */
class SalidaInventarioService
{
    /**
     * Crear una nueva salida de inventario
     */
    public function crear(array $data): SalidaInventario
    {
        return DB::transaction(function () use ($data) {
            $data['estado'] = $data['estado'] ?? 'Pendiente';
            $data['monto_total'] = $data['monto_total'] ?? 0;

            $salida = SalidaInventario::create($data);

            if (!empty($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    $salida->detalles()->create($detalle);
                }
            }

            return $salida->load(['almacen', 'cliente', 'detalles.producto']);
        });
    }

    /**
     * Obtener salida por ID con relaciones
     */
    public function obtener(int $salidaId): ?SalidaInventario
    {
        return SalidaInventario::with(['almacen', 'cliente', 'proveedor', 'venta', 'detalles.producto'])
            ->find($salidaId);
    }

    /**
     * Listar salidas con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return SalidaInventario::with(['almacen', 'cliente', 'detalles.producto'])
            ->orderByDesc('fecha_salida')
            ->paginate($perPage);
    }

    /**
     * Salidas por cliente
     */
    public function porCliente(int $clienteId, int $perPage = 15): LengthAwarePaginator
    {
        return SalidaInventario::where('cliente_id', $clienteId)
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderByDesc('fecha_salida')
            ->paginate($perPage);
    }

    /**
     * Salidas por almacén
     */
    public function porAlmacen(int $almacenId, int $perPage = 15): LengthAwarePaginator
    {
        return SalidaInventario::where('almacen_id', $almacenId)
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderByDesc('fecha_salida')
            ->paginate($perPage);
    }

    /**
     * Salidas entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): LengthAwarePaginator
    {
        return SalidaInventario::whereBetween('fecha_salida', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderByDesc('fecha_salida')
            ->paginate($perPage);
    }

    /**
     * Actualizar salida
     */
    public function actualizar(SalidaInventario $salida, array $data): SalidaInventario
    {
        $salida->update($data);
        return $salida->fresh(['almacen', 'cliente', 'detalles.producto']);
    }

    /**
     * Eliminar salida
     */
    public function eliminar(SalidaInventario $salida): bool
    {
        return $salida->delete();
    }

    /**
     * Procesar salida y reducir stock
     */
    public function procesar(SalidaInventario $salida): SalidaInventario
    {
        if ($salida->estado === 'Procesada') {
            throw InventarioException::salidaYaProcesada();
        }

        $salida->load('detalles.producto');

        if ($salida->detalles->isEmpty()) {
            throw InventarioException::salidaSinProductos();
        }

        return DB::transaction(function () use ($salida) {
            foreach ($salida->detalles as $detalle) {
                $inventario = DB::table('inventarios')
                    ->where('producto_id', $detalle->producto_id)
                    ->where('almacen_id', $salida->almacen_id)
                    ->first();

                if (!$inventario || $inventario->cantidad_actual < $detalle->cantidad) {
                    throw InventarioException::stockInsuficiente($detalle->producto_id);
                }

                DB::table('inventarios')
                    ->where('id', $inventario->id)
                    ->decrement('cantidad_actual', (float) $detalle->cantidad);
            }

            $salida->update(['estado' => 'Procesada']);
            return $salida->fresh(['almacen', 'cliente', 'detalles.producto']);
        });
    }
}
