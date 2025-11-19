<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoCuentaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Activo Corriente', 'descripcion' => 'Bienes y derechos líquidos en menos de un año', 'naturaleza' => 'Deudora'],
            ['nombre' => 'Activo No Corriente', 'descripcion' => 'Bienes y derechos a largo plazo', 'naturaleza' => 'Deudora'],
            ['nombre' => 'Pasivo Corriente', 'descripcion' => 'Obligaciones a corto plazo', 'naturaleza' => 'Acreedora'],
            ['nombre' => 'Pasivo No Corriente', 'descripcion' => 'Obligaciones a largo plazo', 'naturaleza' => 'Acreedora'],
            ['nombre' => 'Patrimonio', 'descripcion' => 'Capital y resultados acumulados', 'naturaleza' => 'Acreedora'],
            ['nombre' => 'Ingresos', 'descripcion' => 'Ingresos operacionales y no operacionales', 'naturaleza' => 'Acreedora'],
            ['nombre' => 'Costos', 'descripcion' => 'Costos de ventas y producción', 'naturaleza' => 'Deudora'],
            ['nombre' => 'Gastos', 'descripcion' => 'Gastos operacionales y administrativos', 'naturaleza' => 'Deudora'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipos_cuentas')->insert([
                'nombre' => $tipo['nombre'],
                'descripcion' => $tipo['descripcion'],
                'naturaleza' => $tipo['naturaleza'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
