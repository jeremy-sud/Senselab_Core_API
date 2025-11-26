<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditoriaActividad;

class AuditoriaActividadSeeder extends Seeder
{
    public function run(): void
    {
        AuditoriaActividad::factory()->count(50)->create();
    }
}
