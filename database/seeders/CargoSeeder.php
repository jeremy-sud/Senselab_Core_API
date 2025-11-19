<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoSeeder extends Seeder
{
    public function run(): void
    {
        $cargos = [
            ['nombre' => 'Gerente General', 'descripcion' => 'Responsable de la dirección general de la empresa'],
            ['nombre' => 'Contador', 'descripcion' => 'Encargado de la contabilidad y finanzas'],
            ['nombre' => 'Vendedor', 'descripcion' => 'Responsable de las ventas y atención al cliente'],
            ['nombre' => 'Cajero', 'descripcion' => 'Encargado de operaciones de caja'],
            ['nombre' => 'Bodeguero', 'descripcion' => 'Responsable del inventario y almacén'],
            ['nombre' => 'Conductor', 'descripcion' => 'Conductor de vehículos de transporte'],
            ['nombre' => 'Asistente Administrativo', 'descripcion' => 'Apoyo en tareas administrativas'],
        ];

        foreach ($cargos as $cargo) {
            DB::table('cargos')->insert([
                'nombre' => $cargo['nombre'],
                'descripcion' => $cargo['descripcion'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
