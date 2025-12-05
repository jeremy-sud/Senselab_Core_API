<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Super Administrador',
                'descripcion' => 'Acceso total absoluto al sistema - Solo fundadores',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total al sistema',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Gerente',
                'descripcion' => 'Acceso a gestión y reportes',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Contador',
                'descripcion' => 'Acceso a módulos contables y financieros',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Vendedor',
                'descripcion' => 'Acceso a ventas, clientes y facturación',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Comprador',
                'descripcion' => 'Acceso a compras y proveedores',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Bodeguero',
                'descripcion' => 'Acceso a inventario y almacenes',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'nombre' => 'Usuario',
                'descripcion' => 'Acceso básico de solo lectura',
                'activo' => true,
                'eliminado' => false,
            ],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(
                ['nombre' => $rol['nombre']],
                $rol
            );
        }

        $this->command->info('✓ Roles cargados exitosamente.');
    }
}
