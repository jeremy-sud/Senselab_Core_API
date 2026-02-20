<?php

namespace App\Services;

use App\DTOs\API\EntradaInventarioCreateDTO;
use App\Models\EntradaInventario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar Entradas de Inventario
 *
 * Encapsula la lógica de negocio para entradas de inventario
 * Fecha de creación: 12 de febrero de 2026
 * Refactorizado: 13 de febrero de 2026
 */
class EntradaInventarioService
{
    /**
     * Crear una nueva entrada de inventario
     */
    public function crear(array $data): EntradaInventario
    {
        return DB::transaction(function () use ($data) {
            $data['usuario_id'] = auth('sanctum')->id();
            $data['estado'] = $data['estado'] ?? 'pendiente';

            $entrada = EntradaInventario::create($data);

            if (!empty($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    $entrada->detalles()->create($detalle);
                }
            }

            return $entrada->load(['proveedor', 'bodega', 'usuario', 'detalles.producto']);
        });
    }

    /**
     * Obtener entrada por ID con relaciones
     */
    public function obtener(int $entradaId): ?EntradaInventario
    {
        return EntradaInventario::with(['proveedor', 'bodega', 'usuario', 'detalles.producto'])
            ->find($entradaId);
    }

    /**
     * Listar entradas con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return EntradaInventario::with(['proveedor', 'bodega', 'usuario'])
            ->orderByDesc('fecha_entrada')
            ->paginate($perPage);
    }

    /**
     * Entradas por proveedor
     */
    public function porProveedor(int $proveedorId, int $perPage = 15): LengthAwarePaginator
    {
        return EntradaInventario::where('proveedor_id', $proveedorId)
            ->with(['proveedor', 'bodega', 'detalles'])
            ->orderByDesc('fecha_entrada')
            ->paginate($perPage);
    }

    /**
     * Entradas por almacén/bodega
     */
    public function porAlmacen(int $almacenId, int $perPage = 15): LengthAwarePaginator
    {
        return EntradaInventario::where('bodega_id', $almacenId)
            ->with(['proveedor', 'bodega', 'detalles'])
            ->orderByDesc('fecha_entrada')
            ->paginate($perPage);
    }

    /**
     * Entradas entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): LengthAwarePaginator
    {
        return EntradaInventario::whereBetween('fecha_entrada', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with(['proveedor', 'bodega', 'detalles'])
            ->orderByDesc('fecha_entrada')
            ->paginate($perPage);
    }

    /**
     * Actualizar entrada
     */
    public function actualizar(EntradaInventario $entrada, array $data): EntradaInventario
    {
        $entrada->update($data);
        return $entrada->fresh(['proveedor', 'bodega', 'usuario', 'detalles.producto']);
    }

    /**
     * Eliminar entrada
     */
    public function eliminar(EntradaInventario $entrada): bool
    {
        return $entrada->delete();
    }

    /**
     * Procesar entrada y actualizar stock
     */
    public function procesar(EntradaInventario $entrada): EntradaInventario
    {
        if ($entrada->estado === 'Procesada') {
            throw new \Exception('La entrada ya fue procesada anteriormente');
        }

        $entrada->load('detalles.producto');

        if ($entrada->detalles->isEmpty()) {
            throw new \Exception('No se puede procesar una entrada sin productos');
        }

        return DB::transaction(function () use ($entrada) {
            foreach ($entrada->detalles as $detalle) {
                $inventario = DB::table('inventarios')
                    ->where('producto_id', $detalle->producto_id)
                    ->where('almacen_id', $entrada->bodega_id)
                    ->first();

                if ($inventario) {
                    DB::table('inventarios')
                        ->where('id', $inventario->id)
                        ->increment('cantidad_actual', (float) $detalle->cantidad);
                } else {
                    DB::table('inventarios')->insert([
                        'producto_id' => $detalle->producto_id,
                        'almacen_id' => $entrada->bodega_id,
                        'cantidad_actual' => (float) $detalle->cantidad,
                        'cantidad_minima' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            $entrada->update(['estado' => 'Procesada']);
            return $entrada->fresh(['proveedor', 'bodega', 'detalles.producto']);
        });
    }

    /**
     * Cancelar entrada
     */
    public function cancelar(EntradaInventario $entrada): EntradaInventario
    {
        if ($entrada->estado === 'Procesada') {
            throw new \Exception('No se puede cancelar una entrada ya procesada');
        }

        $entrada->update(['estado' => 'Cancelada']);
        return $entrada->fresh();
    }
}
