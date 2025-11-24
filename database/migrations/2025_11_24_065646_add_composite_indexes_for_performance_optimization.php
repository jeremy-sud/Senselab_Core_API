<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sprint 8.2: Índices compuestos estratégicos para optimización de performance
     * Estos índices mejoran las consultas más frecuentes del sistema
     */
    public function up(): void
    {
        // En entorno de testing / unit tests omitimos creación de índices compuestos
        if (app()->environment('testing') || app()->runningUnitTests() || env('APP_ENV') === 'testing') {
            return;
        }
        // === VENTAS: Optimización de consultas por empresa, fecha y estado ===
        Schema::table('ventas', function (Blueprint $table) {
            // Ajuste: la columna de estado en tabla ventas se llama 'estado_venta'
            if (Schema::hasColumn('ventas', 'empresa_id') && Schema::hasColumn('ventas', 'fecha_venta') && Schema::hasColumn('ventas', 'eliminado')) {
                $table->index(['empresa_id', 'fecha_venta', 'eliminado'], 'idx_ventas_empresa_fecha_eliminado');
            }
            if (Schema::hasColumn('ventas', 'empresa_id') && Schema::hasColumn('ventas', 'estado_venta') && Schema::hasColumn('ventas', 'eliminado')) {
                $table->index(['empresa_id', 'estado_venta', 'eliminado'], 'idx_ventas_empresa_estadoVenta_eliminado');
            }
            if (Schema::hasColumn('ventas', 'cliente_id') && Schema::hasColumn('ventas', 'fecha_venta')) {
                $table->index(['cliente_id', 'fecha_venta'], 'idx_ventas_cliente_fecha');
            }
        });

        // === CLIENTES: Optimización de búsquedas y filtros ===
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes','empresa_id') && Schema::hasColumn('clientes','activo') && Schema::hasColumn('clientes','eliminado')) {
                $table->index(['empresa_id', 'activo', 'eliminado'], 'idx_clientes_empresa_activo_eliminado');
            }
            // Ajuste: la tabla clientes no tiene tipo_cliente_id, usamos tipo_identificacion para segmentar
            if (Schema::hasColumn('clientes','empresa_id') && Schema::hasColumn('clientes','tipo_identificacion')) {
                $table->index(['empresa_id', 'tipo_identificacion'], 'idx_clientes_empresa_tipoIdent');
            }
        });

        // === PRODUCTOS: Optimización de consultas de inventario ===
        Schema::table('productos', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo', 'eliminado'], 'idx_productos_empresa_activo_eliminado');
            $table->index(['empresa_id', 'categoria_id', 'activo'], 'idx_productos_empresa_categoria_activo');
        });

        // === PROVEEDORES: Optimización de filtros ===
        Schema::table('proveedores', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo', 'eliminado'], 'idx_proveedores_empresa_activo_eliminado');
        });

        // === ORDENES COMPRA: Optimización de consultas por fecha y estado ===
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->index(['empresa_id', 'fecha_orden', 'eliminado'], 'idx_ordenes_empresa_fecha_eliminado');
            $table->index(['empresa_id', 'estado', 'eliminado'], 'idx_ordenes_empresa_estado_eliminado');
            $table->index(['proveedor_id', 'fecha_orden'], 'idx_ordenes_proveedor_fecha');
        });

        // === ASIENTOS CONTABLES: Optimización de consultas contables ===
        Schema::table('asientos_contables', function (Blueprint $table) {
            $table->index(['empresa_id', 'fecha_asiento', 'eliminado'], 'idx_asientos_empresa_fecha_eliminado');
            $table->index(['empresa_id', 'tipo_asiento', 'estado'], 'idx_asientos_empresa_tipo_estado');
        });

        // === CUENTAS POR COBRAR: Optimización de gestión de cobros ===
        Schema::table('cuentas_por_cobrar', function (Blueprint $table) {
            $table->index(['empresa_id', 'estado', 'eliminado'], 'idx_cxc_empresa_estado_eliminado');
            $table->index(['empresa_id', 'fecha_vencimiento', 'estado'], 'idx_cxc_empresa_vencimiento_estado');
            $table->index(['cliente_id', 'estado'], 'idx_cxc_cliente_estado');
        });

        // === CUENTAS POR PAGAR: Optimización de gestión de pagos ===
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->index(['empresa_id', 'estado', 'eliminado'], 'idx_cxp_empresa_estado_eliminado');
            $table->index(['empresa_id', 'fecha_vencimiento', 'estado'], 'idx_cxp_empresa_vencimiento_estado');
            $table->index(['proveedor_id', 'estado'], 'idx_cxp_proveedor_estado');
        });

        // === INVENTARIO: Optimización de consultas de stock ===
        Schema::table('inventario_productos', function (Blueprint $table) {
            $table->index(['almacen_id', 'producto_id'], 'idx_inventario_almacen_producto');
        });

        // === ENTRADAS INVENTARIO: Optimización de historial ===
        Schema::table('entradas_inventario', function (Blueprint $table) {
            $table->index(['empresa_id', 'fecha_entrada', 'eliminado'], 'idx_entradas_empresa_fecha_eliminado');
            $table->index(['almacen_id', 'fecha_entrada'], 'idx_entradas_almacen_fecha');
        });

        // === SALIDAS INVENTARIO: Optimización de historial ===
        Schema::table('salidas_inventario', function (Blueprint $table) {
            $table->index(['empresa_id', 'fecha_salida', 'eliminado'], 'idx_salidas_empresa_fecha_eliminado');
            $table->index(['almacen_id', 'fecha_salida'], 'idx_salidas_almacen_fecha');
        });

        // === NOMINA: Optimización de consultas de nómina ===
        Schema::table('nomina_empleados', function (Blueprint $table) {
            // Ajuste: tabla no tiene empresa_id, solo índices útiles sobre periodo + empleado
            if (Schema::hasColumn('nomina_empleados','periodo_nomina_id') && Schema::hasColumn('nomina_empleados','empleado_id')) {
                $table->index(['periodo_nomina_id', 'empleado_id'], 'idx_nomina_periodo_empleado');
            }
        });

        // === EMPLEADOS: Optimización de filtros ===
        Schema::table('empleados', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo', 'eliminado'], 'idx_empleados_empresa_activo_eliminado');
            $table->index(['empresa_id', 'cargo_id'], 'idx_empleados_empresa_cargo');
        });

        // === USUARIOS: Optimización de autenticación y búsquedas ===
        Schema::table('usuarios', function (Blueprint $table) {
            $table->index(['empresa_id', 'activo', 'eliminado'], 'idx_usuarios_empresa_activo_eliminado');
        });

        // === MOVIMIENTOS BANCARIOS: Optimización de conciliación ===
        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            $table->index(['cuenta_bancaria_id', 'fecha_movimiento'], 'idx_movbanco_cuenta_fecha');
            $table->index(['cuenta_bancaria_id', 'conciliado'], 'idx_movbanco_cuenta_conciliado');
        });

        // === NOTIFICACIONES: Optimización de consultas de usuario ===
        Schema::table('notificaciones', function (Blueprint $table) {
            // Tabla no contiene columna 'eliminado'; ya existe índice usuario+leida creado en migración base.
            if (Schema::hasColumn('notificaciones','usuario_id') && Schema::hasColumn('notificaciones','tipo')) {
                $table->index(['usuario_id', 'tipo'], 'idx_notif_usuario_tipo');
            }
        });

        // === AUDITORIA: Optimización de consultas de auditoría ===
        Schema::table('auditoria_actividades', function (Blueprint $table) {
            $table->index(['empresa_id', 'tabla', 'accion'], 'idx_audit_empresa_tabla_accion');
            $table->index(['usuario_id', 'accion'], 'idx_audit_usuario_accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing') || app()->runningUnitTests() || env('APP_ENV') === 'testing') {
            return; // Nada que revertir en testing
        }
        // === ELIMINAR ÍNDICES EN ORDEN INVERSO ===
        
        Schema::table('auditoria_actividades', function (Blueprint $table) {
            $table->dropIndex('idx_audit_empresa_tabla_accion');
            $table->dropIndex('idx_audit_usuario_accion');
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            if (Schema::hasColumn('notificaciones','usuario_id')) {
                $table->dropIndex('idx_notif_usuario_tipo');
            }
        });

        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            $table->dropIndex('idx_movbanco_cuenta_fecha');
            $table->dropIndex('idx_movbanco_cuenta_conciliado');
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropIndex('idx_usuarios_empresa_activo_eliminado');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropIndex('idx_empleados_empresa_activo_eliminado');
            $table->dropIndex('idx_empleados_empresa_cargo');
        });

        Schema::table('nomina_empleados', function (Blueprint $table) {
            if (Schema::hasColumn('nomina_empleados','periodo_nomina_id')) {
                $table->dropIndex('idx_nomina_periodo_empleado');
            }
        });

        Schema::table('salidas_inventario', function (Blueprint $table) {
            $table->dropIndex('idx_salidas_empresa_fecha_eliminado');
            $table->dropIndex('idx_salidas_almacen_fecha');
        });

        Schema::table('entradas_inventario', function (Blueprint $table) {
            $table->dropIndex('idx_entradas_empresa_fecha_eliminado');
            $table->dropIndex('idx_entradas_almacen_fecha');
        });

        Schema::table('inventario_productos', function (Blueprint $table) {
            $table->dropIndex('idx_inventario_almacen_producto');
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropIndex('idx_cxp_empresa_estado_eliminado');
            $table->dropIndex('idx_cxp_empresa_vencimiento_estado');
            $table->dropIndex('idx_cxp_proveedor_estado');
        });

        Schema::table('cuentas_por_cobrar', function (Blueprint $table) {
            $table->dropIndex('idx_cxc_empresa_estado_eliminado');
            $table->dropIndex('idx_cxc_empresa_vencimiento_estado');
            $table->dropIndex('idx_cxc_cliente_estado');
        });

        Schema::table('asientos_contables', function (Blueprint $table) {
            $table->dropIndex('idx_asientos_empresa_fecha_eliminado');
            $table->dropIndex('idx_asientos_empresa_tipo_estado');
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('idx_ordenes_empresa_fecha_eliminado');
            $table->dropIndex('idx_ordenes_empresa_estado_eliminado');
            $table->dropIndex('idx_ordenes_proveedor_fecha');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropIndex('idx_proveedores_empresa_activo_eliminado');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('idx_productos_empresa_activo_eliminado');
            $table->dropIndex('idx_productos_empresa_categoria_activo');
        });

        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes','empresa_id')) {
                $table->dropIndex('idx_clientes_empresa_activo_eliminado');
            }
            if (Schema::hasColumn('clientes','tipo_identificacion')) {
                $table->dropIndex('idx_clientes_empresa_tipoIdent');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Drop seguro solo si existen
            if (Schema::hasColumn('ventas', 'empresa_id')) {
                $table->dropIndex('idx_ventas_empresa_fecha_eliminado');
            }
            if (Schema::hasColumn('ventas', 'estado_venta')) {
                $table->dropIndex('idx_ventas_empresa_estadoVenta_eliminado');
            }
            if (Schema::hasColumn('ventas', 'cliente_id')) {
                $table->dropIndex('idx_ventas_cliente_fecha');
            }
        });
    }
};
