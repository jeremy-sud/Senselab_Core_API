<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoImpuestoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo_hacienda' => '01', 'nombre' => 'IVA', 'descripcion' => 'Impuesto al Valor Agregado', 'comentario' => 'IVA General'],
            ['codigo_hacienda' => '02', 'nombre' => 'Impuesto Selectivo de Consumo', 'descripcion' => 'Impuesto para productos específicos', 'comentario' => 'ISC'],
            ['codigo_hacienda' => '03', 'nombre' => 'Impuesto Único sobre Combustibles', 'descripcion' => 'Impuesto sobre combustibles', 'comentario' => 'Combustibles'],
            ['codigo_hacienda' => '04', 'nombre' => 'Impuesto a Bebidas Alcohólicas', 'descripcion' => 'Impuesto a bebidas alcohólicas', 'comentario' => 'Bebidas Alcohólicas'],
            ['codigo_hacienda' => '05', 'nombre' => 'Impuesto a Bebidas Gaseosas', 'descripcion' => 'Impuesto a bebidas gaseosas', 'comentario' => 'Bebidas Gaseosas'],
            ['codigo_hacienda' => '99', 'nombre' => 'Otros', 'descripcion' => 'Otros impuestos', 'comentario' => 'Otros'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipos_impuesto')->insert([
                'codigo_hacienda' => $tipo['codigo_hacienda'],
                'nombre' => $tipo['nombre'],
                'descripcion' => $tipo['descripcion'],
                'Comentario' => $tipo['comentario'],
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ]);
        }
    }
}
