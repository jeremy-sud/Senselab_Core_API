<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegimenesTributariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regimenes = [
            [
                'codigo' => '01',
                'nombre' => 'Régimen Tradicional',
                'descripcion' => 'Régimen tributario tradicional para empresas en Costa Rica',
                'activo' => true,
                'eliminado' => false,
            ],
            [
                'codigo' => '02',
                'nombre' => 'Régimen Simplificado',
                'descripcion' => 'Régimen simplificado para pequeños contribuyentes',
                'activo' => true,
                'eliminado' => false,
            ],
        ];

        foreach ($regimenes as $regimen) {
            DB::table('regimenes_tributarios')->updateOrInsert(
                ['codigo' => $regimen['codigo']],
                $regimen
            );
        }

        $this->command->info('✓ Regímenes tributarios cargados exitosamente.');
    }
}
