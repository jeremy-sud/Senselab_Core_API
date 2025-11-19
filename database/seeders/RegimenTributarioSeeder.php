<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegimenTributarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('regimenes_tributarios')->insert([
            [
                'id' => 1,
                'codigo' => '01',
                'nombre' => 'Régimen Tradicional',
                'descripcion' => 'Régimen tributario tradicional para empresas en Costa Rica',
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'id' => 2,
                'codigo' => '02',
                'nombre' => 'Régimen Simplificado',
                'descripcion' => 'Régimen simplificado para pequeños contribuyentes',
                'activo' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);
    }
}
