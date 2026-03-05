<?php

namespace App\Services;

use App\Models\EntradaInventario;
use App\Models\SalidaInventario;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestionar Inventario (Entradas y Salidas)
 */
class InventarioService
{
    // ─── ENTRADAS ────────────────────────────────────────────────

    /**
     * Listar entradas de inventario con filtros
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @return Collection
     */
    public function listarEntradas(int $empresaId, array $filtros = []): Collection
    {
        $query = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles']);

        if (!empty($filtros['almacen_id'])) {
            $query->where('almacen_id', $filtros['almacen_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['tipo_entrada'])) {
            $query->where('tipo_entrada', $filtros['tipo_entrada']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_entrada', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_entrada', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('fecha_entrada', 'desc')->get();
    }

    /**
     * Crear entrada de inventario
     *
     * @param array<string, mixed> $data
     * @return EntradaInventario
     */
    public function crearEntrada(array $data): EntradaInventario
    {
        $entrada = EntradaInventario::create($data);
        $entrada->load(['almacen', 'ordenCompra', 'proveedor', 'detalles']);
        return $entrada;
    }

    /**
     * Obtener entrada de inventario por ID
     *
     * @param int $empresaId
     * @param int $id
     * @return EntradaInventario
     */
    public function obtenerEntrada(int $empresaId, int $id): EntradaInventario
    {
        return EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);
    }

    /**
     * Cancelar entrada de inventario
     *
     * @param EntradaInventario $entrada
     * @return EntradaInventario
     * @throws ValidationException
     */
    public function cancelarEntrada(EntradaInventario $entrada): EntradaInventario
    {
        if ($entrada->estado === 'Procesada') {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cancelar una entrada ya procesada.',
            ]);
        }

        $entrada->estado = 'Cancelada';
        $entrada->save();
        return $entrada;
    }

    // ─── SALIDAS ─────────────────────────────────────────────────

    /**
     * Listar salidas de inventario con filtros
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @return Collection
     */
    public function listarSalidas(int $empresaId, array $filtros = []): Collection
    {
        $query = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);

        if (!empty($filtros['almacen_id'])) {
            $query->where('almacen_id', $filtros['almacen_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['tipo_salida'])) {
            $query->where('tipo_salida', $filtros['tipo_salida']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_salida', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_salida', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('fecha_salida', 'desc')->get();
    }

    /**
     * Crear salida de inventario
     *
     * @param array<string, mixed> $data
     * @return SalidaInventario
     */
    public function crearSalida(array $data): SalidaInventario
    {
        $salida = SalidaInventario::create($data);
        $salida->load(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);
        return $salida;
    }

    /**
     * Obtener salida de inventario por ID
     *
     * @param int $empresaId
     * @param int $id
     * @return SalidaInventario
     */
    public function obtenerSalida(int $empresaId, int $id): SalidaInventario
    {
        return SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);
    }

    /**
     * Cancelar salida de inventario
     *
     * @param SalidaInventario $salida
     * @return SalidaInventario
     * @throws ValidationException
     */
    public function cancelarSalida(SalidaInventario $salida): SalidaInventario
    {
        if ($salida->estado === 'Procesada') {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cancelar una salida ya procesada.',
            ]);
        }

        $salida->estado = 'Cancelada';
        $salida->save();
        return $salida;
    }
}
