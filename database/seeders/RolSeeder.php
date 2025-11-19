<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso completo al sistema'],
            ['nombre' => 'Gerente', 'descripcion' => 'Acceso a funciones de gestión y reportes'],
            ['nombre' => 'Contador', 'descripcion' => 'Acceso a módulo contable y financiero'],
            ['nombre' => 'Vendedor', 'descripcion' => 'Acceso a módulo de ventas y clientes'],
            ['nombre' => 'Cajero', 'descripcion' => 'Acceso a punto de venta y caja'],
            ['nombre' => 'Bodeguero', 'descripcion' => 'Acceso a inventario y almacén'],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->insert([
                'nombre' => $rol['nombre'],
                'descripcion' => $rol['descripcion'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
