<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar Productos
 *
 * Encapsula la lógica de negocio para productos
 */
class ProductoService
{
    /**
     * Listar productos con filtros y paginación
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Producto::with(['empresa', 'categoria', 'unidadMedida', 'marca', 'tipoImpuesto'])
            ->where('productos.eliminado', false);

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (!empty($filtros['empresa_id'])) {
            $query->porEmpresa($filtros['empresa_id']);
        }

        if (!empty($filtros['categoria_id'])) {
            $query->porCategoria($filtros['categoria_id']);
        }

        if (!empty($filtros['tipo'])) {
            $query->porTipo($filtros['tipo']);
        }

        if (isset($filtros['activo'])) {
            if ($filtros['activo']) {
                $query->activos();
            }
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Crear un nuevo producto
     *
     * @param array<string, mixed> $data
     * @return Producto
     */
    public function crear(array $data): Producto
    {
        $producto = Producto::create($data);
        $producto->load(['empresa', 'categoria', 'unidadMedida', 'marca', 'tipoImpuesto']);
        return $producto;
    }

    /**
     * Obtener producto por ID con relaciones completas
     *
     * @param int $id
     * @return Producto
     */
    public function obtener(int $id): Producto
    {
        return Producto::with([
            'empresa', 'categoria', 'unidadMedida', 'marca',
            'proveedor', 'tipoImpuesto', 'cabys'
        ])->findOrFail($id);
    }

    /**
     * Actualizar un producto existente
     *
     * @param Producto $producto
     * @param array<string, mixed> $data
     * @return Producto
     */
    public function actualizar(Producto $producto, array $data): Producto
    {
        $producto->update($data);
        $producto->load(['empresa', 'categoria', 'unidadMedida', 'marca', 'tipoImpuesto']);
        return $producto;
    }

    /**
     * Eliminar un producto (soft delete)
     *
     * @param Producto $producto
     * @return bool
     */
    public function eliminar(Producto $producto): bool
    {
        $producto->update([
            'activo' => false,
            'eliminado' => true,
        ]);
        return true;
    }

    /**
     * Actualizar stock de producto
     *
     * @param Producto $producto
     * @param float $cantidad
     * @return Producto
     */
    public function actualizarStock(Producto $producto, float $cantidad): Producto
    {
        $producto->stock_actual = $cantidad;
        $producto->save();
        return $producto->fresh() ?? $producto;
    }
}
