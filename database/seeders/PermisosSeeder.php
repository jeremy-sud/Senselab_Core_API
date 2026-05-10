<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modulos = [
            'empresas' => 'Empresas',
            'sucursales' => 'Sucursales',
            'usuarios' => 'Usuarios',
            'roles' => 'Roles y Permisos',
            'productos' => 'Productos',
            'clientes' => 'Clientes',
            'proveedores' => 'Proveedores',
            'inventario' => 'Inventario',
            'ventas' => 'Ventas',
            'compras' => 'Compras',
            'contabilidad' => 'Contabilidad',
            'nomina' => 'Nómina',
            'cuentas_cobrar' => 'Cuentas por Cobrar',
            'cuentas_pagar' => 'Cuentas por Pagar',
            'facturacion' => 'Facturación Electrónica',
            'reportes' => 'Reportes',
            'configuracion' => 'Configuración',
            'webhooks' => 'Webhooks',
            'reservas' => 'Reservas',
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        foreach ($modulos as $moduloSlug => $moduloNombre) {
            foreach ($acciones as $accion) {
                $permiso = [
                    'nombre' => ucfirst($accion) . ' ' . $moduloNombre,
                    'slug' => $accion . '-' . $moduloSlug,
                    'descripcion' => 'Permiso para ' . $accion . ' ' . strtolower($moduloNombre),
                    'modulo' => $moduloNombre,
                    'activo' => true,
                    'eliminado' => false,
                ];

                DB::table('permisos')->updateOrInsert(
                    ['slug' => $permiso['slug']],
                    $permiso
                );
            }
        }

        $this->command->info('✓ Permisos cargados exitosamente.');
    }
}
