<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para permisos de las 57 policies del sistema
 * Formato compatible con hasPermission() del modelo Usuario
 * 
 * @author GitHub Copilot
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PermisosPoliciesSeeder extends Seeder
{
    /**
     * Lista de todos los modelos con policies
     * Formato: ['nombre_permiso' => 'Descripción del módulo']
     */
    private array $modulos = [
        // 21 Policies originales
        'empresas' => 'Empresas',
        'usuarios' => 'Usuarios',
        'productos' => 'Productos',
        'ventas' => 'Ventas',
        'clientes' => 'Clientes',
        'proveedores' => 'Proveedores',
        'cuentas_bancarias' => 'Cuentas Bancarias',
        'declaraciones_tributarias' => 'Declaraciones Tributarias',
        'almacenes' => 'Almacenes',
        'sucursales' => 'Sucursales',
        'ordenes_compra' => 'Órdenes de Compra',
        'empleados' => 'Empleados',
        'categorias_productos' => 'Categorías de Productos',
        'roles' => 'Roles',
        'permisos' => 'Permisos',
        'cuentas_por_cobrar' => 'Cuentas por Cobrar',
        'cuentas_por_pagar' => 'Cuentas por Pagar',
        'movimientos_bancarios' => 'Movimientos Bancarios',
        'retenciones_impuestos' => 'Retenciones de Impuestos',
        'cajas_chicas' => 'Cajas Chicas',
        'asientos_contables' => 'Asientos Contables',
        
        // 36 Policies nuevas (Sprint 1)
        'bus_unidades' => 'Buses/Unidades',
        'cabys' => 'Códigos CABYS',
        'cargos' => 'Cargos',
        'codigos_actividad_economica' => 'Códigos de Actividad Económica',
        'comprobantes_recibidos_electronicos' => 'Comprobantes Recibidos Electrónicos',
        'configuraciones' => 'Configuraciones',
        'cuentas_contables' => 'Cuentas Contables',
        'deducciones_legales' => 'Deducciones Legales',
        'detalles_asientos' => 'Detalles de Asientos Contables',
        'detalles_entradas_inventario' => 'Detalles de Entradas de Inventario',
        'detalles_presupuestos' => 'Detalles de Presupuestos',
        'detalles_salidas_inventario' => 'Detalles de Salidas de Inventario',
        'entradas_inventario' => 'Entradas de Inventario',
        'formas_pago' => 'Formas de Pago',
        'horarios_rutas' => 'Horarios de Rutas',
        'logs_acceso_sistema' => 'Logs de Acceso al Sistema',
        'marcas' => 'Marcas',
        'mensajes_hacienda' => 'Mensajes de Hacienda',
        'modelos_bus' => 'Modelos de Bus',
        'pagos' => 'Pagos',
        'pagos_nomina' => 'Pagos de Nómina',
        'periodos_nomina' => 'Períodos de Nómina',
        'planillas_ccss' => 'Planillas CCSS',
        'presupuestos' => 'Presupuestos',
        'rutas' => 'Rutas',
        'salidas_inventario' => 'Salidas de Inventario',
        'tasas_impuesto' => 'Tasas de Impuesto',
        'tipos_cliente' => 'Tipos de Cliente',
        'tipos_comprobante_fe' => 'Tipos de Comprobante FE',
        'tipos_cuenta' => 'Tipos de Cuenta',
        'tipos_impuesto' => 'Tipos de Impuesto',
        'tiquetes_detalles' => 'Detalles de Tiquetes',
        'unidades_medida' => 'Unidades de Medida',
        'url_shorteners' => 'Acortador de URLs',
        'zonas_geograficas' => 'Zonas Geográficas',
        'inventarios' => 'Inventarios de Productos',
    ];

    /**
     * Acciones CRUD estándar para policies
     */
    private array $acciones = [
        'leer' => 'Ver/Listar',
        'crear' => 'Crear',
        'editar' => 'Editar/Actualizar',
        'eliminar' => 'Eliminar',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creando permisos para 57 policies...');
        
        $totalPermisos = 0;
        
        foreach ($this->modulos as $moduloNombre => $moduloDescripcion) {
            foreach ($this->acciones as $accionNombre => $accionDescripcion) {
                // Formato: productos.leer (con puntos para hasPermission())
                $nombre = "{$moduloNombre}.{$accionNombre}";
                
                // Formato: productos-leer (con guiones para URLs)
                $slug = str_replace('_', '-', $moduloNombre) . "-{$accionNombre}";
                
                $permiso = [
                    'nombre' => $nombre,
                    'slug' => $slug,
                    'descripcion' => "{$accionDescripcion} {$moduloDescripcion}",
                    'modulo' => $moduloDescripcion,
                    'activo' => true,
                    'eliminado' => false,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ];

                DB::table('permisos')->updateOrInsert(
                    ['nombre' => $nombre], // Buscar por nombre (campo único usado en hasPermission)
                    $permiso
                );
                
                $totalPermisos++;
            }
        }

        $this->command->info("✅ {$totalPermisos} permisos creados/actualizados exitosamente");
        $this->command->info("   - 57 módulos");
        $this->command->info("   - 4 acciones por módulo (leer, crear, editar, eliminar)");
        $this->command->info("   - Total: 228 permisos");
    }
}
