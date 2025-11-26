<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PeriodoNomina;

class PeriodoNominaSeeder extends Seeder
{
    public function run(): void
    {
        PeriodoNomina::factory()->count(12)->create();
    }
}
