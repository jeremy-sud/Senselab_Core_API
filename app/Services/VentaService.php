<?php

namespace App\Services;

use App\DTOs\API\VentaCreateDTO;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Almacen;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\InventarioProducto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * VentaService - Servicio de Gestión de Ventas
 * 
 * Encapsula la lógica de negocio completa para ventas:
 * - CRUD operations
 * - Detalle de venta processing con cálculo de totales
 * - Validación de stock
 * - Generación de números de comprobante
 * - Cambios de estado
 * 
 * Refactorización: 12 de febrero de 2026 (FASE 4.2)
 */
class VentaService
{
    /**
     * Crear una nueva venta con detalles
     * 
     * @param VentaCreateDTO $dto
     * @return Venta
     * @throws \Exception
     */
    public function crear(VentaCreateDTO $dto): Venta
    {
        DB::beginTransaction();
        
        try {
            // Validar relaciones
            $sucursal = $this->validarSucursal($dto->getSucursalId(), $dto->getEmpresaId());
            $cliente = $this->validarCliente($dto->getClienteId(), $dto->getEmpresaId());
            $usuario = $this->validarUsuario($dto->getUsuarioId(), $dto->getEmpresaId());
            
            $almacen = $dto->getAlmacenId() 
                ? $this->validarAlmacen($dto->getAlmacenId(), $dto->getEmpresaId())
                : null;
            
            // Validar productos
            $productos = $this->validarProductos($dto->getProductoIds(), $dto->getEmpresaId());
            
            // Validar stock si hay almacén
            if ($almacen) {
                $this->validarStockDisponible($almacen->id, $dto->getDetalles());
            }
            
            // Crear venta base
            $ventaData = $dto->toArray();
            $ventaData['numero_comprobante'] = $this->generarNumeroComprobante(
                $dto->getEmpresaId(),
                $ventaData['tipo_comprobante']
            );
            
            $venta = Venta::create($ventaData);
            
            // Procesar detalles y calcular totales
            $totales = $this->procesarDetalles($venta, $dto->getDetalles(), $productos, $almacen);
            
            // Actualizar totales en venta
            $venta->update([
                'subtotal_bruto_total' => $totales['subtotal'],
                'monto_descuento_total' => $totales['descuentos'],
                'subtotal_neto_total' => $totales['subtotal'] - $totales['descuentos'],
                'monto_impuesto_total' => $totales['impuestos'],
                'monto_total_venta' => ($totales['subtotal'] - $totales['descuentos']) + $totales['impuestos'],
            ]);
            
            DB::commit();
            
            return $venta->load(['cliente', 'detalles.producto', 'empresa', 'sucursal', 'usuario']);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener venta por ID con relaciones
     */
    public function obtener(int $ventaId): ?Venta
    {
        return Venta::with([
            'empresa', 'sucursal', 'cliente', 'usuario', 'formaPago', 'detalles.producto'
        ])->find($ventaId);
    }

    /**
     * Listar ventas con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return Venta::with(['empresa', 'sucursal', 'cliente'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Ventas por cliente
     */
    public function porCliente(int $clienteId, int $perPage = 15): LengthAwarePaginator
    {
        return Venta::where('cliente_id', $clienteId)
            ->with(['detalles.producto', 'cliente'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Ventas entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): LengthAwarePaginator
    {
        return Venta::whereBetween('fecha_venta', [
            $inicio->format('Y-m-d'),
            $fin->format('Y-m-d')
        ])
            ->with('cliente')
            ->orderBy('fecha_venta', 'desc')
            ->paginate($perPage);
    }

    /**
     * Calcular total de ventas en período
     */
    public function totalEnPeriodo(\DateTime $inicio, \DateTime $fin): float
    {
        return (float) Venta::whereBetween('fecha_venta', [
            $inicio->format('Y-m-d'),
            $fin->format('Y-m-d')
        ])->sum('monto_total_venta');
    }

    /**
     * Cambiar estado de venta
     */
    public function cambiarEstado(Venta $venta, string $nuevoEstado): Venta
    {
        $estadosValidos = ['pendiente', 'pagada', 'parcial', 'anulada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '{$nuevoEstado}' no válido");
        }
        
        $venta->update(['estado_venta' => $nuevoEstado]);
        return $venta->fresh();
    }

    /**
     * Anular venta
     */
    public function anular(Venta $venta): Venta
    {
        $venta->update([
            'estado_venta' => 'anulada',
            'activo' => false,
            'eliminado' => true
        ]);
        
        return $venta->fresh();
    }

    /**
     * ===== MÉTODOS PRIVADOS HELPER =====
     */

    /**
     * Generar número de comprobante único por tipo
     */
    private function generarNumeroComprobante(int $empresaId, string $tipoComprobante): string
    {
        $prefijos = [
            'factura' => 'FAC',
            'tiquete' => 'TIQ',
            'nota_credito' => 'NC',
            'nota_debito' => 'ND',
        ];

        $prefijo = $prefijos[$tipoComprobante] ?? 'DOC';

        $ultimaVenta = Venta::where('empresa_id', $empresaId)
            ->where('tipo_comprobante', $tipoComprobante)
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimaVenta ? (int)substr($ultimaVenta->numero_comprobante, -8) + 1 : 1;

        return $prefijo . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Validar sucursal pertenece a empresa
     */
    private function validarSucursal(int $sucursalId, int $empresaId): Sucursal
    {
        return Sucursal::where('id', $sucursalId)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();
    }

    /**
     * Validar cliente pertenece a empresa
     */
    private function validarCliente(int $clienteId, int $empresaId): Cliente
    {
        return Cliente::where('id', $clienteId)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();
    }

    /**
     * Validar usuario pertenece a empresa
     */
    private function validarUsuario(int $usuarioId, int $empresaId): Usuario
    {
        $usuario = Usuario::where('id', $usuarioId)
            ->where('empresa_id', $empresaId)
            ->first();

        if (!$usuario) {
            throw new AccessDeniedHttpException('Usuario no pertenece a la empresa');
        }

        return $usuario;
    }

    /**
     * Validar almacén pertenece a empresa
     */
    private function validarAlmacen(int $almacenId, int $empresaId): Almacen
    {
        return Almacen::where('id', $almacenId)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();
    }

    /**
     * Validar que todos los productos existen y pertenecen a empresa
     */
    private function validarProductos(array $productoIds, int $empresaId): Collection
    {
        if (empty($productoIds)) {
            throw ValidationException::withMessages([
                'detalles' => ['Debe incluir al menos un producto']
            ]);
        }

        $productos = Producto::whereIn('id', array_unique($productoIds))
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy('id');

        if ($productos->count() !== count(array_unique($productoIds))) {
            throw new AccessDeniedHttpException('Uno o más productos no pertenecen a empresa');
        }

        return $productos;
    }

    /**
     * Validar stock disponible en almacén
     */
    private function validarStockDisponible(int $almacenId, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            $inventario = InventarioProducto::where('almacen_id', $almacenId)
                ->where('producto_id', $detalle['producto_id'])
                ->first();

            if (!$inventario || $inventario->stock_actual < $detalle['cantidad']) {
                throw ValidationException::withMessages([
                    'stock' => ["Stock insuficiente para producto ID {$detalle['producto_id']}"]
                ]);
            }
        }
    }

    /**
     * Procesar detalles de venta y descontar stock
     * 
     * @return array{subtotal: float, descuentos: float, impuestos: float}
     */
    private function procesarDetalles(Venta $venta, array $detalles, Collection $productos, ?Almacen $almacen): array
    {
        $subtotal = 0;
        $descuentos = 0;
        $impuestos = 0;

        foreach ($detalles as $index => $detalle) {
            $producto = $productos->get($detalle['producto_id']);
            
            $cantidad = (float) $detalle['cantidad'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $montoDescuento = (float) ($detalle['descuento'] ?? 0);
            $tasaImpuesto = (float) ($detalle['porcentaje_impuesto'] ?? 0);

            $subtotalLinea = $cantidad * $precioUnitario;
            $subtotalConDescuento = max(0, $subtotalLinea - $montoDescuento);
            $impuestoLinea = $subtotalConDescuento * ($tasaImpuesto / 100);
            $totalLinea = $subtotalConDescuento + $impuestoLinea;
            $porcentajeDescuento = $subtotalLinea > 0 ? ($montoDescuento / $subtotalLinea) * 100 : 0;

            $subtotal += $subtotalLinea;
            $descuentos += $montoDescuento;
            $impuestos += $impuestoLinea;

            // Crear registro de detalle
            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'numero_linea' => $index + 1,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal_linea' => $subtotalLinea,
                'porcentaje_descuento' => $porcentajeDescuento,
                'monto_descuento' => $montoDescuento,
                'subtotal_con_descuento' => $subtotalConDescuento,
                'tasa_impuesto' => $tasaImpuesto,
                'monto_impuesto' => $impuestoLinea,
                'total_linea' => $totalLinea,
                'detalle_adicional' => $detalle['descripcion'] ?? null,
            ]);

            // Descontar stock si hay almacén
            if ($almacen) {
                $inventario = InventarioProducto::where('almacen_id', $almacen->id)
                    ->where('producto_id', $producto->id)
                    ->lockForUpdate()
                    ->first();

                if ($inventario) {
                    $inventario->decrement('stock_actual', $cantidad);
                }
            }
        }

        return compact('subtotal', 'descuentos', 'impuestos');
    }
}
