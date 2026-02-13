<?php

namespace App\Services;

use App\DTOs\API\ProductoCreateDTO;
use App\DTOs\API\ProductoUpdateDTO;
use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar Productos
 *
 * Encapsula la lógica de negocio para productos
 * Fecha de creación: 12 de febrero de 2026
 */
class ProductoService
{
    /**
     * Crear un nuevo producto
     */
    public function crear(ProductoCreateDTO $dto): Producto
    {
        return Producto::create($dto->toArray());
    }

    /**
     * Actualizar un producto existente
     */
    public function actualizar(Producto $producto, ProductoUpdateDTO $dto): Producto
    {
        $producto->update($dto->toArray());
        return $producto->fresh() ?? $producto;
    }

    /**
     * Eliminar un producto
     */
    public function eliminar(Producto $producto): bool
    {
        return (bool) $producto->delete();
    }

    /**
     * Obtener producto por ID
     */
    public function obtener(int $productoId): ?Producto
    {
        return Producto::find($productoId);
    }

    /**
     * Listar productos con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return Producto::paginate($perPage);
    }

    /**
     * Buscar productos
     */
    public function buscar(string $termino, int $perPage = 15): LengthAwarePaginator
    {
        return Producto::where('nombre', 'like', "%{$termino}%")
            ->orWhere('descripcion', 'like', "%{$termino}%")
            ->orWhere('sku', 'like', "%{$termino}%")
            ->paginate($perPage);
    }

    /**
     * Obtener productos activos
     */
    public function activos(int $perPage = 15): LengthAwarePaginator
    {
        return Producto::where('activo', true)->paginate($perPage);
    }

    /**
     * Actualizar stock de producto
     */
    public function actualizarStock(Producto $producto, float $cantidad): Producto
    {
        $producto->stock_actual = $cantidad;
        $producto->save();
        return $producto->fresh() ?? $producto;
    }
}
