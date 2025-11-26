<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CodigoActividadEconomica;

class CodigoActividadEconomicaSeeder extends Seeder
{
    public function run(): void
    {
        CodigoActividadEconomica::factory()->count(30)->create();
    }
}
