<?php

namespace App\Services;

use App\DTOs\API\ProductoCreateDTO;
use App\DTOs\API\ProductoUpdateDTO;
use App\Models\Produto;
use Illuminate\Pagination\Paginator;

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
    public function crear(ProductoCreateDTO $dto): Produto
    {
        return Produto::create($dto->toArray());
    }

    /**
     * Actualizar un producto existente
     */
    public function actualizar(Produto $producto, ProductoUpdateDTO $dto): Produto
    {
        $producto->update($dto->toArray());
        return $producto->fresh();
    }

    /**
     * Eliminar un producto
     */
    public function eliminar(Produto $producto): bool
    {
        return $producto->delete();
    }

    /**
     * Obtener producto por ID
     */
    public function obtener(int $productoId): ?Produto
    {
        return Produto::find($productoId);
    }

    /**
     * Listar productos con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return Produto::paginate($perPage);
    }

    /**
     * Buscar productos
     */
    public function buscar(string $termino, int $perPage = 15): Paginator
    {
        return Produto::where('nombre', 'like', "%{$termino}%")
            ->orWhere('descripcion', 'like', "%{$termino}%")
            ->orWhere('sku', 'like', "%{$termino}%")
            ->paginate($perPage);
    }

    /**
     * Obtener productos activos
     */
    public function activos(int $perPage = 15): Paginator
    {
        return Produto::where('activo', true)->paginate($perPage);
    }

    /**
     * Actualizar stock de producto
     */
    public function actualizarStock(Produto $producto, float $cantidad): Produto
    {
        $producto->stock_actual = $cantidad;
        $producto->save();
        return $producto->fresh();
    }
}
