<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZonasGeograficasCRSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provincias = [
            [
                'empresa_id' => null, // NULL = catálogo nacional
                'codigo' => 'SJ',
                'nombre' => 'San José',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'AL',
                'nombre' => 'Alajuela',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'CA',
                'nombre' => 'Cartago',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'HE',
                'nombre' => 'Heredia',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'GU',
                'nombre' => 'Guanacaste',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'PU',
                'nombre' => 'Puntarenas',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
            [
                'empresa_id' => null,
                'codigo' => 'LI',
                'nombre' => 'Limón',
                'tipo' => 'provincia',
                'zona_padre_id' => null,
                'provincias_incluidas' => null,
                'vendedor_asignado_id' => null,
                'activa' => true,
                'eliminado' => false,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ];

        foreach ($provincias as $provincia) {
            DB::table('zonas_geograficas')->updateOrInsert(
                ['codigo' => $provincia['codigo'], 'tipo' => 'provincia'],
                collect($provincia)->except('codigo', 'tipo')->toArray()
            );
        }

        $this->command->info('✓ 7 provincias de Costa Rica cargadas exitosamente.');
    }
}
