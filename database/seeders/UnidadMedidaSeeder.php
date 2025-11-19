<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            ['codigo_dgt' => 'Unid', 'nombre' => 'Unidad', 'descripcion' => 'Unidad individual'],
            ['codigo_dgt' => 'Kg', 'nombre' => 'Kilogramo', 'descripcion' => 'Kilogramo'],
            ['codigo_dgt' => 'g', 'nombre' => 'Gramo', 'descripcion' => 'Gramo'],
            ['codigo_dgt' => 'L', 'nombre' => 'Litro', 'descripcion' => 'Litro'],
            ['codigo_dgt' => 'mL', 'nombre' => 'Mililitro', 'descripcion' => 'Mililitro'],
            ['codigo_dgt' => 'm', 'nombre' => 'Metro', 'descripcion' => 'Metro lineal'],
            ['codigo_dgt' => 'm2', 'nombre' => 'Metro cuadrado', 'descripcion' => 'Metro cuadrado'],
            ['codigo_dgt' => 'm3', 'nombre' => 'Metro cúbico', 'descripcion' => 'Metro cúbico'],
            ['codigo_dgt' => 'Caja', 'nombre' => 'Caja', 'descripcion' => 'Caja'],
            ['codigo_dgt' => 'Paq', 'nombre' => 'Paquete', 'descripcion' => 'Paquete'],
            ['codigo_dgt' => 'Serv', 'nombre' => 'Servicio', 'descripcion' => 'Servicio'],
        ];

        foreach ($unidades as $unidad) {
            DB::table('unidades_medida')->insert([
                'codigo_dgt' => $unidad['codigo_dgt'],
                'nombre' => $unidad['nombre'],
                'descripcion' => $unidad['descripcion'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
